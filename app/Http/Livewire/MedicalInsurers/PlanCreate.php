<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalPlan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PlanCreate extends Component
{
    public $medical_insurer;
    public $medical_plans = [];

    public function mount()
    { 
        $this->medical_plans = MedicalPlan::where('insurer_id', @$this->medical_insurer->id)->get()->toArray();
        if (!count($this->medical_plans)) $this->medical_plans[] = MedicalPlan::make(['plan_name' => '']);
    }

    protected $rules = [
        'medical_plans.*.plan_name' => 'required',
    ];
    
    protected $messages = [
        'medical_plans.*.plan_name.required' => 'medical plan field is required!',
    ];

    public function save()
    { 
        $this->validate();        
        $insurer_id = $this->medical_insurer->id;

        try {
            DB::beginTransaction();
            $input_arr = $this->medical_plans;

            // delete omitted rows
            $item_ids = array_map(fn($v) => @$v['id'], $input_arr);
            if ($item_ids) {
                MedicalPlan::doesntHave('plan_options')
                ->where('insurer_id', $insurer_id)
                ->whereNotIn('id', $item_ids)
                ->delete();
            }

            foreach ($input_arr as $key => $value) {
                $value1 = Arr::only($value, ['id', 'plan_name']);
                $value1 = array_replace($value1, [
                    'insurer_id' => $this->medical_insurer->id,
                    'user_id' => auth()->user()->id,
                ]);
                
                $medical_plan = MedicalPlan::firstOrNew(['id' => @$value1['id']]);
                $medical_plan->fill($value1);
                $medical_plan->save();
            } 

            DB::commit();
        } catch (\Throwable $th) {
            return errorHandler('Error updating medical plans', $th);
        }

        return redirect(route('medical_insurers.show', $this->medical_insurer))->with('success', 'Successfully updated');
    }

    public function addRow()
    {
        $this->medical_plans[] = MedicalPlan::make(['plan_name' => '']);
    }

    public function removeRow($key)
    {
        array_splice($this->medical_plans, $key, 1);
    }

    public function render()
    {
        return view('livewire.medical-insurers.plan-create');
    }
}
