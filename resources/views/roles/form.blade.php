<div class="row mb-4 px-3">
    <div class="col-md-12 col-12">
        <label for="name">Role Name<span class="text-danger">*</span></label>
        {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
</div>
<div class="table-responsive mb-5 px-3">
    <table class="table table-flush-spacing">
        <tbody>
            <tr>
                <td class="text-nowrap fw-bolder">
                    <span class="me-1">Administrator Access</span>
                    <span data-toggle="tooltip" title="Grants full access to the system">
                        <i class="bi bi-info-circle"></i>
                    </span>
                </td>
                <td>
                    <div class="form-check mx-5">
                        <input class="form-check-input select-all" type="checkbox" id="select-all" />
                        <label class="form-check-label" for="selectAll"> Select All </label>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <h4 class="fw-bolder text-center mt-2">Programme Management</h4>
                </td>
            </tr>
            @php
                $modules = [
                    'proposal' => 'Grant Proposal', 
                    'budgeting' => 'Budgeting', 
                    'log-frame' => 'Log Frame', 
                    'action-plan' => 'Action Plan', 
                    'agenda' => 'Agenda', 
                    'attendance' => 'Attendance', 
                    'activity-narrative' => 'Activity Narrative', 
                    'case-study' => 'Case Study',
                ];
            @endphp
            @foreach ($modules as $key => $module)
                <tr>
                    <td class="text-nowrap fw-bolder">{{ $module }}</td>
                    <td>
                        <div class="d-flex">
                            <div class="form-check mx-5">
                                {!! Form::checkbox('permissions[]', 'create-' . $key, false,
                                [ 'class' => 'form-check-input perm', 'id' => 'create-' . $key]); !!}
                                <label class="form-check-label" for="create"> Create </label>
                            </div>
                            <div class="form-check mx-5">
                                {!! Form::checkbox('permissions[]', 'edit-' . $key, false,
                                [ 'class' => 'form-check-input perm', 'id'=> 'edit-' . $key]); !!}
                                <label class="form-check-label" for="edit"> Edit </label>
                            </div>
                            <div class="form-check mx-5">
                                {!! Form::checkbox('permissions[]', 'delete-' . $key, false,
                                [ 'class' => 'form-check-input perm', 'id'=> 'delete-' . $key]); !!}
                                <label class="form-check-label" for="delete"> Delete </label>
                            </div>
                            <div class="form-check mx-5">
                                {!! Form::checkbox('permissions[]', 'view-' . $key, false,
                                [ 'class' => 'form-check-input perm', 'id'=> 'view-' . $key]); !!}
                                <label class="form-check-label" for="view"> View </label>
                            </div>
                            @if (in_array($key, ['proposal', 'budgeting', 'action-plan', 'agenda', 'activity-narrative']))
                                <div class="form-check mx-5">
                                    {!! Form::checkbox('permissions[]', 'approve-' . $key, false,
                                    [ 'class' => 'form-check-input perm', 'id'=> 'approve-' . $key]); !!}
                                    <label class="form-check-label" for="approve"> Approve </label>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2">
                    <h4 class="fw-bolder text-center mt-2">Account Settings</h4>
                </td>
            </tr>
            @php
                $modules = [
                    'donor' => 'Donor', 
                    'programme' => 'Key-Programme', 
                    'region' => 'Target Region', 
                    'cohort' => 'Target Cohort', 
                    'age-group' => 'Age Group', 
                    'disability' => 'Disability', 
                    'deadline' => 'Deadline Management',
                    'code-prefix' => 'Code Prefixes',
                    'role' => 'Roles & Rights', 
                    'user' => 'User Management',
                ];
            @endphp
            @foreach ($modules as $key => $module)
                <tr>
                    <td class="text-nowrap fw-bolder">{{ $module }}</td>
                    <td>
                        <div class="d-flex">
                            @if ($key == 'code-prefix')
                                <div class="form-check mx-5">
                                    {!! Form::checkbox('permissions[]', 'create-' . $key, false,
                                    [ 'class' => 'form-check-input perm', 'disabled' => 'disabled', 'id' => 'create-' . $key]); !!}
                                    <label class="form-check-label" for="create"> Create </label>
                                </div>    
                            @else
                                <div class="form-check mx-5">
                                    {!! Form::checkbox('permissions[]', 'create-' . $key, false,
                                    [ 'class' => 'form-check-input perm', 'id' => 'create-' . $key]); !!}
                                    <label class="form-check-label" for="create"> Create </label>
                                </div>

                            @endif
                            <div class="form-check mx-5">
                                {!! Form::checkbox('permissions[]', 'edit-' . $key, false,
                                [ 'class' => 'form-check-input perm', 'id'=> 'edit-' . $key]); !!}
                                <label class="form-check-label" for="edit"> Edit </label>
                            </div>
                            @if ($key == 'code-prefix')
                                <div class="form-check mx-5">
                                    {!! Form::checkbox('permissions[]', 'delete-' . $key, false,
                                    [ 'class' => 'form-check-input perm', 'disabled' => 'disabled' ,'id'=> 'delete-' . $key]); !!}
                                    <label class="form-check-label" for="delete"> Delete </label>
                                </div>
                            @else
                                <div class="form-check mx-5">
                                    {!! Form::checkbox('permissions[]', 'delete-' . $key, false,
                                    [ 'class' => 'form-check-input perm', 'id'=> 'delete-' . $key]); !!}
                                    <label class="form-check-label" for="delete"> Delete </label>
                                </div>
                            @endif
                            <div class="form-check mx-5">
                                {!! Form::checkbox('permissions[]', 'view-' . $key, false,
                                [ 'class' => 'form-check-input perm', 'id'=> 'view-' . $key]); !!}
                                <label class="form-check-label" for="view"> View </label>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>    
    </table>
</div>   


@section('script')
<script>
    $('#select-all').click(function() {
        if ($(this).prop('checked')) {
            $('.perm').prop('checked', true);
        } else {
            $('.perm').prop('checked', false);
        }
    });
    
    const permissions = @json(@$role->permissions);
    if (permissions) {
        $('.perm').each(function() {
            const id = $(this).attr('id');
            for (let i = 0; i < permissions.length; i++) {
                const el = permissions[i];
                if (el.name == id) {
                    $(this).prop('checked', true);
                    break;
                }
            }
        });
        if ($('.perm').length == permissions.length) {
            $('#select-all').prop('checked', true);
        }
    }
</script>
@endsection

