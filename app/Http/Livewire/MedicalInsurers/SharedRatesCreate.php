<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalPlan;
use App\Models\medical_insurers\SharedRate;
use App\Models\medical_insurers\PlanOption;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SharedRatesCreate extends Component
{
    public $medical_insurer;
    public $plan_id;
    public $option_label;

    public $medical_plans = [];
    public $option_labels = [];
    public $plan_options = [];
    
    public Collection $inpatients;
    public Collection $outpatients;
    public Collection $maternities;
    public Collection $dentals;
    public Collection $opticals;

    public function mount()
    { 
        $this->plan_options = new Collection([PlanOption::make()]);

        $this->medical_plans = MedicalPlan::where('insurer_id', @$this->medical_insurer->id)->get();
        $this->option_labels = PlanOption::select('label')->distinct('label')->pluck('label');

        
        $this->fill([
            'inpatients' => new Collection([SharedRate::make(['class' => 'Inpatient'])]),
            'outpatients' => new Collection([SharedRate::make(['class' => 'Outpatient'])]),
            'maternities' => new Collection([SharedRate::make(['class' => 'Maternity'])]),
            'dentals' => new Collection([SharedRate::make(['class' => 'Dental'])]),
            'opticals' => new Collection([SharedRate::make(['class' => 'Optical'])]),
        ]);
    }


    protected $rules = [
        'plan_id' => 'required',
        'option_label' => 'required',
    ];
    
    protected $messages = [
        'plan_id.required' => 'medical plan field is required!',
        'option_label.required' => 'plan option field is required!',
    ];

    public function save()
    { 
        $this->validate();

        $medical_insurer_id = $this->medical_insurer->id;
        $plan_id = $this->plan_id;
        $option_label = $this->option_label;
        $inpatients = $this->inpatients->toArray();
        $outpatients = $this->outpatients->toArray();
        $maternities = $this->maternities->toArray();
        $dentals = $this->dentals->toArray();
        $opticals = $this->opticals->toArray();

        try {
            DB::beginTransaction();

            $saveSharedRate = function($input_arr) use($medical_insurer_id, $plan_id, $option_label) {
                // delete omitted rows
                $class = @current($input_arr)['class'];
                $item_ids = array_map(fn($v) => @$v['id'], $input_arr);
                if ($item_ids) {
                    SharedRate::where('plan_id', $plan_id)
                    ->where('class', $class)
                    ->whereNotIn('id', $item_ids)
                    ->delete();
                }
                foreach ($input_arr as $value) {
                    $value1 = Arr::only($value, ['id', 'class', 'age_from', 'age_to']);
                    // check if rate has been set
                    $count = 0;
                    foreach (range(0,15) as $j => $num) {
                        if ($j) {
                            $rate = @$value['m'.$num];
                            $value1['m'.$num] = numberClean($rate);
                        } else {
                            $rate = @$value['m'];
                            $value1['m'] = numberClean($rate);
                        }
                        if (@$rate) $count++;
                    }
                    if (!$count) continue;

                    $value1 = array_replace($value1, [
                        'insurer_id' => $medical_insurer_id,
                        'plan_id' => $plan_id,
                        'label' => $option_label,
                        'user_id' => auth()->user()->id,
                    ]);
                    $shared_rate = SharedRate::firstOrNew(['id' => @$value1['id']]);
                    $shared_rate->fill($value1);
                    $shared_rate->save();
                }
            };

            $saveSharedRate($inpatients);
            $saveSharedRate($outpatients);            
            $saveSharedRate($maternities);            
            $saveSharedRate($dentals);
            $saveSharedRate($opticals);

            DB::commit();
        } catch (\Throwable $th) {
            return errorHandler('Error updating shared rates', $th);
        }
        
        return redirect(route('medical_insurers.show', $this->medical_insurer))->with('success', 'Successfully updated shared rates');
    }

    public function updatedOptionLabel($label)
    {        
        $shared_rates = SharedRate::where('plan_id', $this->plan_id)->where('label', $label)->get();
        if ($shared_rates->count()) {
            $this->fill([
                'inpatients' => new Collection($shared_rates->where('class', 'Inpatient')),
                'outpatients' => new Collection($shared_rates->where('class', 'Outpatient')),
                'maternities' => new Collection($shared_rates->where('class', 'Maternity')),
                'dentals' => new Collection($shared_rates->where('class', 'Dental')),
                'opticals' => new Collection($shared_rates->where('class', 'Optical')),
            ]);
        } else {
            $this->fill([
                'inpatients' => new Collection([SharedRate::make(['class' => 'Inpatient'])]),
                'outpatients' => new Collection([SharedRate::make(['class' => 'Outpatient'])]),
                'maternities' => new Collection([SharedRate::make(['class' => 'Maternity'])]),
                'dentals' => new Collection([SharedRate::make(['class' => 'Dental'])]),
                'opticals' => new Collection([SharedRate::make(['class' => 'Optical'])]),
            ]);
        }
        $this->dispatchBrowserEvent('updateIndex');
    }

    public function addRow($class)
    {
        switch ($class) {
            case 'inpatient': 
                $this->inpatients->push(SharedRate::make(['class' => 'Inpatient']));
                break;
            case 'outpatient':
                $this->outpatients->push(SharedRate::make(['class' => 'Outpatient'])); 
                break;
            case 'maternity': 
                $this->maternities->push(SharedRate::make(['class' => 'Maternity'])); 
                break;
            case 'dental': 
                $this->dentals->push(SharedRate::make(['class' => 'Dental'])); 
                break;
            case 'optical': 
                $this->opticals->push(SharedRate::make(['class' => 'Optical'])); 
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
        return view('livewire.medical-insurers.shared-rates-create');
    }
}
