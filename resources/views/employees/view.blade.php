@extends('layouts.core')

@section('title', 'Employee Management')
    
@section('content')
    @include('employees.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">View Employee</h5>
            <div class="card-content p-2">
                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item">
                    <a href="#" class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" aria-controls="info">Summary Info</a>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link" id="doc-tab" data-bs-toggle="tab" data-bs-target="#document" aria-controls="document">Attached Documents</a>
                  </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <!-- Employee Summary -->
                    <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                        <div class="m-2">
                            @foreach ($employeeCols as $row)
                                <div class="form-group row mb-2">
                                    @foreach ($row as $cell)
                                        <div class="col-md-3">
                                            @if ($cell == 'payroll_no')
                                                <label for="{{ $cell }}">PF No.</label>
                                                {{ Form::text($cell, $employee->$cell, ['class' => 'form-control', 'disabled' => 'disabled']) }}
                                            @else
                                                <label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
                                                {{ Form::text($cell, $employee->$cell, ['class' => 'form-control', 'disabled' => 'disabled']) }}
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Employee Documents -->
                    <div class="tab-pane fade" id="document" role="tabpanel" aria-labelledby="document-tab">
                        <div class="row m-2">
                            <div class="col-md-10">
                                <span class="badge bg-primary float-end mb-2 mt-2" role="button" data-bs-toggle="modal" data-bs-target="#documentModal">
                                    <i class="bi bi-plus-circle"></i> Attach
                                </span>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                          <th>#</th>
                                          <th>Document Type</th>
                                          <th>Document</th>
                                          <th>Caption</th>
                                          <th><i class="bi bi-gear"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employee->documents as $i => $doc)
                                            <tr>
                                                <td>{{ $i+1 }}</td>
                                                <td>{{ $doc->doc_type }}</td>
                                                <td><a target="_blank" href="{{ route('employees.document_download', $doc) }}" class="ms-1 dn-link"><u>{{ $doc->origin_name }}</u></a></td>
                                                <td>{{ $doc->caption }}</td>
                                                <td></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('employees.document_modal')
@stop

@section('script')
<script>
    
</script>    
@stop
