<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    // ── List ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->checkPermission();

        $query = Agent::withCount('transactions')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }

        if ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->get('status') !== 'all') {
            $query->where('is_active', true);
        }

        $agents = $query->paginate(20)->withQueryString();

        return view('masters.agents.index', compact('agents'));
    }

    // ── Create form ───────────────────────────────────────────
    public function create()
    {
        $this->checkPermission();
        return view('masters.agents.create');
    }

    // ── Store ─────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->checkPermission();

        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:agents,name',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'An agent with this name already exists.',
        ]);

        $data['name']      = strtoupper(trim($data['name']));
        $data['is_active'] = $request->boolean('is_active', true);

        Agent::create($data);

        return redirect()
            ->route('agents.index')
            ->with('success', "Agent [{$data['name']}] added successfully.");
    }

    // ── Edit form ─────────────────────────────────────────────
    public function edit(Agent $agent)
    {
        $this->checkPermission();
        return view('masters.agents.edit', compact('agent'));
    }

    // ── Update ────────────────────────────────────────────────
    public function update(Request $request, Agent $agent)
    {
        $this->checkPermission();

        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:agents,name,' . $agent->id,
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'An agent with this name already exists.',
        ]);

        $data['name']      = strtoupper(trim($data['name']));
        $data['is_active'] = $request->boolean('is_active');

        $agent->update($data);

        return redirect()
            ->route('agents.index')
            ->with('success', "Agent [{$agent->name}] updated successfully.");
    }

    // ── Toggle active/inactive ────────────────────────────────
    public function toggleStatus(Agent $agent)
    {
        $this->checkPermission();

        $agent->update(['is_active' => ! $agent->is_active]);

        $status = $agent->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Agent [{$agent->name}] {$status}.");
    }

    // ── Delete ────────────────────────────────────────────────
    public function destroy(Agent $agent)
    {
        $this->checkPermission();

        if ($agent->transactions()->count() > 0) {
            return back()->with('error',
                "Cannot delete [{$agent->name}] — they have {$agent->transactions()->count()} transactions linked. Deactivate instead."
            );
        }

        $name = $agent->name;
        $agent->delete();

        return redirect()
            ->route('agents.index')
            ->with('success', "Agent [{$name}] deleted.");
    }

    // ── Permission helper ─────────────────────────────────────
    // Managers and Super Admins can manage masters
    private function checkPermission(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->hasPermission('masters.manage')) {
            abort(403, 'You do not have permission to manage agents.');
        }
    }
}
