<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalPlan;
use App\Models\medical_insurers\PlanOption;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PlanOptionsCreate extends Component
{
    public $medical_insurer;
    public $plan_id;
    public $medical_plans = [];
    public $max_fam_sizes = [];

    public Collection $inpatients;
    public Collection $outpatients;
    public Collection $maternities;
    public Collection $dentals;
    public Collection $opticals;

    public function mount()
    { 
        $this->medical_plans = MedicalPlan::where('insurer_id', @$this->medical_insurer->id)->get();
        $this->max_fam_sizes = array_map(fn($v) => ['id' => $v+1, 'unit' => $v? 'M+'.$v : 'M'], range(0,15));
        
        $this->fill([
            'inpatients' => new Collection([PlanOption::make(['class' => 'Inpatient'])]),
            'outpatients' => new Collection([PlanOption::make(['class' => 'Outpatient'])]),
            'maternities' => new Collection([PlanOption::make(['class' => 'Maternity'])]),
            'dentals' => new Collection([PlanOption::make(['class' => 'Dental'])]),
            'opticals' => new Collection([PlanOption::make(['class' => 'Optical'])]),
        ]);
    }

    protected $rules = [
        'plan_id' => 'required',
        'inpatients.*.limit' => 'required',
        'inpatients.*.max_fam_size_id' => 'required',
    ];
    
    protected $messages = [
        'plan_id.required' => 'medical plan field is required!',
        'inpatients.*.limit.required' => 'limit field is required!',
        'inpatients.*.max_fam_size_id.required' => 'max family size field is required!',
    ];

    public function save()
    { 
        $this->validate();
        $medical_insurer_id = $this->medical_insurer->id;
        $plan_id = $this->plan_id;
        $inpatients = $this->inpatients->toArray();
        $outpatients = $this->outpatients->toArray();
        $maternities = $this->maternities->toArray();
        $dentals = $this->dentals->toArray();
        $opticals = $this->opticals->toArray();

        try {
            DB::beginTransaction();

            $savePlanOption = function ($input_arr) use($medical_insurer_id, $plan_id) {
                // delete omitted rows
                $class = @current($input_arr)['class'];
                $item_ids = array_map(fn($v) => @$v['id'], $input_arr);
                if ($item_ids) {
                    PlanOption::doesntHave('rate_variables')
                    ->where('plan_id', $plan_id)
                    ->where('class', $class)
                    ->whereNotIn('id', $item_ids)
                    ->delete();
                }
                
                foreach ($input_arr as $key => $value) {
                    $value1 = Arr::only($value, ['id', 'class', 'label', 'limit', 'max_fam_size_id', 'inpatient_id']);
                    $value1 = array_replace($value1, [
                        'insurer_id' => $medical_insurer_id,
                        'plan_id' => $plan_id,
                        'user_id' => auth()->user()->id,
                        'limit' => numberClean(@$value1['limit']),
                    ]);
                    if (!$value1['limit']) continue;

                    $plan_option = PlanOption::firstOrNew(['id' => @$value1['id']]);
                    $plan_option->fill($value1);
                    $plan_option->save();
                }    
            };
            
            $savePlanOption($inpatients);
            $savePlanOption($outpatients);
            $savePlanOption($maternities);
            $savePlanOption($dentals);
            $savePlanOption($opticals);

            DB::commit();
        } catch (\Throwable $th) {
            return errorHandler('Error updating plan options', $th);
        }
        
        return redirect(route('medical_insurers.show', $this->medical_insurer))->with('success', 'Successfully updated plan options');
    }

    public function updatedPlanId($plan_id)
    {
        $plan_options = PlanOption::where('plan_id', $plan_id)->get();
        if ($plan_options->count()) {
            $this->fill([
                'inpatients' => new Collection($plan_options->where('class', 'Inpatient')),
                'outpatients' => new Collection($plan_options->where('class', 'Outpatient')),
                'maternities' => new Collection($plan_options->where('class', 'Maternity')),
                'dentals' => new Collection($plan_options->where('class', 'Dental')),
                'opticals' => new Collection($plan_options->where('class', 'Optical')),
            ]);
        } else {
            $this->fill([
                'inpatients' => new Collection([PlanOption::make(['class' => 'Inpatient'])]),
                'outpatients' => new Collection([PlanOption::make(['class' => 'Outpatient'])]),
                'maternities' => new Collection([PlanOption::make(['class' => 'Maternity'])]),
                'dentals' => new Collection([PlanOption::make(['class' => 'Dental'])]),
                'opticals' => new Collection([PlanOption::make(['class' => 'Optical'])]),
            ]);
        }
    }

    public function addRow($class)
    {
        switch ($class) {
            case 'inpatient': 
                $this->inpatients->push(PlanOption::make(['class' => 'Inpatient']));
                break;
            case 'outpatient':
                $this->outpatients->push(PlanOption::make(['class' => 'Outpatient'])); 
                break;
            case 'maternity': 
                $this->maternities->push(PlanOption::make(['class' => 'Maternity'])); 
                break;
            case 'dental': 
                $this->dentals->push(PlanOption::make(['class' => 'Dental'])); 
                break;
            case 'optical': 
                $this->opticals->push(PlanOption::make(['class' => 'Optical'])); 
                break;
        }
    }

    public function removeRow($class, $key)
    {
        switch ($class) {
            case 'inpatient': $this->inpatients->pull($key); break;
            case 'outpatient': $this->outpatients->pull($key); break;
            case 'maternity': $this->maternities->pull($key); break;
            case 'dental': $this->dentals->pull($key); break;
            case 'optical': $this->opticals->pull($key); break;
        }
    }

    public function render()
    {
        return view('livewire.medical-insurers.plan-options-create');
    }
}
