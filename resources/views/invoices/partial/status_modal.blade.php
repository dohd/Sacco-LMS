<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="statusModalLabel">Update Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      {{ Form::open(['route' => 'invoices.update_status', 'method' => 'POST']) }}
        {{ Form::hidden('invoice_id', null, ['id' => 'invoiceId']) }}
        <div class="modal-body">
          <div class="row g-3 align-items-center p-2">
            <div class="col-md-4">
              <label for="customer" class="col-form-label">Invoice Status</label>
            </div>
            <div class="col-md-8">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="option1" value="draft" checked>
                <label class="form-check-label" for="option1">Draft</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="option2" value="confirmed">
                <label class="form-check-label" for="option2">Confirmed</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      {{ Form::close() }}
    </div>
  </div>
</div>