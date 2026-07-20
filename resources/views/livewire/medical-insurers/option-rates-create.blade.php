<div>
    <style>
        .num-inpt {
            width: 8em;
        }
        
        #optical-rate-tbl th {
            padding-left: 3em;
        }
        #dental-rate-tbl th {
            padding-left: 3em;
        }
        #maternity-rate-tbl th {
            padding-left: 3em;
        }
        #outpatient-rate-tbl th {
            padding-left: 3em;
        }
    </style>

    <form>
        @csrf
        <div class="row mb-3">
            <div class="col-md-6 col-12">
                <label for="medical_plans">Medical Plan<span class="text-danger">*</span></label>
                <select wire:model="plan_id" class="form-select" id="plan-id" required>
                    <option value="">-- Choose Medical Plan --</option>
                    @foreach ($medical_plans as $i => $item)
                        <option value="{{ $item['id'] }}">{{ $item['plan_name'] }}</option>
                    @endforeach
                </select>
                @error('plan_id')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
        </div>
        
        <!-- Inpatient Rates -->
        <fieldset class="border rounded-3 p-3 mb-3">
            <legend class="float-none w-auto px-1 fs-5">Inpatient Rates</legend>
            <div class="table-responsive">
                <table class="table table-striped" id="inpatient-rate-tbl">
                    <thead>
                        <tr class="table-primary">
                            <th colspan="3">&nbsp;</th>
                            @foreach ($plan_options->where('class', 'Inpatient') as $item)
                                <th>{{ $item->label }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th  colspan="2" class="text-center">Limit Per Family</th>
                            <th width="12%" class="text-center">Age Limit</th>
                            @foreach ($plan_options->where('class', 'Inpatient') as $item)
                                <th width="12%">{{ numberFormat($item->limit) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inpatients as $i => $item)
                            <tr>
                                <td width="5%">
                                    <span class="badge bg-danger text-white {{!$i? 'invisible' : ''}}" role="button" wire:click="removeRow('inpatient', {{$i}})">Delete</span>
                                </td>
                                <td>
                                    <select wire:model.defer="inpatients.{{$i}}.limit_label" class="form-select" style="width:10rem">
                                        <option value="">-- Choose Type --</option>
                                        @foreach (['Principal', 'Spouse', 'Child'] as $j => $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="row g-1" style="width:12em;">
                                        <div class="col-6"><input wire:model.defer="inpatients.{{$i}}.age_from" class="form-control num-inpt" style="width: 6em" type="number" placeholder="From"></div>
                                        <div class="col-6"><input wire:model.defer="inpatients.{{$i}}.age_to" class="form-control num-inpt" style="width: 6em" type="number" placeholder="To"></div>
                                    </div>                                                        
                                </td>
                                @php $j = 0 @endphp
                                @foreach ($plan_options->where('class', 'Inpatient') as $option)
                                    <td>
                                        <input wire:model.defer="inpatients.{{$i}}.rate.{{$j}}" class="form-control num-inpt" type="number">
                                        <input wire:model.defer="inpatients.{{$i}}.plan_option_id.{{$j}}" data-id="{{ $option->id }}" class="plan-option-id d-none" type="number">
                                    </td>
                                    @php $j++ @endphp
                                @endforeach
                                <input wire:model.defer="inpatients.{{$i}}.row_index" data-index="{{$i}}" class="row-index d-none" type="number">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row mb-3">
                <div class="col-md-2 col-2">
                    <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('inpatient')">
                        <i class="bi bi-plus-lg"></i> Add Row 
                    </span>
                </div>
            </div>  
        </fieldset>

        <!-- Outpatient Rates -->
        <fieldset class="border rounded-3 p-3 mb-3">
            <legend class="float-none w-auto px-1 fs-5">Outpatient Rates</legend>
            <div class="table-responsive">
                <table class="table table-striped" id="outpatient-rate-tbl">
                    <thead>
                        <tr class="table-primary">
                            <th colspan="3">&nbsp;</th>
                            @foreach ($plan_options->where('class', 'Outpatient') as $item)
                                <th>{{ $item->label }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th colspan="2" class="text-center">Limit per person per annum</th>
                            <th width="12%" class="text-center">Age Limit</th>
                            @foreach ($plan_options->where('class', 'Outpatient') as $item)
                                <th width="12%">{{ numberFormat($item->limit) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($outpatients as $i => $item)
                            <tr>
                                <td width="5%">
                                    <span class="badge bg-danger text-white {{!$i? 'invisible' : ''}}" role="button" wire:click="removeRow('inpatient', {{$i}})">Delete</span>
                                </td>
                                <td>
                                    <select wire:model.defer="outpatients.{{$i}}.limit_label" class="form-select" style="width:10rem">
                                        <option value="">-- Choose Type --</option>
                                        @foreach (['Principal', 'Spouse', 'Child'] as $j => $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="row g-1" style="width:12em;">
                                        <div class="col-6"><input wire:model.defer="outpatients.{{$i}}.age_from" class="form-control num-inpt" style="width: 6em" type="number" placeholder="From"></div>
                                        <div class="col-6"><input wire:model.defer="outpatients.{{$i}}.age_to" class="form-control num-inpt" style="width: 6em" type="number" placeholder="To"></div>
                                    </div>                                                        
                                </td>
                                @php $j = 0 @endphp
                                @foreach ($plan_options->where('class', 'Outpatient') as $option)
                                    <td>
                                        <input wire:model.defer="outpatients.{{$i}}.rate.{{$j}}" class="form-control num-inpt" type="number">
                                        <input wire:model.defer="outpatients.{{$i}}.plan_option_id.{{$j}}" data-id="{{ $option->id }}" class="plan-option-id d-none" type="number">
                                    </td>
                                    @php $j++ @endphp
                                @endforeach
                                <input wire:model.defer="outpatients.{{$i}}.row_index" data-index="{{$i}}" class="row-index d-none" type="number">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row mb-3">
                <div class="col-md-2 col-2">
                    <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('outpatient')">
                        <i class="bi bi-plus-lg"></i> Add Row 
                    </span>
                </div>
            </div>  
        </fieldset>

        <!-- Maternity Rates -->
        <fieldset class="border rounded-3 p-3 mb-3">
            <legend class="float-none w-auto px-1 fs-5">Maternity Rates</legend>
            <div class="table-responsive">
                <table class="table table-striped" id="maternity-rate-tbl">
                    <thead>
                        <tr class="table-primary">
                            <th colspan="3">&nbsp;</th>
                            @foreach ($plan_options->where('class', 'Maternity') as $item)
                                <th>{{ $item->label }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th colspan="2" class="text-center">Limit per family</th>
                            <th width="12%" class="text-center">Age Limit</th>
                            @foreach ($plan_options->where('class', 'Maternity') as $item)
                                <th width="12%">{{ numberFormat($item->limit) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($maternities as $i => $item)
                            <tr>
                                <td width="5%">
                                    <span class="badge bg-danger text-white {{!$i? 'invisible' : ''}}" role="button" wire:click="removeRow('inpatient', {{$i}})">Delete</span>
                                </td>
                                <td>
                                    <select wire:model.defer="maternities.{{$i}}.limit_label" class="form-select" style="width:10rem">
                                        @foreach (['Spouse'] as $j => $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="row g-1" style="width:12em;">
                                        <div class="col-6"><input wire:model.defer="maternities.{{$i}}.age_from" class="form-control num-inpt" style="width: 6em" type="number" placeholder="From"></div>
                                        <div class="col-6"><input wire:model.defer="maternities.{{$i}}.age_to" class="form-control num-inpt" style="width: 6em" type="number" placeholder="To"></div>
                                    </div>                                                        
                                </td>
                                @php $j = 0 @endphp
                                @foreach ($plan_options->where('class', 'Maternity') as $option)
                                    <td>
                                        <input wire:model.defer="maternities.{{$i}}.rate.{{$j}}" class="form-control num-inpt" type="number">
                                        <input wire:model.defer="maternities.{{$i}}.plan_option_id.{{$j}}" data-id="{{ $option->id }}" class="plan-option-id d-none" type="number">
                                    </td>
                                    @php $j++ @endphp
                                @endforeach
                                <input wire:model.defer="maternities.{{$i}}.row_index" data-index="{{$i}}" class="row-index d-none" type="number">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row mb-3">
                <div class="col-md-2 col-2">
                    <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('maternity')">
                        <i class="bi bi-plus-lg"></i> Add Row 
                    </span>
                </div>
            </div>  
        </fieldset>
                
        <!-- Dental Rates -->
        <fieldset class="border rounded-3 p-3 mb-3">
            <legend class="float-none w-auto px-1 fs-5">Dental Rates</legend>
            <div class="table-responsive">
                <table class="table table-striped" id="dental-rate-tbl">
                    <thead>
                        <tr class="table-primary">
                            <th colspan="3">&nbsp;</th>
                            @foreach ($plan_options->where('class', 'Dental') as $item)
                                <th>{{ $item->label }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th colspan="2" class="text-center">Limit per person per annum</th>
                            <th width="12%" class="text-center">Age Limit</th>
                            @foreach ($plan_options->where('class', 'Dental') as $item)
                                <th width="12%">{{ numberFormat($item->limit) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dentals as $i => $item)
                            <tr>
                                <td width="5%">
                                    <span class="badge bg-danger text-white {{!$i? 'invisible' : ''}}" role="button" wire:click="removeRow('inpatient', {{$i}})">Delete</span>
                                </td>
                                <td>
                                    <select wire:model.defer="dentals.{{$i}}.limit_label" class="form-select" style="width:10rem">
                                        <option value="">-- Choose Type --</option>
                                        @foreach (['Principal', 'Spouse', 'Child'] as $j => $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="row g-1" style="width:12em;">
                                        <div class="col-6"><input wire:model.defer="dentals.{{$i}}.age_from" class="form-control num-inpt" style="width: 6em" type="number" placeholder="From"></div>
                                        <div class="col-6"><input wire:model.defer="dentals.{{$i}}.age_to" class="form-control num-inpt" style="width: 6em" type="number" placeholder="To"></div>
                                    </div>                                                        
                                </td>
                                @php $j = 0 @endphp
                                @foreach ($plan_options->where('class', 'Dental') as $option)
                                    <td>
                                        <input wire:model.defer="dentals.{{$i}}.rate.{{$j}}" class="form-control num-inpt" type="number">
                                        <input wire:model.defer="dentals.{{$i}}.plan_option_id.{{$j}}" data-id="{{ $option->id }}" class="plan-option-id d-none" type="number">
                                    </td>
                                    @php $j++ @endphp
                                @endforeach
                                <input wire:model.defer="dentals.{{$i}}.row_index" data-index="{{$i}}" class="row-index d-none" type="number">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row mb-3">
                <div class="col-md-2 col-2">
                    <span class="badge bg-success text-white add-row" role="button" wire:click="addRow('dental')">
                        <i class="bi bi-plus-lg"></i> Add Row 
                    </span>
                </div>
            </div>  
        </fieldset>
        
        <!-- Optical Rates -->
        <fieldset class="border rounded-3 p-3 mb-3">
            <legend class="float-none w-auto px-1 fs-5">Optical Rates</legend>
            <div class="table-responsive">
                <table class="table table-striped" id="optical-rate-tbl">
                    <thead>
                        <tr class="table-primary">
                            <th colspan="3">&nbsp;</th>
                            @foreach ($plan_options->where('class', 'Optical') as $item)
                                <th>{{ $item->label }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th colspan="2" class="text-center">Limit per person per annum</th>
                            <th width="12%" class="text-center">Age Limit</th>
                            @foreach ($plan_options->where('class', 'Optical') as $item)
                                <th width="12%">{{ numberFormat($item->limit) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($opticals as $i => $item)
                            <tr>
                                <td width="5%">
                                    <span class="badge bg-danger text-white {{!$i? 'invisible' : ''}}" role="button" wire:click="removeRow('inpatient', {{$i}})">Delete</span>
                                </td>
                                <td>
                                    <select wire:model.defer="opticals.{{$i}}.limit_label" class="form-select" style="width:10rem">
                                        <option value="">-- Choose Type --</option>
                                        @foreach (['Principal', 'Spouse', 'Child'] as $j => $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="row g-1" style="width:12em;">
                                        <div class="col-6"><input wire:model.defer="opticals.{{$i}}.age_from" class="form-control num-inpt" style="width: 6em" type="number" placeholder="From"></div>
                                        <div class="col-6"><input wire:model.defer="opticals.{{$i}}.age_to" class="form-control num-inpt" style="width: 6em" type="number" placeholder="To"></div>
                                    </div>                                                        
                                </td>
                                @php $j = 0 @endphp
                                @foreach ($plan_options->where('class', 'Optical') as $option)
                                    <td>
                                        <input wire:model.defer="opticals.{{$i}}.rate.{{$j}}" class="form-control num-inpt" type="number">
                                        <input wire:model.defer="opticals.{{$i}}.plan_option_id.{{$j}}" data-id="{{ $option->id }}" class="plan-option-id d-none" type="number">
                                    </td>
                                    @php $j++ @endphp
                                @endforeach
                                <input wire:model.defer="opticals.{{$i}}.row_index" data-index="{{$i}}" class="row-index d-none" type="number">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                <button type="button" wire:click="save" class="btn btn-primary">Save & Continue >></button>
            </div>
        @endif
    </form>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('updateIndex', () => {
                $('.row-index').each(function() {
                    $(this).val($(this).attr('data-index'));
                    $(this)[0].dispatchEvent(new Event('input'));
                });
                $('.plan-option-id').each(function() {
                    $(this).val($(this).attr('data-id'));
                    $(this)[0].dispatchEvent(new Event('input'));
                });
            });
            window.dispatchEvent(new Event('updateIndex') );
        });
    </script>
</div>
