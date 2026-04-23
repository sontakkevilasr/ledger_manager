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
        @php $selectedState = trim(old('state', $customer->state ?? '')) @endphp
        <select name="state" class="form-select @error('state') is-invalid @enderror">
            <option value="">— Select State —</option>
            @foreach ([
                'Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh',
                'Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka',
                'Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram',
                'Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana',
                'Tripura','Uttar Pradesh','Uttarakhand','West Bengal',
                'Andaman and Nicobar Islands','Chandigarh',
                'Dadra and Nagar Haveli and Daman and Diu','Delhi',
                'Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry',
            ] as $state)
                <option value="{{ $state }}" {{ strcasecmp($selectedState, $state) === 0 ? 'selected' : '' }}>
                    {{ $state }}
                </option>
            @endforeach
        </select>
        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- ZIP --}}
    <div class="col-md-4">
        <label class="form-label fw-500">ZIP Code</label>
        <input type="text" name="zip_code" class="form-control"
            value="{{ old('zip_code', $customer->zip_code ?? '') }}"
            placeholder="440001">
    </div>


    {{-- Description / Notes --}}
    <div class="col-12">
        <label class="form-label fw-500">Description / Notes</label>
        <textarea name="description" class="form-control" rows="3"
            placeholder="e.g. Wholesale plywood dealer, Nagpur. Contact during business hours.">{{ old('description', $customer->description ?? '') }}</textarea>
        <div class="form-text">Any notes or remarks about this customer. Shown on the customer list and ledger.</div>
    </div>

    {{-- ── Opening Balance (stored as a transaction) ───────────────────────── --}}
    <div class="col-12">
        <div class="card" style="border:1px solid #e5e7eb;border-radius:10px;">
            <div class="card-body pb-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span class="fw-500" style="font-size:14px;">Opening Balance</span>
                    <span class="badge" style="background:#eff3ff;color:#3b5bdb;font-size:10px;">Stored as ledger entry</span>
                </div>

                <div class="row g-3">

                    {{-- Amount + Dr/Cr --}}
                    <div class="col-md-5">
                        <label class="form-label fw-500" style="font-size:13px;">Amount</label>
                        <div class="d-flex gap-2">
                            <div class="btn-group" role="group" style="flex-shrink:0;">
                                <input type="radio" class="btn-check" name="ob_type" id="ob-dr" value="Dr"
                                    {{ old('ob_type', isset($openingTransaction) ? ($openingTransaction->type === 'Debit' ? 'Dr' : 'Cr') : 'Dr') === 'Dr' ? 'checked' : '' }}>
                                <label class="btn btn-outline-danger btn-sm" for="ob-dr">Dr</label>

                                <input type="radio" class="btn-check" name="ob_type" id="ob-cr" value="Cr"
                                    {{ old('ob_type', isset($openingTransaction) ? ($openingTransaction->type === 'Debit' ? 'Dr' : 'Cr') : 'Dr') === 'Cr' ? 'checked' : '' }}>
                                <label class="btn btn-outline-success btn-sm" for="ob-cr">Cr</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="ob_amount" id="ob_amount"
                                    class="form-control @error('ob_amount') is-invalid @enderror"
                                    step="0.01" min="0"
                                    value="{{ old('ob_amount', isset($openingTransaction) ? ($openingTransaction->debit > 0 ? $openingTransaction->debit : $openingTransaction->credit) : '') }}"
                                    placeholder="0">
                            </div>
                        </div>
                        <div id="ob-help" class="form-text mt-1" style="font-size:11px;"></div>
                    </div>

                    {{-- Date --}}
                    <div class="col-md-3">
                        <label class="form-label fw-500" style="font-size:13px;">Date <span class="text-danger">*</span></label>
                        <input type="date" name="ob_date" class="form-control"
                            value="{{ old('ob_date', isset($openingTransaction) ? $openingTransaction->transaction_date->format('Y-m-d') : now()->startOfYear()->format('Y-m-d')) }}">
                        <div class="form-text" style="font-size:11px;">Usually financial year start</div>
                    </div>

                    {{-- Description --}}
                    <div class="col-md-4">
                        <label class="form-label fw-500" style="font-size:13px;">Description</label>
                        <input type="text" name="ob_description" class="form-control"
                            value="{{ old('ob_description', isset($openingTransaction) ? $openingTransaction->description : 'Opening Balance') }}"
                            placeholder="Opening Balance">
                    </div>

                </div>

                <div class="mt-2" style="font-size:11px;color:#9ca3af;">
                    <i class="bi bi-info-circle me-1"></i>
                    Leave amount as 0 if this is a new customer with no previous balance.
                    This entry will appear in the customer ledger with a special opening balance marker.
                </div>
            </div>
        </div>
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
    const amtInput = document.getElementById('ob_amount');

    function updateHelp() {
        const amt  = parseFloat(amtInput.value) || 0;
        const isDr = drRadio.checked;

        if (amt === 0) {
            helpText.innerHTML = '';
            return;
        }

        const divisor = {{ scale_divisor() }};
        const scaled  = amt / divisor;
        const fmtAmt  = '₹' + scaled.toLocaleString('en-IN', { minimumFractionDigits: 2 });

        helpText.innerHTML = isDr
            ? `<span style="color:#dc2626;"><strong>Dr ${fmtAmt}</strong> — Customer owes Aman Traders</span>`
            : `<span style="color:#059669;"><strong>Cr ${fmtAmt}</strong> — Aman Traders owes customer</span>`;
    }

    if (drRadio) drRadio.addEventListener('change', updateHelp);
    if (crRadio) crRadio.addEventListener('change', updateHelp);
    if (amtInput) amtInput.addEventListener('input', updateHelp);

    // Run on page load
    updateHelp();
})();
</script>
@endpush
