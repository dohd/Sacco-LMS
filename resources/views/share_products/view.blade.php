@extends('layouts.core')
@section('title', 'View | Share Products')

@section('content')
    @include('share_products.partial.header')    
    @php
        $currency  = config('app.currency', 'KES');
        $money     = fn ($v) => $currency . ' ' . number_format((float) $v, 2);
        $minInvest = $shareProduct->unit_value * $shareProduct->minimum_units;
        $maxInvest = $shareProduct->maximum_units ? $shareProduct->unit_value * $shareProduct->maximum_units : null;

        $features = [
            ['key' => 'dividend_eligible', 'icon' => 'bi-cash-coin',              'label' => 'Dividend Eligible', 'on' => 'Shares earn dividends at declaration.',         'off' => 'Shares do not participate in dividends.'],
            ['key' => 'allows_transfer',   'icon' => 'bi-arrow-left-right',       'label' => 'Transferable',      'on' => 'Members may transfer shares to other members.', 'off' => 'Shares cannot be transferred.'],
            ['key' => 'allows_redemption', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Redeemable',        'on' => 'Shares may be refunded / redeemed.',            'off' => 'Shares cannot be redeemed.'],
            ['key' => 'can_secure_loan',   'icon' => 'bi-shield-check',           'label' => 'Loan Security',     'on' => 'Shares can be pledged as loan collateral.',     'off' => 'Shares cannot secure loans.'],
        ];
    @endphp


    <div class="container-fluid py-4">
        {{-- ===================== HEADER ===================== --}}
        <div class="card border-0  shadow-sm rounded-3 mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 fs-3 lh-1">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h1 class="h3 fw-bold mb-0">{{ $shareProduct->name }}</h1>
                                @if ($shareProduct->is_active)
                                    <span class="badge rounded-pill bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                @else
                                    <span class="badge rounded-pill bg-secondary"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm font-monospace rounded-pill py-0 js-copy"
                                    data-copy="{{ $shareProduct->code }}"
                                    data-bs-toggle="tooltip" title="Click to copy">
                                <i class="bi bi-upc me-1"></i>{{ $shareProduct->code }}
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm js-toggle_status"
                                data-action="{{ route('share_products.toggle_status', $shareProduct) }}"
                                data-label="{{ $shareProduct->is_active ? 'deactivate' : 'activate' }}">
                            <i class="bi {{ $shareProduct->is_active ? 'bi-toggle-off' : 'bi-toggle-on' }} me-2"></i>
                            {{ $shareProduct->is_active ? 'Deactivate' : 'Activate' }}
                        </button>                        
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== SUMMARY CARDS ===================== --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase mb-1"><i class="bi bi-coin me-1"></i>Unit Value</div>
                        <div class="fs-4 fw-bold">{{ $money($shareProduct->unit_value) }}</div>
                        <small class="text-muted">Nominal value per share</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase mb-1"><i class="bi bi-arrow-down-circle me-1"></i>Minimum Units</div>
                        <div class="fs-4 fw-bold">{{ number_format($shareProduct->minimum_units) }}</div>
                        <small class="text-muted">= {{ $money($minInvest) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase mb-1"><i class="bi bi-arrow-up-circle me-1"></i>Maximum Units</div>
                        <div class="fs-4 fw-bold">{{ $shareProduct->maximum_units ? number_format($shareProduct->maximum_units) : '∞' }}</div>
                        <small class="text-muted">{{ $maxInvest ? '= ' . $money($maxInvest) : 'No upper limit' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase mb-1"><i class="bi bi-ui-checks me-1"></i>Features Enabled</div>
                        <div class="fs-4 fw-bold">
                            {{ collect($features)->filter(fn ($f) => $shareProduct->{$f['key']})->count() }}
                            <span class="fs-6 text-muted fw-normal">/ {{ count($features) }}</span>
                        </div>
                        <small class="text-muted">Dividends, transfer, redemption, loans</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== SECTIONS ===================== --}}
        <div class="row g-4">

            {{-- General Information --}}
            <div class="col-12">
                <section class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-2 px-2 py-1"><i class="bi bi-info-circle"></i></span>
                        <h6 class="mb-0 fw-semibold">General Information</h6>
                    </div>
                    <div class="card-body p-4">
                        <dl class="row mb-0">
                            <div class="col-md-4">
                                <dt class="small text-muted fw-normal">Product Code</dt>
                                <dd class="font-monospace fw-semibold">{{ $shareProduct->code }}</dd>
                            </div>
                            <div class="col-md-5">
                                <dt class="small text-muted fw-normal">Product Name</dt>
                                <dd class="fw-semibold">{{ $shareProduct->name }}</dd>
                            </div>
                            <div class="col-md-3">
                                <dt class="small text-muted fw-normal">Status</dt>
                                <dd>
                                    <span class="badge {{ $shareProduct->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $shareProduct->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </dd>
                            </div>
                            <div class="col-12">
                                <dt class="small text-muted fw-normal">Description</dt>
                                <dd class="mb-0 text-muted">
                                    {!! $shareProduct->description ? nl2br(e($shareProduct->description)) : 'No description provided.' !!}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>

            {{-- Units & Valuation --}}
            <div class="col-12">
                <section class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-2 px-2 py-1"><i class="bi bi-calculator"></i></span>
                        <h6 class="mb-0 fw-semibold">Units &amp; Valuation</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 align-items-center">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between px-0">
                                        <span class="text-muted">Unit Value</span>
                                        <span class="fw-semibold">{{ $money($shareProduct->unit_value) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0">
                                        <span class="text-muted">Minimum Units</span>
                                        <span class="fw-semibold">{{ number_format($shareProduct->minimum_units) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0">
                                        <span class="text-muted">Maximum Units</span>
                                        <span class="fw-semibold">{{ $shareProduct->maximum_units ? number_format($shareProduct->maximum_units) : 'Unlimited' }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0">
                                        <span class="text-muted">Min. Investment</span>
                                        <span class="fw-bold">{{ $money($minInvest) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between px-0">
                                        <span class="text-muted">Max. Investment</span>
                                        <span class="fw-bold">{{ $maxInvest ? $money($maxInvest) : 'Unlimited' }}</span>
                                    </li>
                                </ul>
                            </div>

                            {{-- Quick calculator --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-light border">
                                    <label for="unitsCalc" class="form-label small fw-semibold text-muted mb-1">
                                        <i class="bi bi-lightning-charge me-1"></i>Quick Share Calculator
                                    </label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="unitsCalc" class="form-control"
                                               min="{{ $shareProduct->minimum_units }}"
                                               @if ($shareProduct->maximum_units) max="{{ $shareProduct->maximum_units }}" @endif
                                               value="{{ $shareProduct->minimum_units }}"
                                               data-unit-value="{{ $shareProduct->unit_value }}"
                                               data-min="{{ $shareProduct->minimum_units }}"
                                               data-max="{{ $shareProduct->maximum_units }}"
                                               data-currency="{{ $currency }}">
                                        <span class="input-group-text">units</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-baseline">
                                        <span class="small text-muted">Total value</span>
                                        <span id="calcTotal" class="fs-5 fw-bold">{{ $money($minInvest) }}</span>
                                    </div>
                                    <div id="calcHint" class="small mt-1"></div>

                                    @if ($shareProduct->maximum_units)
                                        <div class="progress mt-2" role="progressbar" aria-label="Units within allowed range">
                                            <div class="progress-bar" id="calcBar"></div>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mt-1">
                                            <span>{{ number_format($shareProduct->minimum_units) }}</span>
                                            <span>{{ number_format($shareProduct->maximum_units) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Features & Rules --}}
            <div class="col-12">
                <section class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-2 px-2 py-1"><i class="bi bi-sliders"></i></span>
                        <h6 class="mb-0 fw-semibold">Features &amp; Rules</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @foreach ($features as $f)
                                @php $on = (bool) $shareProduct->{$f['key']}; @endphp
                                <div class="col-md-6">
                                    <div class="d-flex gap-3 align-items-start border rounded-3 p-3 h-100">
                                        <span class="fs-5 rounded-3 px-2 py-1 bg-opacity-10 {{ $on ? 'bg-success text-success' : 'bg-secondary text-secondary' }}">
                                            <i class="bi {{ $f['icon'] }}"></i>
                                        </span>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold">{{ $f['label'] }}</span>
                                                <span class="badge rounded-pill {{ $on ? 'bg-success' : 'bg-secondary' }}">{{ $on ? 'Yes' : 'No' }}</span>
                                            </div>
                                            <small class="text-muted">{{ $on ? $f['on'] : $f['off'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            {{-- GL Mapping --}}
            <div class="col-12">
                <section class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-2 px-2 py-1"><i class="bi bi-journal-bookmark"></i></span>
                        <h6 class="mb-0 fw-semibold">General Ledger Mapping</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @foreach ([
                                ['title' => 'Share Capital Account', 'icon' => 'bi-bank',           'acc' => $shareProduct->shareCapitalAccount, 'id' => $shareProduct->share_capital_account_id, 'hint' => 'Credited with nominal value of shares issued.'],
                                ['title' => 'Share Premium Account', 'icon' => 'bi-graph-up-arrow', 'acc' => $shareProduct->sharePremiumAccount, 'id' => $shareProduct->share_premium_account_id, 'hint' => 'Credited with amounts paid above nominal value.'],
                            ] as $gl)
                                <div class="col-md-6">
                                    <div class="border rounded-3 bg-light p-3 h-100">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi {{ $gl['icon'] }} text-primary"></i>
                                            <span class="small text-muted text-uppercase fw-semibold">{{ $gl['title'] }}</span>
                                        </div>
                                        @if ($gl['acc'])
                                            <div class="fw-semibold">{{ $gl['acc']->name }}</div>
                                            <div class="font-monospace small text-muted">{{ $gl['acc']->code ?? '#' . $gl['id'] }}</div>
                                        @else
                                            <div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Account #{{ $gl['id'] }} not found</div>
                                        @endif
                                        <hr class="my-2">
                                        <small class="text-muted">{{ $gl['hint'] }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            {{-- Audit Trail --}}
            <div class="col-12">
                <section class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-2 px-2 py-1"><i class="bi bi-clock-history"></i></span>
                        <h6 class="mb-0 fw-semibold">Audit Trail</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach (['Created' => $shareProduct->created_at, 'Last Updated' => $shareProduct->updated_at] as $label => $date)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                <span><i class="bi bi-dot text-primary"></i>{{ $label }}</span>
                                <span class="text-end">
                                    <span class="fw-semibold">{{ $date ? $date->format('d M Y, h:i A') : '—' }}</span>
                                    @if ($date)
                                        <br><small class="text-muted">{{ $date->diffForHumans() }}</small>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

        </div>
    </div>

    {{-- ===================== DELETE MODAL ===================== --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('share_products.destroy', $shareProduct) }}" class="modal-content border-0 shadow" id="deleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Delete Share Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">This will permanently delete <strong>{{ $shareProduct->name }}</strong>. This action cannot be undone.</p>
                    <label for="confirmCode" class="form-label small text-muted">
                        Type <code>{{ $shareProduct->code }}</code> to confirm
                    </label>
                    <input type="text" id="confirmCode" class="form-control" autocomplete="off" data-expected="{{ $shareProduct->code }}">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hidden form for status toggle --}}
    <form id="toggleStatusForm" method="POST" class="d-none">
        @csrf
    </form>

    {{-- Toast --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="spToast" class="toast align-items-center bg-dark text-white border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(function () {
    // Tooltips
    $('[data-bs-toggle="tooltip"]').each(function () { new bootstrap.Tooltip(this); });

    // Toast helper
    const toast = (msg) => {
        const $t = $('#spToast');
        $t.find('.toast-body').text(msg);
        bootstrap.Toast.getOrCreateInstance($t[0], { delay: 2000 }).show();
    };

    // Copy product code
    $('.js-copy').on('click', function () {
        const text = $(this).data('copy');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => toast('Code "' + text + '" copied'));
        }
    });

    // Print
    $('.js-print').on('click', () => window.print());

    // Status toggle
    $('.js-toggle_status').on('click', function () {
        if (confirm('Are you sure you want to ' + $(this).data('label') + ' this share product?')) {
            $('#toggleStatusForm').attr('action', $(this).data('action')).trigger('submit');
        }
    });

    // Delete confirmation: require typing the product code
    const $confirm = $('#confirmCode');
    $confirm.on('input', function () {
        $('#confirmDeleteBtn').prop('disabled', $.trim($(this).val()) !== String($(this).data('expected')));
    });
    $('#deleteModal').on('hidden.bs.modal', function () {
        $confirm.val('');
        $('#confirmDeleteBtn').prop('disabled', true);
    }).on('shown.bs.modal', () => $confirm.trigger('focus'));
    $('#deleteForm').on('submit', function () {
        $('#confirmDeleteBtn').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Deleting...');
    });

    // Quick share calculator
    const $calc = $('#unitsCalc');
    const fmt = (n) => $calc.data('currency') + ' ' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    $calc.on('input', function () {
        const units = parseInt($(this).val(), 10) || 0;
        const unitValue = parseFloat($(this).data('unit-value'));
        const min = parseInt($(this).data('min'), 10);
        const max = parseInt($(this).data('max'), 10) || null;
        const $hint = $('#calcHint').removeClass('text-danger text-success');
        const $bar = $('#calcBar').removeClass('bg-danger');

        $('#calcTotal').text(fmt(units * unitValue));

        if (units < min) {
            $hint.addClass('text-danger').html('<i class="bi bi-x-circle me-1"></i>Below minimum of ' + min.toLocaleString() + ' units');
            $bar.addClass('bg-danger');
        } else if (max && units > max) {
            $hint.addClass('text-danger').html('<i class="bi bi-x-circle me-1"></i>Exceeds maximum of ' + max.toLocaleString() + ' units');
            $bar.addClass('bg-danger');
        } else {
            $hint.addClass('text-success').html('<i class="bi bi-check-circle me-1"></i>Within allowed range');
        }

        if (max) {
            const pct = Math.max(0, Math.min(100, ((units - min) / Math.max(1, max - min)) * 100));
            $bar.css('width', pct + '%');
        }
    }).trigger('input');
});
</script>
@endsection
