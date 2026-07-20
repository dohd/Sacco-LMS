<form>
    @csrf
    @foreach ($medical_plans as $i => $value)
        <div class="row mb-2">
            <div class="col-md-6 col-12">
                <input type="text" wire:model.defer="medical_plans.{{$i}}.plan_name" class="form-control" placeholder="Name of Medical Plan*">
            </div>
            @if ($i > 0)
                <div class="col-md-1 pt-1">
                    <span class="badge bg-danger text-white" role="button" wire:click="removeRow({{$i}})">
                        Delete
                    </span>
                </div>
            @endif
            @error('medical_plans.'.$i.'.plan_name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    @endforeach
    
    <div class="row mb-3">
        <div class="col-md-2 col-2">
            <span class="badge bg-success text-white add-row" role="button" wire:click="addRow">
                <i class="bi bi-plus-lg"></i> Add Line
            </span>
        </div>
    </div>
    
    <hr>
    @if (@$medical_insurer)
        <div class="text-center">
            <button type="button" wire:click="save" class="btn btn-primary">Save & Continue >></button>
        </div>
    @endif
</form>
