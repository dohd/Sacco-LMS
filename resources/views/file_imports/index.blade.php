@extends('layouts.core')

@section('title', 'Data Import Management')

@section('content')
    @include('file_imports.header')
    <div class="card">
        <div class="card-body">
            <div class="card-content">
                {{ Form::open(['route' => 'file_imports.store', 'method' => 'POST', 'files' => true, 'class' => 'form']) }}
                    <div class="row mb-2 p-2">
                        <div class="col-md-12 bg-light pt-3 mb-2">
                            <p>
                                <span>Data format should be as per downloaded template.</span>
                                <a target="_blank" href="{{ route('file_imports.download_template', 'employees') }}" class="ms-1 dn-link"><u><b>Click to download template</b></u></a>
                            </p>
                        </div>
                        <hr style="border: none; border-bottom: 2px solid black;">
                        
                        <div class="col-md-6 col-12">
                            <label class="form-label" for="file">Import File</label>
                            {{ Form::file('file', ['class' => 'form-control', 'id' => 'file', 'accept' => '.xls, .xlsx', 'required' => 'required' ]) }}
                        </div>
                        <div class="col-md-2 col-12 pt-4">
                            <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-upload"></i> Upload</button>
                        </div>
                    </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@stop

@section('script')
<script>
    $('#category').change(function() {
        // default anchor link
        if (this.value) {
            $('.dn-link').attr('href', "{{ asset('storage/import_templates') }}/" + this.value  + '.xls');
        } else {
            $('.dn-link').attr('href', '#');
        }  
        
        // template rules
        $('.dn-link').parents('p').next().remove();
        if (this.value == 'families' || this.value == 'self_advocates') {
            const el = '<p><span class="text-danger">*</span> <b><i>Column4 - DOB must be Fomarted as General Text instead of Date</i></b></p>';
            $('.dn-link').parents('p').after(el);
        }      
    }).trigger('change');
</script>
@stop

