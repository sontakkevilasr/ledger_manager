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
            'description'          => 'nullable|string',
            'registered_on'        => 'nullable|date',
            'is_active'            => 'nullable|boolean',
            // Opening balance — stored as a transaction
            'ob_amount'            => 'nullable|numeric|min:0',
            'ob_type'              => 'nullable|in:Dr,Cr',
            'ob_date'              => 'nullable|date',
            'ob_description'       => 'nullable|string|max:255',
        ]);

        $data['customer_name'] = strtoupper(trim($data['customer_name']));
        $data['city']          = strtoupper(trim($data['city'] ?? ''));
        $data['state']         = ucwords(strtolower(trim($data['state'] ?? '')));
        $data['is_active']     = $request->boolean('is_active', true);
        $data['registered_on'] = $data['registered_on'] ?? now()->toDateString();
        $data['created_by']    = Auth::id();
        $data['updated_by']    = Auth::id();

        // Remove opening balance fields — they go into transactions, not customers
        $obAmount      = (float) ($data['ob_amount'] ?? 0);
        $obType        = $data['ob_type'] ?? 'Dr';
        $obDate        = $data['ob_date'] ?? $data['registered_on'];
        $obDescription = $data['ob_description'] ?? 'Opening Balance';

        unset($data['ob_amount'], $data['ob_type'], $data['ob_date'], $data['ob_description']);

        DB::transaction(function () use ($data, $obAmount, $obType, $obDate, $obDescription) {
            $customer = Customer::create($data);

            // ── Create opening balance transaction if amount > 0 ───────────
            if ($obAmount > 0) {
                Transaction::create([
                    'customer_id'      => $customer->id,
                    'payment_type_id'  => null,
                    'agent_id'         => null,
                    'description'      => $obDescription,
                    'transaction_date' => $obDate,
                    'credit'           => $obType === 'Cr' ? $obAmount : 0,
                    'debit'            => $obType === 'Dr' ? $obAmount : 0,
                    'type'             => $obType === 'Dr' ? 'Debit' : 'Credit',
                    'is_opening'       => true,
                    'remark'           => null,
                    'created_by'       => Auth::id(),
                    'updated_by'       => Auth::id(),
                ]);
            }

            $this->redirectCustomer = $customer;
        });

        $customer = Customer::where('customer_name', $data['customer_name'])
            ->latest()
            ->first();

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Customer [{$customer->customer_name}] added successfully.");
    }

    // ── View single customer + ledger ─────────────────────────
    public function show(Customer $customer, Request $request)
    {
        $this->checkPermission('customers.view');

        $viewAll = $request->boolean('all');

        // Date range filter for ledger
        if ($viewAll) {
            $from = null;
            $to   = null;
            $balanceBroughtForward = 0.0;
        } else {
            $from = $request->get('from', now()->startOfYear()->toDateString());
            $to   = $request->get('to', now()->toDateString());

            // B/F = SUM of ALL transactions BEFORE $from date
            $balanceBroughtForward = (float) Transaction::forCustomer($customer->id)
                ->where('transaction_date', '<', $from)
                ->whereNull('deleted_at')
                ->selectRaw('SUM(debit) - SUM(credit) as net')
                ->value('net');
        }

        // ── Transactions ───────────────────────────────────────
        $txnQuery = Transaction::forCustomer($customer->id)
            ->with(['paymentType', 'agent', 'createdBy'])
            ->orderBy('is_opening', 'desc')
            ->orderBy('transaction_date')
            ->orderBy('id');

        if (! $viewAll) {
            $txnQuery->dateRange($from, $to);
        }

        $transactions = $txnQuery->get();

        // ── Build ledger rows with running balance ─────────────
        $runningBalance = $balanceBroughtForward;

        $ledger = $transactions->map(function ($txn) use (&$runningBalance) {
            $runningBalance += ($txn->debit - $txn->credit);
            return array_merge($txn->toArray(), [
                'running_balance' => round($runningBalance, 2),
            ]);
        });

        // ── Period totals (excluding opening balance from credit/debit display) ──
        $totalCredit = $transactions->where('is_opening', false)->sum('credit');
        $totalDebit  = $transactions->where('is_opening', false)->sum('debit');
        $closingBalance = $balanceBroughtForward
            + $transactions->sum('debit')
            - $transactions->sum('credit');

        // True all-time balance for the header card
        $trueBalance = $customer->balance;

        ActivityLogger::log(
            'viewed', 'customers',
            $customer->id, $customer->customer_name,
            "Viewed ledger for {$customer->customer_name} " .
                ($viewAll ? '(all time)' : "({$from} to {$to})")
        );

        return view('customers.show', compact(
            'customer', 'ledger', 'from', 'to', 'viewAll',
            'totalCredit', 'totalDebit',
            'balanceBroughtForward', 'closingBalance', 'trueBalance'
        ));
    }

    // ── Edit form ─────────────────────────────────────────────
    public function edit(Customer $customer)
    {
        $this->checkPermission('customers.edit');

        // Load the existing opening transaction if any
        $openingTransaction = $customer->transactions()
            ->where('is_opening', true)
            ->whereNull('deleted_at')
            ->first();

        return view('customers.edit', compact('customer', 'openingTransaction'));
    }

    // ── Update customer ───────────────────────────────────────
    public function update(Request $request, Customer $customer)
    {
        $this->checkPermission('customers.edit');

        $data = $request->validate([
            'customer_name'  => 'required|string|max:150',
            'phone'          => 'nullable|string|max:20',
            'mobile'         => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'zip_code'       => 'nullable|string|max:10',
            'description'    => 'nullable|string',
            'registered_on'  => 'nullable|date',
            'is_active'      => 'nullable|boolean',
            // Opening balance transaction fields
            'ob_amount'      => 'nullable|numeric|min:0',
            'ob_type'        => 'nullable|in:Dr,Cr',
            'ob_date'        => 'nullable|date',
            'ob_description' => 'nullable|string|max:255',
        ]);

        $data['customer_name'] = strtoupper(trim($data['customer_name']));
        $data['city']          = strtoupper(trim($data['city'] ?? ''));
        $data['is_active']     = $request->boolean('is_active');
        $data['updated_by']    = Auth::id();

        $obAmount      = (float) ($data['ob_amount'] ?? 0);
        $obType        = $data['ob_type'] ?? 'Dr';
        $obDate        = $data['ob_date'] ?? now()->toDateString();
        $obDescription = $data['ob_description'] ?? 'Opening Balance';

        unset($data['ob_amount'], $data['ob_type'], $data['ob_date'], $data['ob_description']);

        DB::transaction(function () use ($data, $customer, $obAmount, $obType, $obDate, $obDescription) {
            $customer->update($data);

            // ── Handle opening balance transaction ─────────────
            $existing = $customer->transactions()
                ->where('is_opening', true)
                ->whereNull('deleted_at')
                ->first();

            if ($obAmount > 0) {
                $txnData = [
                    'customer_id'      => $customer->id,
                    'description'      => $obDescription,
                    'transaction_date' => $obDate,
                    'credit'           => $obType === 'Cr' ? $obAmount : 0,
                    'debit'            => $obType === 'Dr' ? $obAmount : 0,
                    'type'             => $obType === 'Dr' ? 'Debit' : 'Credit',
                    'is_opening'       => true,
                    'updated_by'       => Auth::id(),
                ];

                if ($existing) {
                    $existing->update($txnData);
                } else {
                    Transaction::create(array_merge($txnData, [
                        'payment_type_id' => null,
                        'agent_id'        => null,
                        'remark'          => null,
                        'created_by'      => Auth::id(),
                    ]));
                }
            } else {
                // Amount set to 0 — soft delete the opening transaction
                if ($existing) {
                    $existing->delete();
                }
            }
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Customer [{$customer->customer_name}] updated successfully.");
    }

    // ── Soft delete customer ──────────────────────────────────
    public function destroy(Customer $customer)
    {
        $this->checkPermission('customers.delete');

        // Count non-opening transactions
        $txnCount = $customer->transactions()
            ->where('is_opening', false)
            ->count();

        if ($txnCount > 0) {
            return back()->with('error',
                "Cannot delete [{$customer->customer_name}] — they have transactions. Deactivate instead."
            );
        }

        $name = $customer->customer_name;
        $customer->delete();

        return redirect()->route('customers.index')
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


    // ── Purge (permanent hard delete) ────────────────────────
    //
    // Completely and irreversibly removes the customer and ALL their
    // transaction history from the database.
    //
    // Guards:
    //   1. allow_customer_purge config must be ON (set in Settings)
    //   2. User must be Super Admin
    //   3. Request must include correct customer name confirmation
    //   4. Action is logged BEFORE deletion so the log survives
    //
    public function purge(Request $request, Customer $customer)
    {
        // Guard 1 — feature must be enabled in settings
        if (! config('app.allow_customer_purge', false)) {
            abort(403, 'Customer purge is not enabled. Enable it in Settings → Danger Zone.');
        }

        // Guard 2 — Super Admin only
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can permanently delete customers.');
        }

        // Guard 3 — name confirmation must match exactly
        $request->validate([
            'confirm_name' => 'required|string',
        ]);

        if (trim($request->confirm_name) !== $customer->customer_name) {
            return back()->with('purge_error',
                'Customer name did not match. Please type the exact name to confirm.'
            );
        }

        // Gather stats before deletion for the audit log
        $txnCount    = $customer->transactions()->withTrashed()->count();
        $balance     = $customer->balance;
        $customerName = $customer->customer_name;

        // Guard 4 — Log BEFORE deletion so this record survives
        ActivityLogger::log(
            'purged', 'customers',
            $customer->id,
            $customerName,
            "TRANSACTION PURGE: Permanently deleted {$txnCount} transactions " .
            "for customer [{$customerName}]. " .
            "Balance was " . number_format(abs($balance), 2) .
            ($balance > 0 ? ' Dr' : ($balance < 0 ? ' Cr' : ' (settled)')) .
            ". Purged by [" . Auth::user()->name . "]. Customer record kept."
        );

        DB::transaction(function () use ($customer) {
            // Hard delete ALL transactions only — customer record is KEPT
            $customer->transactions()->withTrashed()->forceDelete();
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success',
                "{$txnCount} transactions for [{$customerName}] have been permanently deleted. Customer record is preserved."
            );
    }

    // ── Permission helper ─────────────────────────────────────
    private function checkPermission(string $permission): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
