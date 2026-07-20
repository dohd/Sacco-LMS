<div>
    <div class="table-responsive" style="max-height: 50vh">
        <table class="table table-striped" style="overflow-y: auto">
            <thead>
                <tr class="table-primary">
                    <th width="5%" class="text-center">#</th>
                    <th>Name</th>
                    <th width="8%">Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($medical_insurers as $i => $row)
                    <tr>
                        <th class="text-center">{{ $i+1 }}</th>
                        <td><a href="{{ route('medical_insurers.show', $row->id) }}" class="{{ @$medical_insurer->id == $row->id? 'text-black fw-bold' : 'text-primary' }}">{{ $row->name }}</a></td>
                        <td><input type="checkbox" wire:click="updateStatus({{$row->id}})" name="status" {{ $row->status? 'checked' : '' }}></td>
                        <td>
                            <a href="#" wire:click="selectItem({{$row->id}}, 'edit')" class="text-warning fw-bold edit-row" role="button">Edit</a>&nbsp;|&nbsp;
                            <a href="#"  wire:click="confirmDelete({{$row->id}})" class="text-danger fw-bold">Delete</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- Insurer Create Modal & Form -->
    <div class="modal fade" id="create-modal" tabindex="-1" aria-labelledby="create_modal_label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="create_modal_label">{{ @$medical_insurer->id? 'Edit' : 'Create' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <livewire:medical-insurers.insurer-create />
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('confirmDelete', event => {
                if (confirm('Are you sure?')) {
                    @this.emit('delete', event.detail.item_id);
                }
            });
        });
    </script>
</div>