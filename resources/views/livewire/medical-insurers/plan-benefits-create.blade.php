<form>
    @csrf
    <div class="row mb-3">
        <div class="col-md-6 col-12">
            <label for="medical_plans">Medical Plan<span class="text-danger">*</span></label>
            <select wire:model="plan_id" class="form-select" required>
                <option value="">-- Choose Medical Plan --</option>
                @foreach ($medical_plans as $i => $item)
                    <option value="{{ $item['id'] }}">{{ $item['plan_name'] }}</option>
                @endforeach
            </select>
            @error('plan_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>

    <div wire:ignore class="row mb-3">
        <div class="col-md-10 col-10">
            <label for="impact">Benefit Narrative</label>
            <input type="text" wire:model.defer="narrative" id="narrative" class="narrative d-none">
            <div class="richtext" id="narrative_text"></div>
        </div>
    </div>

    <hr>
    @if (@$medical_insurer)
        <div class="text-center">
            <button type="button" wire:click="save" class="btn btn-primary">Save & Finnish >></button>
        </div>
    @endif

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            $('#narrative').on('keyup', function() {
                $(this)[0].dispatchEvent(new Event('input'));
            });
            window.addEventListener('updateNarrative', event => {
                const {narrative} = event.detail;
                $('#narrative_text').find('.ql-editor').html(narrative);
            });
        });
    </script>
</form>
