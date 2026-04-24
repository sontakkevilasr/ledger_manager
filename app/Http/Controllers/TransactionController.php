<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\PaymentType;
use App\Models\Agent;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // ── List all transactions (with filters) ──────────────────
    public function index(Request $request)
    {
        $this->checkPermission('transactions.view');

        $sortBy  = in_array($request->get('sort_by'), ['transaction_date', 'type', 'credit', 'debit'])
            ? $request->get('sort_by')
            : 'transaction_date';

        $hasFilter = $request->filled('customer_id')
            || $request->filled('type')
            || $request->filled('agent_id')
            || $request->filled('from')
            || $request->filled('to')
            || $request->filled('search')
            || $request->filled('amount');
        $defaultDir = $hasFilter ? 'asc' : 'desc';
        $sortDir = in_array($request->get('sort_dir'), ['asc', 'desc'])
            ? $request->get('sort_dir')
            : $defaultDir;

        $query = Transaction::with(['customer', 'paymentType', 'agent', 'createdBy'])
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', $sortDir);

        // Filter by customer
        $customerId = $request->get('customer_id');
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        // Filter by type
        $type = $request->get('type');
        if ($type) {
            $query->where('type', ucfirst($type));
        }

        // Filter by date range
        $from = $request->get('from');
        $to   = $request->get('to');
        if ($from && $to) {
            $query->dateRange($from, $to);
        }

        // Filter by agent
        $agentId = $request->get('agent_id');
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        // Filter by description
        $search = trim($request->get('search', ''));
        if ($search !== '') {
            $query->where('description', 'like', '%' . $search . '%');
        }

        // Filter by amount
        $amount = $request->get('amount');
        if ($amount !== null && $amount !== '') {
            $query->where(function ($q) use ($amount) {
                $q->where('credit', $amount)->orWhere('debit', $amount);
            });
        }

        $transactions = $query->paginate(30)->withQueryString();

        // ── Summary cards — MUST apply ALL the same filters as $query ──────
        $summary = Transaction::when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($type,              fn($q) => $q->where('type', ucfirst($type)))
            ->when($from && $to,       fn($q) => $q->dateRange($from, $to))
            ->when($agentId ?? null,   fn($q) => $q->where('agent_id', $agentId))
            ->when($search !== '',     fn($q) => $q->where('description', 'like', '%' . $search . '%'))
            ->when($amount !== null && $amount !== '', fn($q) => $q->where(fn($q2) => $q2->where('credit', $amount)->orWhere('debit', $amount)))
            ->selectRaw('SUM(credit) as total_credit, SUM(debit) as total_debit, COUNT(*) as total_count')
            ->first();

        $customers    = Customer::active()->orderBy('customer_name')->pluck('customer_name', 'id');
        $agents       = Agent::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        $paymentTypes = PaymentType::where('is_active', true)->pluck('payment_type', 'id');

        return view('transactions.index', compact(
            'transactions', 'summary', 'customers', 'agents', 'paymentTypes', 'sortBy', 'sortDir'
        ));
    }

    // ── Show add transaction form ─────────────────────────────
    public function create(Request $request)
    {
        $this->checkPermission('transactions.create');

        $customers    = Customer::active()->orderBy('customer_name')->pluck('customer_name', 'id');
        $paymentTypes = PaymentType::where('is_active', true)->pluck('payment_type', 'id');
        $agents       = Agent::where('is_active', true)->orderBy('name')->pluck('name', 'id');

        // Pre-select customer if coming from ledger page
        $selectedCustomer = $request->get('customer_id')
            ? Customer::find($request->get('customer_id'))
            : null;

        return view('transactions.create', compact(
            'customers', 'paymentTypes', 'agents', 'selectedCustomer'
        ));
    }

    // ── Save transaction ──────────────────────────────────────
    public function store(Request $request)
    {
        $this->checkPermission('transactions.create');

        $data = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'type'             => 'required|in:Credit,Debit',
            'amount'           => 'required|numeric|min:0.01|max:99999999',
            'transaction_date' => 'required|date|before_or_equal:today',
            'description'      => 'nullable|string|max:255',
            'payment_type_id'  => 'nullable|exists:payment_types,id',
            'agent_id'         => 'nullable|exists:agents,id',
            'remark'           => 'nullable|string|max:150',
        ]);

        // Set credit/debit based on type
        $amount = (float) $data['amount'];

        $transaction = Transaction::create([
            'customer_id'      => $data['customer_id'],
            'type'             => $data['type'],
            'credit'           => $data['type'] === 'Credit' ? $amount : 0,
            'debit'            => $data['type'] === 'Debit'  ? $amount : 0,
            'transaction_date' => $data['transaction_date'],
            'description'      => $data['description'] ?? null,
            'payment_type_id'  => $data['payment_type_id'] ?? null,
            'agent_id'         => $data['agent_id'] ?? null,
            'remark'           => $data['remark'] ?? null,
            'created_by'       => Auth::id(),
            'updated_by'       => Auth::id(),
        ]);

        $customer = Customer::find($data['customer_id']);

        return redirect()
            ->route('customers.show', $data['customer_id'])
            ->with('success',
                "{$data['type']} of ₹" . number_format($amount, 2) .
                " added for [{$customer->customer_name}]."
            );
    }

    // ── Show single transaction ───────────────────────────────
    public function show(Transaction $transaction)
    {
        $this->checkPermission('transactions.view');

        $transaction->load(['customer', 'paymentType', 'agent', 'createdBy', 'updatedBy']);

        // Fetch activity log for this transaction
        $logs = \App\Models\ActivityLog::where('module', 'transaction')
            ->where('record_id', $transaction->id)
            ->orderByDesc('created_at')
            ->get();

        return view('transactions.show', compact('transaction', 'logs'));
    }

    // ── Edit transaction ──────────────────────────────────────
    public function edit(Transaction $transaction)
    {
        $this->checkPermission('transactions.edit');

        // Accountants can only edit their own entries
        if (! Auth::user()->isSuperAdmin() && ! Auth::user()->hasRole('manager')) {
            if ($transaction->created_by !== Auth::id()) {
                return redirect()->route('transactions.index')
                    ->with('error', 'You can only edit your own transactions.');
            }
        }

        $customers    = Customer::active()->orderBy('customer_name')->pluck('customer_name', 'id');
        $paymentTypes = PaymentType::where('is_active', true)->pluck('payment_type', 'id');
        $agents       = Agent::where('is_active', true)->orderBy('name')->pluck('name', 'id');

        return view('transactions.edit', compact('transaction', 'customers', 'paymentTypes', 'agents'));
    }

    // ── Update transaction ────────────────────────────────────
    public function update(Request $request, Transaction $transaction)
    {
        $this->checkPermission('transactions.edit');

        $data = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'type'             => 'required|in:Credit,Debit',
            'amount'           => 'required|numeric|min:0.01|max:99999999',
            'transaction_date' => 'required|date',
            'description'      => 'nullable|string|max:255',
            'payment_type_id'  => 'nullable|exists:payment_types,id',
            'agent_id'         => 'nullable|exists:agents,id',
            'remark'           => 'nullable|string|max:150',
        ]);

        $amount = (float) $data['amount'];

        $transaction->update([
            'customer_id'      => $data['customer_id'],
            'type'             => $data['type'],
            'credit'           => $data['type'] === 'Credit' ? $amount : 0,
            'debit'            => $data['type'] === 'Debit'  ? $amount : 0,
            'transaction_date' => $data['transaction_date'],
            'description'      => $data['description'] ?? null,
            'payment_type_id'  => $data['payment_type_id'] ?? null,
            'agent_id'         => $data['agent_id'] ?? null,
            'remark'           => $data['remark'] ?? null,
            'updated_by'       => Auth::id(),
        ]);
        // LogsActivity trait auto-logs old_values vs new_values

        return redirect()
            ->route('customers.show', $transaction->customer_id)
            ->with('success', 'Transaction updated successfully.');
    }

    // ── Soft delete transaction ───────────────────────────────
    public function destroy(Transaction $transaction)
    {
        $this->checkPermission('transactions.delete');

        $customerId = $transaction->customer_id;
        $desc       = $transaction->description ?? "#{$transaction->id}";

        $transaction->delete();    // soft delete — history preserved

        return redirect()
            ->route('customers.show', $customerId)
            ->with('success', "Transaction [{$desc}] deleted.");
    }

    // ── Quick AJAX: get customer current balance ──────────────
    public function getBalance(Customer $customer)
    {
        $this->checkPermission('transactions.view');

        return response()->json([
            'customer_name'   => $customer->customer_name,
            'balance'         => $customer->balance,
            'total_credit'    => $customer->total_credit,
            'total_debit'     => $customer->total_debit,
            'opening_balance' => $customer->opening_balance,
        ]);
    }

    // ── Helper ────────────────────────────────────────────────
    private function checkPermission(string $permission): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->hasPermission($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
