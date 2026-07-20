<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalPlan;
use App\Models\medical_insurers\PlanBenefit;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PlanBenefitsCreate extends Component
{
    public $medical_insurer;
    public $medical_plans = [];
    public $plan_id;
    public $plan_benefit_id;
    public $narrative;
    
    public function mount()
    { 
        $this->medical_plans = MedicalPlan::where('insurer_id', @$this->medical_insurer->id)->get();
    }

    protected $rules = [
        'plan_id' => 'required',
    ];
    
    protected $messages = [
        'plan_id.required' => 'medical plan field is required!',
    ];

    public function save()
    { 
        $this->validate();
        
        try {
            DB::beginTransaction();

            $plan_benefit = PlanBenefit::firstOrNew(['id' => $this->plan_benefit_id]);
            $plan_benefit->fill(['plan_id' => $this->plan_id, 'narrative' => $this->narrative]);
            $plan_benefit->save(); 

            DB::commit();
        } catch (\Throwable $th) {
            return errorHandler('Error updating plan benefit', $th);
        }
        
        return redirect(route('medical_insurers.show', $this->medical_insurer))->with('success', 'Successfully updated plan benefit');
    }

    public function updatedPlanId($id)
    {
        $plan_benefit = PlanBenefit::where('plan_id', $id)->first();
        $this->narrative = @$plan_benefit->narrative;
        $this->plan_benefit_id = @$plan_benefit->id;
        $this->dispatchBrowserEvent('updateNarrative', ['narrative' => $this->narrative]);
    }

    public function render()
    {
        return view('livewire.medical-insurers.plan-benefits-create');
    }
}
