@extends('layouts.core')

@section('title', 'Medical Insurers')
    
@section('content')
    @include('medical_insurers.header')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Medical Insurers</h5>
            <div class="card-content p-2">
                <!-- Insurers List -->
                <livewire:medical-insurers.insurer-list 
                    :medical_insurers="$medical_insurers"  
                    :medical_insurer="@$medical_insurer"  
                />
            </div>
        </div>
    </div>

    <div class="card">
        @if (@$medical_insurer)
            <div class="card-header">{{ $medical_insurer->name }}</div>
        @endif
        <div class="card-body pt-2">
            <div class="card-content p-2">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <!-- medical plans -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="plan-tab" data-bs-toggle="tab" data-bs-target="#plan" type="button" role="tab" aria-controls="plan" aria-selected="true">
                            Medical Plans <i class="bi bi-check2-circle"></i>
                        </button>
                    </li>
                    <!-- plan options -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="plan-option-tab" data-bs-toggle="tab" data-bs-target="#plan-option" type="button" role="tab" aria-controls="plan-option" aria-selected="true">
                            Plan Options <i class="bi bi-check2-circle"></i>
                        </button>
                    </li>
                    <!-- option rates -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="option-rts-tab" data-bs-toggle="tab" data-bs-target="#option-rts" type="button" role="tab" aria-controls="option-rts" aria-selected="true">
                            Option Rates <i class="bi bi-check2-circle"></i>
                        </button>
                    </li>     
                    <!-- shared rates -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shared-rts-tab" data-bs-toggle="tab" data-bs-target="#shared-rts" type="button" role="tab" aria-controls="shared-rts" aria-selected="true">
                            Shared Rates <i class="bi bi-check2-circle"></i>
                        </button>
                    </li>    
                    <!-- plan benefits -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="plan-benefit-tab" data-bs-toggle="tab" data-bs-target="#plan-benefit" type="button" role="tab" aria-controls="plan-benefit" aria-selected="true">
                            Plan Benefits <i class="bi bi-check2-circle"></i>
                        </button>
                    </li>                 
                </ul>
                <div class="tab-content pt-2" id="myTabContent">
                    <div class="tab-pane fade active show p-3" id="plan" role="tabpanel" aria-labelledby="plan-tab">
                        <livewire:medical-insurers.plan-create :medical_insurer="@$medical_insurer" />
                    </div>
                    <div class="tab-pane fade show p-3" id="plan-option" role="tabpanel" aria-labelledby="plan-option-tab">
                        <livewire:medical-insurers.plan-options-create :medical_insurer="@$medical_insurer" />
                    </div>
                    <div class="tab-pane fade show p-3" id="option-rts" role="tabpanel" aria-labelledby="option-rts-tab">
                        <livewire:medical-insurers.option-rates-create :medical_insurer="@$medical_insurer" />
                    </div>
                    <div class="tab-pane fade show p-3" id="shared-rts" role="tabpanel" aria-labelledby="shared-rts-tab">
                        <livewire:medical-insurers.shared-rates-create :medical_insurer="@$medical_insurer" />
                    </div>
                    <div class="tab-pane fade show p-3" id="plan-benefit" role="tabpanel" aria-labelledby="plan-benefit-tab">
                        <livewire:medical-insurers.plan-benefits-create :medical_insurer="@$medical_insurer" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('script')
<script>
    window.addEventListener('openFormModal', event => {
        $("#create-modal").modal('show');
    })
</script>    
@stop
