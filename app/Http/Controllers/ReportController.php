<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Agent;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ── Balance Summary — all customers ───────────────────────
    public function balanceSummary(Request $request)
    {
        $this->checkPermission('reports.view');

        // ── FORMULA (must match Customer model and Dashboard everywhere) ──────
        //
        // net_balance = opening_balance_signed + SUM(debit) - SUM(credit)
        //
        // opening_balance_signed:
        //   Dr → +opening_balance  (customer owes us from before)
        //   Cr → -opening_balance  (we owe customer from before)
        //
        // Result:
        //   Positive (+) → Dr balance → customer owes Aman Traders → "To Collect"
        //   Negative (-) → Cr balance → Aman Traders owes customer → "To Pay"
        //   Zero         → Settled

        $cols = [
            'customers.id', 'customers.customer_name', 'customers.phone',
            'customers.mobile', 'customers.email', 'customers.address',
            'customers.city', 'customers.state', 'customers.zip_code',
            'customers.opening_balance', 'customers.opening_balance_type',
            'customers.registered_on', 'customers.is_active',
            'customers.created_by', 'customers.updated_by',
            'customers.created_at', 'customers.updated_at', 'customers.deleted_at',
        ];

        $query = Customer::select($cols)
            ->selectRaw("
                (
                    CASE WHEN customers.opening_balance_type = 'Dr'
                        THEN  customers.opening_balance
                        ELSE -customers.opening_balance
                    END
                )
                + COALESCE(SUM(t.debit),  0)
                - COALESCE(SUM(t.credit), 0) AS net_balance,
                COALESCE(SUM(t.credit), 0)   AS total_credit,
                COALESCE(SUM(t.debit),  0)   AS total_debit
            ")
            ->leftJoin('transactions as t', function ($join) {
                $join->on('customers.id', '=', 't.customer_id')
                     ->whereNull('t.deleted_at');
            })
            ->groupBy($cols);

        // ── Filters ────────────────────────────────────────────
        $filter = $request->get('filter', 'all');

        if ($filter === 'debit') {
            // Dr balance: positive net = customer owes us
            $query->having('net_balance', '>', 0.009);
        } elseif ($filter === 'credit') {
            // Cr balance: negative net = we owe customer
            $query->having('net_balance', '<', -0.009);
        } elseif ($filter === 'zero') {
            $query->havingRaw('ABS(ROUND(net_balance, 2)) < 0.01');
        }

        if ($city = $request->get('city')) {
            $query->where('customers.city', $city);
        }

        if ($search = $request->get('search')) {
            $query->where('customers.customer_name', 'like', "%{$search}%");
        }

        $sort = $request->get('sort', 'customer_name');
        $dir  = $request->get('dir', 'asc');
        $query->orderBy($sort === 'balance' ? 'net_balance' : 'customers.customer_name', $dir);

        $customers = $query->paginate(30)->withQueryString();

        // ── Grand Totals (must include opening balances) ────────
        //
        // grand_outstanding = SUM of every customer's net_balance
        // = SUM(opening_balance_signed) + SUM(all debits) - SUM(all credits)
        //
        // This must match the Dashboard's $totalOutstanding exactly.
        $totals = DB::table('customers')
            ->selectRaw("
                SUM(
                    CASE WHEN opening_balance_type = 'Dr'
                        THEN  opening_balance
                        ELSE -opening_balance
                    END
                ) AS total_opening_signed,
                COALESCE(SUM(t.credit), 0) AS grand_credit,
                COALESCE(SUM(t.debit),  0) AS grand_debit
            ")
            ->leftJoin('transactions as t', function ($join) {
                $join->on('customers.id', '=', 't.customer_id')
                     ->whereNull('t.deleted_at');
            })
            ->first();

        // grand_outstanding: positive = Dr (net to collect), negative = Cr (net to pay)
        $grandOutstanding = ($totals->total_opening_signed ?? 0)
                          + ($totals->grand_debit ?? 0)
                          - ($totals->grand_credit ?? 0);

        $cities = Customer::active()->distinct()->orderBy('city')->pluck('city')->filter();

        ActivityLogger::log('viewed', 'reports', description: 'Viewed balance summary report');

        return view('reports.balance-summary', compact(
            'customers', 'totals', 'grandOutstanding', 'cities', 'filter'
        ));
    }

    // ── Date Range Report ─────────────────────────────────────
    public function dateRange(Request $request)
    {
        $this->checkPermission('reports.view');

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        // $request->validate([
        //     'from' => 'required|date',
        //     'to'   => 'required|date|after_or_equal:from',
        // ]);
        
        // Group by customer, sum credit/debit in date range
        $results = DB::table('customers')
            ->join('transactions', 'customers.id', '=', 'transactions.customer_id')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('transactions.transaction_date', [$from, $to])
            ->select(
                'customers.id',
                'customers.customer_name',
                'customers.city',
                'customers.mobile'
            )
            ->selectRaw('SUM(transactions.credit) as credit, SUM(transactions.debit) as debit')
            ->selectRaw('SUM(transactions.credit) - SUM(transactions.debit) as balance')
            ->groupBy('customers.id', 'customers.customer_name', 'customers.city', 'customers.mobile')
            ->orderBy('customers.customer_name')
            ->get();

        $summary = [
            'total_credit' => $results->sum('credit'),
            'total_debit'  => $results->sum('debit'),
            'total_balance'=> $results->sum('balance'),
            'count'        => $results->count(),
        ];

        ActivityLogger::log('viewed', 'reports', description: "Viewed date range report: {$from} to {$to}");

        return view('reports.date-range', compact('results', 'summary', 'from', 'to'));
    }

    // ── Customer Ledger (printable) ───────────────────────────
    public function customerLedger(Request $request)
    {
        $this->checkPermission('reports.view');

        $customerId = $request->get('customer_id');
        $from       = $request->get('from', now()->startOfYear()->toDateString());
        $to         = $request->get('to', now()->toDateString());

        $customer = $customerId ? Customer::findOrFail($customerId) : null;

        $transactions = collect();
        $runningBalance = 0;
        $ledger         = collect();

        if ($customer) {
            $transactions = Transaction::forCustomer($customer->id)
                ->dateRange($from, $to)
                ->with(['paymentType', 'agent', 'createdBy'])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $runningBalance = $customer->opening_balance;
            $ledger = $transactions->map(function ($txn) use (&$runningBalance) {
                $runningBalance += ($txn->credit - $txn->debit);
                return array_merge($txn->toArray(), ['running_balance' => $runningBalance]);
            });

            ActivityLogger::log(
                'viewed', 'reports',
                $customer->id, $customer->customer_name,
                "Viewed ledger report for {$customer->customer_name} ({$from} to {$to})"
            );
        }

        $customers = Customer::active()->orderBy('customer_name')->pluck('customer_name', 'id');

        return view('reports.customer-ledger', compact(
            'customers', 'customer', 'ledger', 'from', 'to', 'runningBalance'
        ));
    }

    // ── City-wise report ──────────────────────────────────────
    public function cityWise(Request $request)
    {
        $this->checkPermission('reports.view');

        // ── MUST use the same formula as Dashboard city-wise ──────────────
        //
        // Three fixes vs old code:
        //
        // 1. opening_balance_signed must be included
        //    Old: SUM(debit) - SUM(credit)              ← wrong, misses opening
        //    New: opening_signed + SUM(debit) - SUM(credit) ← correct
        //
        // 2. Use correlated subquery (not INNER JOIN) so customers with
        //    zero transactions but a non-zero opening balance are included
        //    Old: INNER JOIN transactions ← excludes no-transaction customers
        //    New: correlated subquery with COALESCE(..., 0) ← includes all
        //
        // 3. Respect opening_balance_type (Dr / Cr)
        //    Old: always added opening_balance
        //    New: CASE WHEN Dr THEN +amount ELSE -amount END
        //
        // outstanding per city = SUM across all city customers of:
        //   ( signed opening balance ) + ( net of their transactions )
        // Positive = Dr (city owes us overall)  → "To Collect"
        // Negative = Cr (we owe city overall)   → "To Pay"

        $txnSub = DB::table('transactions')
            ->selectRaw('customer_id, SUM(credit) as total_credit, SUM(debit) as total_debit, SUM(debit) - SUM(credit) as net')
            ->whereNull('deleted_at')
            ->groupBy('customer_id');

        $results = DB::table('customers')
            ->leftJoinSub($txnSub, 'txn', 'customers.id', '=', 'txn.customer_id')
            ->where('customers.is_active', true)
            ->select('customers.city', 'customers.state')
            ->selectRaw('COUNT(customers.id) as customer_count')
            ->selectRaw('COALESCE(SUM(txn.total_credit), 0) AS total_credit')
            ->selectRaw('COALESCE(SUM(txn.total_debit), 0) AS total_debit')
            ->selectRaw("
                SUM(
                    CASE WHEN customers.opening_balance_type = 'Dr'
                        THEN  customers.opening_balance
                        ELSE -customers.opening_balance
                    END
                    + COALESCE(txn.net, 0)
                ) AS outstanding
            ")
            ->groupBy('customers.city', 'customers.state')
            ->orderByDesc('outstanding')
            ->get();

        ActivityLogger::log('viewed', 'reports', description: 'Viewed city-wise report');

        return view('reports.city-wise', compact('results'));
    }

    // ── Agent-wise report ─────────────────────────────────────
    public function agentWise(Request $request)
    {
        $this->checkPermission('reports.view');

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $results = DB::table('agents')
            ->join('transactions', 'agents.id', '=', 'transactions.agent_id')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('transactions.transaction_date', [$from, $to])
            ->select('agents.id', 'agents.name')
            ->selectRaw('COUNT(transactions.id) as transaction_count')
            ->selectRaw('SUM(transactions.credit) as total_credit')
            ->selectRaw('SUM(transactions.debit) as total_debit')
            ->groupBy('agents.id', 'agents.name')
            ->orderByDesc('transaction_count')
            ->get();

        ActivityLogger::log('viewed', 'reports', description: "Viewed agent-wise report {$from} to {$to}");

        return view('reports.agent-wise', compact('results', 'from', 'to'));
    }

    // ── Activity Log Viewer ───────────────────────────────────
    public function activityLogs(Request $request)
    {
        $this->checkPermission('logs.view');

        $query = \App\Models\ActivityLog::with('user')->orderByDesc('created_at');

        if ($module = $request->get('module')) {
            $query->where('module', $module);
        }
        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs    = $query->paginate(40)->withQueryString();
        $modules = \App\Models\ActivityLog::distinct()->pluck('module');
        $users   = \App\Models\User::pluck('name', 'id');

        return view('reports.activity-logs', compact('logs', 'modules', 'users'));
    }

    // ── Export Balance Summary as CSV (opens in Excel) ────────
    //
    // No package needed — PHP streams a CSV with UTF-8 BOM so
    // Excel opens it correctly with Indian characters and ₹ amounts.
    // Accepts the same filters as the balance summary page.
    public function exportBalanceSummary(Request $request)
    {
        $this->checkPermission('reports.export');

        // ── Same query as balanceSummary() ─────────────────────
        $cols = [
            'customers.id', 'customers.customer_name', 'customers.phone',
            'customers.mobile', 'customers.email', 'customers.address',
            'customers.city', 'customers.state', 'customers.zip_code',
            'customers.opening_balance', 'customers.opening_balance_type',
            'customers.registered_on', 'customers.is_active',
            'customers.created_by', 'customers.updated_by',
            'customers.created_at', 'customers.updated_at', 'customers.deleted_at',
        ];

        $query = Customer::select($cols)
            ->selectRaw("
                (
                    CASE WHEN customers.opening_balance_type = 'Dr'
                        THEN  customers.opening_balance
                        ELSE -customers.opening_balance
                    END
                )
                + COALESCE(SUM(t.debit),  0)
                - COALESCE(SUM(t.credit), 0) AS net_balance,
                COALESCE(SUM(t.credit), 0)   AS total_credit,
                COALESCE(SUM(t.debit),  0)   AS total_debit
            ")
            ->leftJoin('transactions as t', function ($join) {
                $join->on('customers.id', '=', 't.customer_id')
                     ->whereNull('t.deleted_at');
            })
            ->groupBy($cols);

        // Apply same filters
        $filter = $request->get('filter', 'all');
        if ($filter === 'debit') {
            $query->having('net_balance', '>', 0.009);
        } elseif ($filter === 'credit') {
            $query->having('net_balance', '<', -0.009);
        } elseif ($filter === 'zero') {
            $query->havingRaw('ABS(ROUND(net_balance, 2)) < 0.01');
        }
        if ($city = $request->get('city')) {
            $query->where('customers.city', $city);
        }
        if ($search = $request->get('search')) {
            $query->where('customers.customer_name', 'like', "%{$search}%");
        }

        $query->orderBy('customers.customer_name');
        $customers = $query->get();

        // ── Log the export ──────────────────────────────────────
        ActivityLogger::log(
            'exported', 'reports',
            description: "Exported balance summary CSV ({$customers->count()} customers, filter: {$filter})"
        );

        // ── Build filename ──────────────────────────────────────
        $filename = 'balance-summary-' . now()->format('Y-m-d-His') . '.csv';

        // ── Stream CSV response ─────────────────────────────────
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($customers, $filter) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM — makes Excel open the file correctly
            // without this, ₹ and Indian city names break
            fwrite($handle, "\xEF\xBB\xBF");

            // ── Report header rows ──────────────────────────────
            fputcsv($handle, ['Aman Traders — Balance Summary Report']);
            fputcsv($handle, ['Generated on', now()->format('d M Y, h:i A')]);
            fputcsv($handle, ['Filter', match($filter) {
                'debit'  => 'Dr — To Collect',
                'credit' => 'Cr — To Pay',
                'zero'   => 'Settled (Zero)',
                default  => 'All Customers',
            }]);
            fputcsv($handle, []); // blank row

            // ── Column headers ──────────────────────────────────
            fputcsv($handle, [
                'Sr#',
                'Customer ID',
                'Customer Name',
                'City',
                'State',
                'Mobile',
                'Phone',
                'Opening Balance',
                'Opening Type',
                'Total Credit',
                'Total Debit',
                'Net Balance',
                'Balance Direction',
            ]);

            // ── Data rows ───────────────────────────────────────
            $grandCredit  = 0;
            $grandDebit   = 0;
            $grandNet     = 0;

            foreach ($customers as $i => $c) {
                $bal = $c->net_balance;
                $dir = $bal > 0.01  ? 'Dr - To Collect'
                     : ($bal < -0.01 ? 'Cr - To Pay'
                     : 'Settled');

                fputcsv($handle, [
                    $i + 1,
                    $c->id,
                    $c->customer_name,
                    $c->city ?? '',
                    $c->state ?? '',
                    $c->mobile ?? '',
                    $c->phone ?? '',
                    number_format((float) $c->opening_balance, 2, '.', ''),
                    $c->opening_balance_type,
                    number_format((float) $c->total_credit, 2, '.', ''),
                    number_format((float) $c->total_debit,  2, '.', ''),
                    number_format(abs($bal), 2, '.', ''),
                    $dir,
                ]);

                $grandCredit += $c->total_credit;
                $grandDebit  += $c->total_debit;
                $grandNet    += $bal;
            }

            // ── Totals row ──────────────────────────────────────
            fputcsv($handle, []); // blank row
            fputcsv($handle, [
                '',
                '',
                'TOTAL (' . $customers->count() . ' customers)',
                '', '', '', '', '', '',
                number_format($grandCredit, 2, '.', ''),
                number_format($grandDebit,  2, '.', ''),
                number_format(abs($grandNet), 2, '.', ''),
                $grandNet > 0.01  ? 'Dr - To Collect'
                    : ($grandNet < -0.01 ? 'Cr - To Pay' : 'Settled'),
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function checkPermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->hasPermission($permission)) {
            abort(403, 'Access denied.');
        }
    }
}
