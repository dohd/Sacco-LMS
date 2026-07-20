<form>
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <label for="status" class="col-12 col-form-label">Reference ID</label>
                <input type="number" wire:model="reference_id" class="form-control">
            </div>
            @error('reference_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="row">
            <div class="col-12">
                <label for="name" class="col-12 col-form-label">Insurer Name</label>
                <input type="text" wire:model="name" class="form-control">
            </div>
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        @if (@$medical_insurer->id)
            <button type="button" wire:click="update({{$medical_insurer->id}})" class="btn btn-primary">Update</button>
        @else
            <button type="button" wire:click="save" class="btn btn-primary">Save</button>
        @endif
    </div>
</form>
