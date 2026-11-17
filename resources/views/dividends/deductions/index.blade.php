@extends('layouts.core')

@section('title', 'Dividend Deductions')

@section('content')
    @include('dividends.deductions.header')

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-borderless datatable">
                <thead>
                    <tr>
                        <th>MEMBER</th>
                        <th>TYPE</th>
                        <th>AMOUNT</th>
                        <th>DESCRIPTION</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deductions as $row)
                        <tr>
                            <td>{{ $row->allocation->member->full_name }}</td>
                            <td>{{ $row->deduction_type }}</td>
                            <td>{{ $row->amount }}</td>
                            <td>{{ $row->description }}</td>
                            <td>
                                <a href="{{ route('dividend_deductions.edit', $row) }}">Edit</a>
                                <form method="POST" action="{{ route('dividend_deductions.destroy', $row) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-link text-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
