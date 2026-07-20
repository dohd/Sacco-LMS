<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalPlan;
use App\Models\medical_insurers\OptionRate;
use App\Models\medical_insurers\PlanOption;
use App\Models\medical_insurers\RateVariable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OptionRatesCreate extends Component
{
    public $medical_insurer;
    public $plan_id;
    public $medical_plans = [];
    public $plan_options = [];
    
    public Collection $inpatients;
    public Collection $outpatients;
    public Collection $maternities;
    public Collection $dentals;
    public Collection $opticals;

    public function mount()
    { 
        $this->medical_plans = MedicalPlan::where('insurer_id', @$this->medical_insurer->id)->get();
        $this->plan_options = PlanOption::get();
        
        $this->fill([
            'inpatients' => new Collection([OptionRate::make(['class' => 'Inpatient'])]),
            'outpatients' => new Collection([OptionRate::make(['class' => 'Outpatient'])]),
            'maternities' => new Collection([OptionRate::make(['class' => 'Maternity'])]),
            'dentals' => new Collection([OptionRate::make(['class' => 'Dental'])]),
            'opticals' => new Collection([OptionRate::make(['class' => 'Optical'])]),
        ]);
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

        $medical_insurer_id = $this->medical_insurer->id;
        $plan_id = $this->plan_id;
        $inpatients = $this->inpatients->toArray();
        $outpatients = $this->outpatients->toArray();
        $maternities = $this->maternities->toArray();
        $dentals = $this->dentals->toArray();
        $opticals = $this->opticals->toArray();

        try {
            DB::beginTransaction();

            $saveOptionRate = function ($input_arr) use($medical_insurer_id, $plan_id) {
                // delete omitted rows
                $class = @current($input_arr)['class'];
                $item_ids = array_map(fn($v) => @$v['id'], $input_arr);
                if ($item_ids) {
                    $option_rates = OptionRate::where('plan_id', $plan_id)->where('class', $class)->whereNotIn('id', $item_ids)->get();
                    foreach ($option_rates as $option_rate) {
                        $option_rate->rate_variables()->delete();
                        $option_rate->delete();
                    }
                }

                foreach ($input_arr as $key => $value) {
                    $value1 = Arr::only($value, ['id', 'class', 'row_index', 'limit_label', 'age_from', 'age_to']);
                    $value1 = array_replace($value1, [
                        'insurer_id' => $medical_insurer_id,
                        'plan_id' => $plan_id,
                        'user_id' => auth()->user()->id,
                    ]);
                    
                    $option_rate = OptionRate::firstOrNew(['id' => @$value1['id']]);
                    $option_rate->fill($value1);
                    $option_rate->save();
                    
                    if (@$value['rate'] && @$value['plan_option_id']) {
                        ksort($value['rate']);
                        ksort($value['plan_option_id']);
                        if (@$value['rate_id']) ksort($value['rate_id']);
                        foreach ($value['rate'] as $key2 => $value2) {
                            $rate_variable = RateVariable::firstOrNew(['id' => @$value['rate_id'][$key2]]);
                            $rate_variable->fill([
                                'option_rate_id' => $option_rate->id,
                                'plan_option_id' => @$value['plan_option_id'][$key2],
                                'rate' => numberClean($value2),
                            ]);
                            $rate_variable->save();
                        }
                    } else $option_rate->delete();
                }
            };

            $saveOptionRate($inpatients);
            $saveOptionRate($outpatients);
            $saveOptionRate($maternities);
            $saveOptionRate($dentals);
            $saveOptionRate($opticals);

            DB::commit();
        } catch (\Throwable $th) {
            return errorHandler('Error updating option rates', $th);
        }
        
        return redirect(route('medical_insurers.show', $this->medical_insurer))->with('success', 'Successfully updated option rates');
    }

    public function updatedPlanId($id)
    {
        $this->plan_options = PlanOption::where('plan_id', $id)->get();
        $option_rates = OptionRate::where('plan_id', $id)->get()->map(function($v) {
            $v['rate'] = $v->rate_variables->pluck('rate')->toArray();
            $v['rate_id'] = $v->rate_variables->pluck('id')->toArray();
            return $v;
        });
        
        if ($option_rates->count()) {
            $this->fill([
                'inpatients' => new Collection($option_rates->where('class', 'Inpatient')),
                'outpatients' => new Collection($option_rates->where('class', 'Outpatient')),
                'maternities' => new Collection($option_rates->where('class', 'Maternity')),
                'dentals' => new Collection($option_rates->where('class', 'Dental')),
                'opticals' => new Collection($option_rates->where('class', 'Optical')),
            ]);
        } else {
            $this->fill([
                'inpatients' => new Collection([OptionRate::make(['class' => 'Inpatient'])]),
                'outpatients' => new Collection([OptionRate::make(['class' => 'Outpatient'])]),
                'maternities' => new Collection([OptionRate::make(['class' => 'Maternity'])]),
                'dentals' => new Collection([OptionRate::make(['class' => 'Dental'])]),
                'opticals' => new Collection([OptionRate::make(['class' => 'Optical'])]),
            ]);
        }
        $this->dispatchBrowserEvent('updateIndex');
    }

    public function addRow($class)
    {
        switch ($class) {
            case 'inpatient': 
                $this->inpatients->push(OptionRate::make(['class' => 'Inpatient']));
                break;
            case 'outpatient':
                $this->outpatients->push(OptionRate::make(['class' => 'Outpatient'])); 
                break;
            case 'maternity': 
                $this->maternities->push(OptionRate::make(['class' => 'Maternity'])); 
                break;
            case 'dental': 
                $this->dentals->push(OptionRate::make(['class' => 'Dental'])); 
                break;
            case 'optical': 
                $this->opticals->push(OptionRate::make(['class' => 'Optical'])); 
                break;
        }
        $this->dispatchBrowserEvent('updateIndex');
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
        $this->dispatchBrowserEvent('updateIndex');
    }

    public function render()
    {
        return view('livewire.medical-insurers.option-rates-create');
    }
}
