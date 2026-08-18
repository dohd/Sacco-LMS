@extends('layouts.core')

@section('title', 'View | User Profile')
    
@section('content')
    @include('users.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">User Profile Details</h5>
            <div class="card-content p-2">
                <table class="table table-bordered">
                    @php
                        $details = [
                            'Name' => $user_profile->name,
                            'Phone' => $user_profile->phone,
                            'Email' => $user_profile->email,
                        ];
                    @endphp
                    @foreach ($details as $key => $val)
                        <tr>
                            <th width="30%">{{ $key }}</th>
                            <td>{{ $val}}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@stop
