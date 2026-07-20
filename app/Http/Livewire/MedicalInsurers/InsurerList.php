<?php

namespace App\Http\Livewire\MedicalInsurers;

use App\Models\medical_insurers\MedicalInsurer;
use Illuminate\Support\Collection;
use Livewire\Component;

class InsurerList extends Component
{
    public Collection $medical_insurers;
    public $medical_insurer;

    protected $listeners = ['delete'];

    public function selectItem(MedicalInsurer $medical_insurer, $action)
    {
        $this->medical_insurer = $medical_insurer;
        // load the modal
        if($action == 'edit') {
            $this->emit('selectMedicalInsurer', $medical_insurer); 
            $this->dispatchBrowserEvent('openFormModal');
        }
    }

    public function updateStatus(MedicalInsurer $medical_insurer)
    {
        try {
            if ($medical_insurer->status) $medical_insurer->status = 0;
            else $medical_insurer->status = 1;
            $medical_insurer->save();
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $this->render();
    }

    public function confirmDelete(MedicalInsurer $medical_insurer)
    {
        $this->dispatchBrowserEvent('confirmDelete', ['item_id' => $medical_insurer->id]);
    }

    public function delete(MedicalInsurer $medical_insurer)
    { 
        if ($medical_insurer->plans->count()) 
            return redirect(route('medical_insurers.index'))
                ->with('error', 'Cannot delete medical insurer that has plans');

        try {
            $medical_insurer->delete();
        } catch (\Throwable $th) {
            return errorHandler('Error deleting medical insurer', $th);
        }

        return redirect(route('medical_insurers.index'))->with('success', 'Successfully deleted');
    }

    public function render()
    {
        return view('livewire.medical-insurers.insurer-list');
    }
}
