<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    // ── Show settings page ────────────────────────────────────
    public function index()
    {
        // Super admin only
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can manage settings.');
        }

        // Load all settings as key → value map
        $settings = DB::table('settings')
            ->orderBy('id')
            ->get()
            ->keyBy('key');

        return view('settings', compact('settings'));
    }

    // ── Save settings ─────────────────────────────────────────
    public function update(Request $request)
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can manage settings.');
        }

        $request->validate([
            'company_name'         => 'nullable|string|max:150',
            'financial_year_start' => 'nullable|string|max:10',
            'scale_amounts'        => 'nullable',
        ]);

        $userId = Auth::id();
        $now    = now();

        // Save scale_amounts (checkbox — absent = 0, present = 1)
        $this->saveSetting('scale_amounts',        $request->has('scale_amounts')        ? '1' : '0', $userId, $now);
        $this->saveSetting('allow_customer_purge', $request->has('allow_customer_purge') ? '1' : '0', $userId, $now);

        // Save text settings
        if ($request->filled('company_name')) {
            $this->saveSetting('company_name', $request->company_name, $userId, $now);
        }
        if ($request->filled('financial_year_start')) {
            $this->saveSetting('financial_year_start', $request->financial_year_start, $userId, $now);
        }

        // Clear the settings cache so next request picks up new values immediately
        cache()->forget('setting.scale_amounts');
        cache()->forget('setting.allow_customer_purge');

        // Log the change
        \App\Services\ActivityLogger::log(
            'updated', 'settings',
            description: 'Updated system settings'
        );

        return back()->with('success', 'Settings saved successfully.');
    }

    private function saveSetting(string $key, string $value, int $userId, $now): void
    {
        DB::table('settings')
            ->where('key', $key)
            ->update([
                'value'      => $value,
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
    }
}
