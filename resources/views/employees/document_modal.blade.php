<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="documentModalLabel">Upload Documents</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      {{ Form::open(['route' => ['employees.document_upload'], 'method' => 'POST', 'files' => true, ]) }}
      <div class="modal-body">
        {{ Form::hidden('employee_id', $employee->id) }}
        <div class="form-group mb-1">
            <label for="documentType">Document Type</label>
            <select name="doc_type" class="form-select">
              <option>-- Select Type --</option>
              @foreach (config('employee_vars.document_type') as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
              @endforeach
            </select>
        </div>
        <div class="form-group mb-1">
          <label class="form-label" for="file">Document</label>
          {{ Form::file('file', ['class' => 'form-control', 'id' => 'file', 'accept' => '', 'required' => 'required' ]) }}
        </div>
        <div class="form-group">
          <label class="form-label" for="caption">Caption</label>
          {{ Form::text('caption', null, ['class' => 'form-control', 'id' => 'caption']) }}
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
      {{ Form::close() }}
    </div>
  </div>
</div>