<div class="row mb-3">
    <label for="full_name" class="col-md-2">First Name<span class="text-danger">*</span></label>
    <div class="col-md-6 col-12">
        {{ Form::text('fname', null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
</div>
<div class="row mb-3">
    <label for="name" class="col-md-2">Last Name<span class="text-danger">*</span></label>
    <div class="col-md-6 col-12">
        {{ Form::text('lname', null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
</div>
<div class="row mb-3">
    <label for="email" class="col-md-2">Email<span class="text-danger">*</span></label>
    <div class="col-md-6 col-12">
        {{ Form::text('email', null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
</div>
<div class="row mb-3">
    <label for="phone" class="col-md-2">Telephone<span class="text-danger">*</span></label>
    <div class="col-md-6 col-12">
        {{ Form::text('phone', null, ['class' => 'form-control', 'required' => 'required']) }}
    </div>
</div>
