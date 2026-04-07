<div class="row g-3">

    {{-- Customer Name --}}
    <div class="col-md-8">
        <label class="form-label fw-500">Customer Name <span class="text-danger">*</span></label>
        <input type="text" name="customer_name"
            class="form-control @error('customer_name') is-invalid @enderror"
            value="{{ old('customer_name', $customer->customer_name ?? '') }}"
            placeholder="e.g. TIP TOP FURNITURE" required>
        @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Registration Date --}}
    <div class="col-md-4">
        <label class="form-label fw-500">Registration Date</label>
        <input type="date" name="registered_on" class="form-control"
            value="{{ old('registered_on', isset($customer) ? $customer->registered_on?->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>

    {{-- Mobile --}}
    <div class="col-md-4">
        <label class="form-label fw-500">Mobile</label>
        <input type="text" name="mobile" class="form-control"
            value="{{ old('mobile', $customer->mobile ?? '') }}"
            placeholder="10-digit number">
    </div>

    {{-- Phone --}}
    <div class="col-md-4">
        <label class="form-label fw-500">Phone</label>
        <input type="text" name="phone" class="form-control"
            value="{{ old('phone', $customer->phone ?? '') }}">
    </div>

    {{-- Email --}}
    <div class="col-md-4">
        <label class="form-label fw-500">Email</label>
        <input type="email" name="email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $customer->email ?? '') }}"
            placeholder="optional">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Address --}}
    <div class="col-12">
        <label class="form-label fw-500">Address</label>
        <textarea name="address" class="form-control" rows="2"
            placeholder="Full address">{{ old('address', $customer->address ?? '') }}</textarea>
    </div>

    {{-- City --}}
    <div class="col-md-4">
        <label class="form-label fw-500">City</label>
        <input type="text" name="city" class="form-control"
            value="{{ old('city', $customer->city ?? '') }}"
            placeholder="e.g. NAGPUR">
    </div>

    {{-- State --}}
    <div class="col-md-4">
        <label class="form-label fw-500">State</label>
        <input type="text" name="state" class="form-control"
            value="{{ old('state', $customer->state ?? '') }}"
            placeholder="e.g. Maharashtra">
    </div>

    {{-- ZIP --}}
    <div class="col-md-4">
        <label class="form-label fw-500">ZIP Code</label>
        <input type="text" name="zip_code" class="form-control"
            value="{{ old('zip_code', $customer->zip_code ?? '') }}"
            placeholder="440001">
    </div>

    {{-- ── Opening Balance with Dr / Cr ──────────────────────────────────── --}}
    <div class="col-12">
        <label class="form-label fw-500">Opening Balance</label>

        <div class="d-flex gap-2 align-items-stretch">

            {{-- Dr / Cr toggle --}}
            <div class="btn-group" role="group" style="flex-shrink:0;">
                <input type="radio" class="btn-check" name="opening_balance_type"
                    id="ob-dr" value="Dr"
                    {{ old('opening_balance_type', $customer->opening_balance_type ?? 'Dr') === 'Dr' ? 'checked' : '' }}>
                <label class="btn btn-outline-danger" for="ob-dr" title="Dr — Customer owes Aman Traders">
                    Dr
                </label>

                <input type="radio" class="btn-check" name="opening_balance_type"
                    id="ob-cr" value="Cr"
                    {{ old('opening_balance_type', $customer->opening_balance_type ?? 'Dr') === 'Cr' ? 'checked' : '' }}>
                <label class="btn btn-outline-success" for="ob-cr" title="Cr — Aman Traders owes customer">
                    Cr
                </label>
            </div>

            {{-- Amount input --}}
            <div class="input-group flex-grow-1">
                <span class="input-group-text">₹</span>
                <input type="number" name="opening_balance"
                    id="opening_balance"
                    class="form-control @error('opening_balance') is-invalid @enderror"
                    step="0.01" min="0"
                    value="{{ old('opening_balance', $customer->opening_balance ?? 0) }}"
                    placeholder="0.00">
                @error('opening_balance')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Dynamic helper text --}}
        <div id="ob-help" class="form-text mt-2"></div>
    </div>

    {{-- Active Status --}}
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox"
                name="is_active" id="is_active" value="1"
                {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active Customer</label>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const drRadio  = document.getElementById('ob-dr');
    const crRadio  = document.getElementById('ob-cr');
    const helpText = document.getElementById('ob-help');
    const amtInput = document.getElementById('opening_balance');

    function updateHelp() {
        const amt    = parseFloat(amtInput.value) || 0;
        const isDr   = drRadio.checked;
        const fmtAmt = '₹' + amt.toLocaleString('en-IN', { minimumFractionDigits: 2 });

        if (amt === 0) {
            helpText.innerHTML = '<span class="text-muted">Enter 0 for new customers with no previous balance.</span>';
            return;
        }

        if (isDr) {
            helpText.innerHTML =
                `<span style="color:#dc2626;">
                    <strong>Dr ${fmtAmt}</strong> — Customer owes Aman Traders this amount from previous period.
                    It will be added to their outstanding balance.
                </span>`;
        } else {
            helpText.innerHTML =
                `<span style="color:#059669;">
                    <strong>Cr ${fmtAmt}</strong> — Aman Traders owes this amount to the customer from previous period.
                    It will be deducted from their outstanding balance.
                </span>`;
        }
    }

    drRadio.addEventListener('change', updateHelp);
    crRadio.addEventListener('change', updateHelp);
    amtInput.addEventListener('input', updateHelp);

    // Run on page load
    updateHelp();
})();
</script>
@endpush
