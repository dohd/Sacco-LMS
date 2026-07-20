<!-- Modal -->
<div class="modal fade" id="status_modal" tabindex="-1" aria-labelledby="status_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="status_modal_label">User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{ Form::open(['route' => array('users.store'), 'method' => 'PATCH']) }}
                <div class="modal-body">
                    <div class="row my-3">
                        <div class="col-12">
                            <select name="status" id="status" class="form-select">
                                <option value="">-- Select Status --</option>
                                @foreach (['1' => 'Active', '0' => 'Inactive'] as $key => $val)
                                    <option value="{{ $key }}">{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
