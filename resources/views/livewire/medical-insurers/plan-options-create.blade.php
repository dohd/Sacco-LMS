<form>
    @csrf
    <div class="row mb-3">
        <div class="col-md-6 col-12">
            <label for="medical_plans">Medical Plan</label>
            <select wire:model="plan_id" class="form-select" required>
                <option value="">-- Choose Medical Plan --</option>
                @foreach ($medical_plans as $i => $item)
                    <option value="{{ $item['id'] }}">{{ $item['plan_name'] }}</option>
                @endforeach
            </select>
            @error('plan_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <!-- Inpatient Options -->
    <fieldset class="border rounded-3 p-3 mb-3">
        <legend class="float-none w-auto px-1 fs-5">Inpatient</legend>
        <div class="row">
            @foreach ($inpatients as $i => $value)
                <div class="col-md-12 col-12 my-1 inpatient-opt">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Label</label>
                                <div class="col-12">
                                    <input type="text" wire:model.defer="inpatients.{{$i}}.label" class="form-control" placeholder="Label">
                                </div>
                                @error('inpatients.'.$i.'.label')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Limit</label>
                                <div class="col-12">
                                    <input type="number" wire:model.defer="inpatients.{{$i}}.limit" class="form-control" placeholder="Amount" required>
                                </div>
                                @error('inpatients.'.$i.'.limit')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Maximum Family Size</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="inpatients.{{$i}}.max_fam_size_id" class="form-select" data-placeholder="Choose Size">
                                        <option value="" selected>-- Choose Size --</option>
                                        @foreach ($max_fam_sizes as $item)
                                            <option value="{{ $item['id'] }}">{{ $item['unit'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('inpatients.'.$i.'.max_fam_size_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        @if ($i > 0)
                            <div class="col-md-1 pt-4">
                                <span class="badge bg-danger text-white" role="button" wire:click="removeRow('inpatient', {{$i}})">
                                    Delete
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                <input type="hidden" wire:model.defer="inpatients.{{$i}}.class" value="Inpatient">
            @endforeach
        </div>   
        <div class="row mb-3">
            <div class="col-md-2 col-2">
                <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('inpatient')">
                    <i class="bi bi-plus-lg"></i> Add Row
                </span>
            </div>
        </div>       
        @if (@$medical_insurer)
            <div class="text-center mt-3">
                <button type="button" wire:click="save" class="btn btn-primary col-2">Save Inpatient</button>
            </div>
        @endif
    </fieldset> 

    <!-- Outpatient Options -->
    <fieldset class="border rounded-3 p-3 mt-3">
        <legend class="float-none w-auto px-1 fs-5">Outpatient</legend>
        <div class="row" data-repeater-list="outpatient-opts">
            @foreach ($outpatients as $i => $value)
                <div wire:key="outpatients-{{$i}}" class="col-md-12 col-12 my-1 outpatient-opt">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Label</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="outpatients.{{$i}}.inpatient_id" class="form-select">
                                        <option value="">-- Choose Label --</option>
                                        @foreach ($inpatients as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('outpatients.'.$i.'.inpatient_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Limit</label>
                                <div class="col-12">
                                    <input type="number" wire:model.defer="outpatients.{{$i}}.limit" class="form-control" placeholder="Amount">
                                </div>
                                @error('outpatients.'.$i.'.limit')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Maximum Family Size</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="outpatients.{{$i}}.max_fam_size_id" class="form-select">
                                        <option value="" selected>-- Choose Size --</option>
                                        @foreach ($max_fam_sizes as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['unit'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('outpatients.'.$i.'.max_fam_size_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        @if ($i > 0)
                        <div class="col-md-1 pt-4">
                            <span class="badge bg-danger text-white" role="button" wire:click="removeRow('outpatient', {{$i}})">
                                Delete
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>   
        <div class="row mb-3">
            <div class="col-md-2 col-2">
                <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('outpatient')">
                    <i class="bi bi-plus-lg"></i> Add Row
                </span>
            </div>
        </div>       
    </fieldset> 

    <!-- Maternity Options -->
    <fieldset class="border rounded-3 p-3">
        <legend class="float-none w-auto px-1 fs-5">Maternity</legend>
        <div class="row" data-repeater-list="maternity-opts">
            @foreach ($maternities as $i => $value)
                <div wire:key="maternities-{{$i}}" class="col-md-12 col-12 my-1 maternity-opt">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Label</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="maternities.{{$i}}.inpatient_id" class="form-select">
                                        <option value="">-- Choose Label --</option>
                                        @foreach ($inpatients as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('maternities.'.$i.'.inpatient_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Limit</label>
                                <div class="col-12">
                                    <input type="number" wire:model.defer="maternities.{{$i}}.limit" class="form-control" placeholder="Amount">
                                </div>
                                @error('maternities.'.$i.'.limit')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Maximum Family Size</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="maternities.{{$i}}.max_fam_size_id" class="form-select">
                                        <option value="">-- Choose Size --</option>
                                        @foreach ($max_fam_sizes as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['unit'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('maternities.'.$i.'.max_fam_size_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        @if ($i > 0)
                        <div class="col-md-1 pt-4">
                            <span class="badge bg-danger text-white" role="button" wire:click="removeRow('maternity', {{$i}})">
                                Delete
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>   
        <div class="row mb-3">
            <div class="col-md-2 col-2">
                <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('maternity')">
                    <i class="bi bi-plus-lg"></i> Add Row
                </span>
            </div>
        </div>       
    </fieldset> 

    <!-- Dental Options -->
    <fieldset class="border rounded-3 p-3">
        <legend class="float-none w-auto px-1 fs-5">Dental</legend>
        <div class="row" data-repeater-list="dental-opts">
            @foreach ($dentals as $i => $value)
                <div wire:key="dentals-{{$i}}" class="col-md-12 col-12 my-1 dental-opt">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Label</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="dentals.{{$i}}.inpatient_id" class="form-select">
                                        <option value="">-- Choose Label --</option>
                                        @foreach ($inpatients as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('dentals.'.$i.'.inpatient_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Limit</label>
                                <div class="col-12">
                                    <input type="number" wire:model.defer="dentals.{{$i}}.limit" class="form-control" placeholder="Amount">
                                </div>
                                @error('dentals.'.$i.'.limit')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Maximum Family Size</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="dentals.{{$i}}.max_fam_size_id" class="form-select">
                                        <option value="">-- Choose Size --</option>
                                        @foreach ($max_fam_sizes as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['unit'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('dentals.'.$i.'.max_fam_size_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        @if ($i > 0)
                        <div class="col-md-1 pt-4">
                            <span class="badge bg-danger text-white" role="button" wire:click="removeRow('dental', {{$i}})">
                                Delete
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>   
        <div class="row mb-3">
            <div class="col-md-2 col-2">
                <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('dental')">
                    <i class="bi bi-plus-lg"></i> Add Row
                </span>
            </div>
        </div>       
    </fieldset> 

    <!-- Optical Options -->
    <fieldset class="border rounded-3 p-3">
        <legend class="float-none w-auto px-1 fs-5">Optical</legend>
        <div class="row" data-repeater-list="optical-opts">
            @foreach ($opticals as $i => $value)
                <div wire:key="opticals-{{$i}}" class="col-md-12 col-12 my-1 optical-opt">
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Label</label>
                                <div class="col-12" wire:ignore>
                                    <select wire:model.defer="opticals.{{$i}}.inpatient_id" class="form-select">
                                        <option value="">-- Choose Label --</option>
                                        @foreach ($inpatients as $j => $item)
                                            <option value="{{ $item['id'] }}">{{ $item['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('opticals.'.$i.'.inpatient_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Limit</label>
                                <div class="col-12">
                                    <input type="number" wire:model.defer="opticals.{{$i}}.limit" class="form-control" placeholder="Amount">
                                </div>
                                @error('opticals.'.$i.'.limit')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <label for="label">Maximum Family Size</label>
                                <div class="col-12" wire:ignore>
                                    <div class="col-12" wire:ignore>
                                        <select wire:model.defer="opticals.{{$i}}.max_fam_size_id" class="form-select">
                                            <option value="">-- Choose Size --</option>
                                            @foreach ($max_fam_sizes as $j => $item)
                                                <option value="{{ $item['id'] }}">{{ $item['unit'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @error('opticals.'.$i.'.max_fam_size_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        @if ($i > 0)
                        <div class="col-md-1 pt-4">
                            <span class="badge bg-danger text-white" role="button" wire:click="removeRow('optical', {{$i}})">
                                Delete
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>   
        <div class="row mb-3">
            <div class="col-md-2 col-2">
                <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('optical')">
                    <i class="bi bi-plus-lg"></i> Add Row
                </span>
            </div>
        </div>       
    </fieldset> 

    <hr>
    @if (@$medical_insurer)
        <div class="text-center">
            <button type="button" wire:click="save" class="btn btn-primary">Save All & Continue >></button>
        </div>
    @endif
</form>
