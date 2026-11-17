@extends('layouts.core')

@section('title', $title)

@section('content')
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1>{{ $title }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="breadcrumb-item">Reports</li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>

    <section class="section">
        <div class="card report-filters">
            <div class="card-body pt-3">
                <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
                    @foreach ($filters as $filter)
                        <div class="col-md-3">
                            <label class="form-label" for="{{ $filter['name'] }}">{{ $filter['label'] }}</label>

                            @if ($filter['type'] === 'select')
                                @if (!empty($filter['locked']))
                                    <select class="form-select" id="{{ $filter['name'] }}" disabled>
                                @else
                                    <select class="form-select" id="{{ $filter['name'] }}" name="{{ $filter['name'] }}">
                                @endif
                                    @foreach ($filter['options'] as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            @if ((string) $filter['value'] === (string) $value) selected @endif
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @if (!empty($filter['locked']))
                                    <input type="hidden" name="{{ $filter['name'] }}" value="{{ $filter['value'] }}">
                                @endif
                            @else
                                <input
                                    class="form-control"
                                    type="{{ $filter['type'] }}"
                                    id="{{ $filter['name'] }}"
                                    name="{{ $filter['name'] }}"
                                    value="{{ $filter['value'] }}"
                                >
                            @endif
                        </div>
                    @endforeach

                    <div class="col-md-auto">
                        <button class="btn btn-primary" type="submit">Generate report</button>
                    </div>
                </form>
            </div>
        </div>

        @if (count($summary))
            <div class="row g-3 mb-3">
                @foreach ($summary as $item)
                    <div class="col-md-4">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="text-muted small">{{ $item['label'] }}</div>
                                <div class="fs-5 fw-bold">
                                    {{ isset($item['suffix'])
                                        ? number_format((float) $item['value'], 2) . $item['suffix']
                                        : (is_numeric($item['value']) && !is_int($item['value'])
                                            ? number_format((float) $item['value'], 2)
                                            : number_format((float) $item['value'], 0)) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($groups))
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Aging totals</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Bucket</th>
                                    <th>Loans</th>
                                    <th>Loan exposure</th>
                                    <th>Overdue installments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $bucket => $totals)
                                    <tr>
                                        <td>{{ $bucket }}</td>
                                        <td>{{ $totals['count'] }}</td>
                                        <td>{{ number_format($totals['balance'], 2) }}</td>
                                        <td>{{ number_format($totals['overdue'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <p class="text-muted mt-3">{{ $description }}</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr>
                                @foreach ($columns as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    @foreach ($columns as $key => $label)
                                        @php($value = $row[$key] ?? '—')
                                        <td>
                                            {{ is_numeric($value) && in_array($key, [
                                                'debit', 'credit', 'amount', 'balance', 'scheduled', 'actual',
                                                'variance', 'outstanding', 'overdue', 'exposure', 'gross',
                                                'deductions', 'net',
                                            ], true)
                                                ? number_format((float) $value, 2)
                                                : $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">
                                        No records match this report.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <style>
        @media print {
            .sidebar,
            .header,
            .footer,
            .report-filters,
            .pagetitle button,
            .back-to-top {
                display: none !important;
            }

            #main {
                margin: 0 !important;
                padding: 0 !important;
            }

            .card {
                border: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
@endsection
