<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalInsurer;
use Livewire\Component;

class InsurerCreate extends Component
{
    public $reference_id;
    public $name;
    public $medical_insurer;

    protected $listeners = ['selectMedicalInsurer'];

    public function selectMedicalInsurer(MedicalInsurer $medical_insurer)
    {
        $this->medical_insurer = $medical_insurer;
        $this->name = $medical_insurer->name;
        $this->reference_id = $medical_insurer->reference_id;
    }

    protected $rules = [
        'reference_id' => 'required',
        'name' => 'required',
    ];
    
    protected $messages = [
        'reference_id.required' => 'This field is required!',
        'name.required' => 'This field is required!',
    ];

    public function save()
    { 
        $this->validate();

        try {
            MedicalInsurer::create([
                'reference_id' => $this->reference_id,
                'name' => $this->name,
            ]);
        } catch (\Throwable $th) {
            return errorHandler('Error creating medical insurer', $th);
        }
        
        return redirect(route('medical_insurers.index'))->with('success', 'Successfully saved');
    }

    public function update(MedicalInsurer $medical_insurer)
    { 
        $this->validate();
        
        try {
            $medical_insurer->update([
                'reference_id' => $this->reference_id,
                'name' => $this->name,
            ]);
        } catch (\Throwable $th) {
            return errorHandler('Error updating medical insurer', $th);
        }
        
        return redirect(route('medical_insurers.index'))->with('success', 'Successfully updated');
    }

    public function render()
    {
        return view('livewire.medical-insurers.insurer-create');
    }
}
