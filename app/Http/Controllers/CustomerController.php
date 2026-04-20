<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    // ── List all customers ────────────────────────────────────
    public function index(Request $request)
    {
        $this->checkPermission('customers.view');

        $query = Customer::withCount('transactions')->with('createdBy');

        // Search
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        // Filter by status
        if ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->get('status') !== 'all') {
            $query->active();
        }

        // Filter by city
        if ($city = $request->get('city')) {
            $query->where('city', $city);
        }

        // Sort
        $sort    = $request->get('sort', 'customer_name');
        $dir     = $request->get('dir', 'asc');
        $allowed = ['customer_name', 'city', 'mobile', 'registered_on', 'transactions_count'];
        $query->orderBy(in_array($sort, $allowed) ? $sort : 'customer_name', $dir === 'desc' ? 'desc' : 'asc');

        $customers = $query->paginate(25)->withQueryString();

        // For city filter dropdown
        $cities = Customer::active()->distinct()->orderBy('city')->pluck('city')->filter();

        ActivityLogger::log('viewed', 'customers', description: 'Viewed customer list');

        return view('customers.index', compact('customers', 'cities'));
    }

    // ── Show add form ─────────────────────────────────────────
    public function create()
    {
        $this->checkPermission('customers.create');
        return view('customers.create');
    }

    // ── Save new customer ─────────────────────────────────────
    public function store(Request $request)
    {
        $this->checkPermission('customers.create');

        $data = $request->validate([
            'customer_name'        => 'required|string|max:150',
            'phone'                => 'nullable|string|max:20',
            'mobile'               => 'nullable|string|max:20',
            'email'                => 'nullable|email|max:150',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'state'                => 'nullable|string|max:100',
            'zip_code'             => 'nullable|string|max:10',
            'opening_balance'      => 'nullable|numeric|min:0',
            'opening_balance_type' => 'required|in:Dr,Cr',
            'registered_on'        => 'nullable|date',
            'is_active'            => 'nullable|boolean',
            'description'          => 'nullable|string',
        ]);

        $data['customer_name']        = strtoupper(trim($data['customer_name']));
        $data['city']                 = strtoupper(trim($data['city'] ?? ''));
        $data['state']                = ucwords(strtolower(trim($data['state'] ?? '')));
        $data['opening_balance']      = $data['opening_balance'] ?? 0;
        $data['opening_balance_type'] = $data['opening_balance_type'] ?? 'Dr';
        $data['is_active']            = $request->boolean('is_active', true);
        $data['registered_on']        = $data['registered_on'] ?? now()->toDateString();
        $data['created_by']           = Auth::id();
        $data['updated_by']           = Auth::id();

        // If opening balance is 0, type doesn't matter — normalize to Dr
        if ((float)$data['opening_balance'] === 0.0) {
            $data['opening_balance_type'] = 'Dr';
        }

        $customer = Customer::create($data);
        // LogsActivity trait auto-logs the create

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Customer [{$customer->customer_name}] added successfully.");
    }

    // ── View single customer + ledger ─────────────────────────
    public function show(Customer $customer, Request $request)
    {
        $this->checkPermission('customers.view');

        // Date range filter for ledger
        $from = $request->get('from', now()->startOfYear()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $transactions = Transaction::forCustomer($customer->id)
            ->dateRange($from, $to)
            ->with(['paymentType', 'agent', 'createdBy'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // Running balance starts from opening_balance (signed)
        $runningBalance = $customer->opening_balance_signed;

        $ledger = $transactions->map(function ($txn) use (&$runningBalance) {
            // Dr transaction = customer owes more → increase running Dr balance
            // Cr transaction = customer paid → decrease running Dr balance
            $runningBalance += ($txn->debit - $txn->credit);
            return array_merge($txn->toArray(), [
                'running_balance' => $runningBalance,
                // Positive = Dr (to collect), Negative = Cr (to pay)
            ]);
        });

        // Summary stats (date-filtered)
        $totalCredit = $transactions->sum('credit');
        $totalDebit  = $transactions->sum('debit');
        $netBalance  = $customer->opening_balance_signed + $totalDebit - $totalCredit;

        // All-time totals (unaffected by date filter)
        $allTxns     = Transaction::forCustomer($customer->id)->get();
        $customer->total_credit = $allTxns->sum('credit');
        $customer->total_debit  = $allTxns->sum('debit');
        $trueBalance = $customer->opening_balance_signed + $customer->total_debit - $customer->total_credit;

        // Balance brought forward = opening + all transactions strictly before $from
        $priorTxns = Transaction::forCustomer($customer->id)
            ->where('transaction_date', '<', $from)
            ->get();
        $balanceBroughtForward = $customer->opening_balance_signed
            + $priorTxns->sum('debit')
            - $priorTxns->sum('credit');

        // Closing balance = balance brought forward + period debit - period credit
        $closingBalance = $balanceBroughtForward + $totalDebit - $totalCredit;

        ActivityLogger::log(
            'viewed', 'customers',
            $customer->id, $customer->customer_name,
            "Viewed ledger for {$customer->customer_name}"
        );

        return view('customers.show', compact(
            'customer', 'ledger', 'from', 'to',
            'totalCredit', 'totalDebit', 'netBalance', 'trueBalance',
            'balanceBroughtForward', 'closingBalance'
        ));
    }

    // ── Show edit form ────────────────────────────────────────
    public function edit(Customer $customer)
    {
        $this->checkPermission('customers.edit');
        return view('customers.edit', compact('customer'));
    }

    // ── Update customer ───────────────────────────────────────
    public function update(Request $request, Customer $customer)
    {
        $this->checkPermission('customers.edit');

        $data = $request->validate([
            'customer_name'        => 'required|string|max:150',
            'phone'                => 'nullable|string|max:20',
            'mobile'               => 'nullable|string|max:20',
            'email'                => 'nullable|email|max:150',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'state'                => 'nullable|string|max:100',
            'zip_code'             => 'nullable|string|max:10',
            'opening_balance'      => 'nullable|numeric|min:0',
            'opening_balance_type' => 'required|in:Dr,Cr',
            'registered_on'        => 'nullable|date',
            'is_active'            => 'nullable|boolean',
            'description'          => 'nullable|string',
        ]);

        $data['customer_name'] = strtoupper(trim($data['customer_name']));
        $data['city']          = strtoupper(trim($data['city'] ?? ''));
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = Auth::id();

        if ((float)($data['opening_balance'] ?? 0) === 0.0) {
            $data['opening_balance_type'] = 'Dr';
        }

        $customer->update($data);
        // LogsActivity trait auto-logs the update with old/new values

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Customer [{$customer->customer_name}] updated successfully.");
    }

    // ── Soft delete customer ──────────────────────────────────
    public function destroy(Customer $customer)
    {
        $this->checkPermission('customers.delete');

        // Prevent deleting customer with transactions
        if ($customer->transactions()->count() > 0) {
            return back()->with('error',
                "Cannot delete [{$customer->customer_name}] — they have transactions. Deactivate instead."
            );
        }

        $name = $customer->customer_name;
        $customer->delete();    // soft delete — LogsActivity trait logs it

        return redirect()
            ->route('customers.index')
            ->with('success', "Customer [{$name}] deleted.");
    }

    // ── Toggle active/inactive ────────────────────────────────
    public function toggleStatus(Customer $customer)
    {
        $this->checkPermission('customers.edit');

        $customer->update([
            'is_active'  => ! $customer->is_active,
            'updated_by' => Auth::id(),
        ]);

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Customer [{$customer->customer_name}] {$status}.");
    }

    // ── Helper: check permission or abort ─────────────────────
    private function checkPermission(string $permission): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
