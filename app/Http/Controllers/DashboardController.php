<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Summary Cards ──────────────────────────────────────────────────

        $totalCustomers = Customer::where('is_active', true)->count();

        // ── Use the SAME formula as Balance Summary report ─────────────────
        // Scope: ALL customers (active + inactive) to match the report exactly.
        // Transactions of inactive customers still exist and must be included.
        //
        // net_outstanding = SUM(opening_balance_signed) + SUM(debit) - SUM(credit)
        // Positive = Dr (to collect), Negative = Cr (to pay)
        $grandTotals = DB::table('customers')
            ->selectRaw("
                SUM(
                    CASE WHEN opening_balance_type = 'Dr'
                        THEN  opening_balance
                        ELSE -opening_balance
                    END
                ) AS opening_signed,
                COALESCE(SUM(t.credit), 0) AS grand_credit,
                COALESCE(SUM(t.debit),  0) AS grand_debit
            ")
            ->leftJoin('transactions as t', function ($join) {
                $join->on('customers.id', '=', 't.customer_id')
                     ->whereNull('t.deleted_at');
            })
            ->first();

        $totalCredit      = $grandTotals->grand_credit ?? 0;
        $totalDebit       = $grandTotals->grand_debit  ?? 0;
        $totalOutstanding = ($grandTotals->opening_signed ?? 0) + $totalDebit - $totalCredit;

        // ── Top 10 customers with highest outstanding (Dr balance) ─────────
        //
        // BUG 2 FIX: SQL must use CASE WHEN for opening_balance_type.
        // Old code: opening_balance + SUM(debit-credit)  ← always added, wrong
        // New code: signed opening + SUM(debit-credit)   ← respects Dr/Cr
        $topDebtors = Customer::active()
            ->select('customers.*')
            ->selectRaw("
                (
                    CASE WHEN opening_balance_type = 'Dr'
                        THEN  opening_balance
                        ELSE -opening_balance
                    END
                    +
                    COALESCE((
                        SELECT SUM(debit) - SUM(credit)
                        FROM transactions
                        WHERE transactions.customer_id = customers.id
                          AND transactions.deleted_at IS NULL
                    ), 0)
                ) as outstanding
            ")
            ->having('outstanding', '>', 0)   // only Dr balances (we should collect)
            ->orderByDesc('outstanding')
            ->limit(10)
            ->get();

        // ── Monthly credit vs debit (last 12 months) ──────────────────────
        $monthly = Transaction::selectRaw("
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                SUM(credit) as total_credit,
                SUM(debit)  as total_debit
            ")
            ->whereNull('deleted_at')
            ->where('transaction_date', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── City-wise outstanding ──────────────────────────────────────────
        //
        // BUG 4 FIX: Include each customer's signed opening balance
        // in the city outstanding total.
        //
        // We calculate per-customer outstanding first, then group by city.
        $cityWise = DB::table('customers')
            ->where('customers.is_active', true)
            ->selectRaw("
                customers.city,
                SUM(
                    (
                        CASE WHEN customers.opening_balance_type = 'Dr'
                            THEN  customers.opening_balance
                            ELSE -customers.opening_balance
                        END
                    )
                    +
                    COALESCE((
                        SELECT SUM(t.debit) - SUM(t.credit)
                        FROM transactions t
                        WHERE t.customer_id = customers.id
                          AND t.deleted_at IS NULL
                    ), 0)
                ) as outstanding
            ")
            ->groupBy('customers.city')
            ->having('outstanding', '>', 0)
            ->orderByDesc('outstanding')
            ->limit(8)
            ->get();

        // ── Recent transactions (last 10) ──────────────────────────────────
        $recentTransactions = Transaction::with(['customer', 'createdBy'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // ── Recent activity logs (last 10) ─────────────────────────────────
        $recentActivity = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ── This month stats ───────────────────────────────────────────────
        $thisMonth = Transaction::whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(credit) as credit, SUM(debit) as debit, COUNT(*) as count')
            ->first();

        return view('dashboard', compact(
            'totalCustomers',
            'totalCredit',
            'totalDebit',
            'totalOutstanding',
            'topDebtors',
            'monthly',
            'cityWise',
            'recentTransactions',
            'recentActivity',
            'thisMonth',
        ));
    }
}
