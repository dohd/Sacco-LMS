@foreach ($employeeCols as $row)
	<div class="form-group row mb-2">
		@foreach ($row as $cell)
			<div class="col-md-3">
				@if ($cell == 'payroll_no')
					<label for="{{ $cell }}">PF No.</label>
					{{ Form::text($cell, null, ['class' => 'form-control', 'id' => $cell]) }}
				@elseif ($cell == 'salutation')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						@foreach (config('employee_vars.salutation') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->salutation? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>
				@elseif ($cell == 'gender')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Gender --</option>
						@foreach (['Male', 'Female'] as $item)
							<option value="{{ $item }}" {{ $item == @$employee->gender? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'blood_group')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Blood Group --</option>
						@foreach (config('employee_vars.blood_type') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->blood_group? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'marital')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						@foreach (['M', 'S', 'D'] as $item)
							<option value="{{ $item }}" {{ $item == @$employee->gender? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>
				@elseif ($cell == 'ethnicity')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Ethnicity --</option>
						@foreach (config('employee_vars.ethnicity') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->ethnicity? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>
				@elseif ($cell == 'religion')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						@foreach (config('employee_vars.religion') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->religion? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>
				@elseif ($cell == 'education_peak')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Education Level --</option>
						@foreach (config('employee_vars.education_level') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->education_peak? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'home_county')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Home County --</option>
						@foreach (config('employee_vars.county') as $i => $item)
							<option value="{{ $item }}" {{ $item == @$employee->home_county? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'disability')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Disability --</option>
						@foreach (["Physical", 'Visual', 'Hearing'] as $item)
							<option value="{{ $item }}" {{ $item == @$employee->disability? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'job_desig')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Job Designation --</option>
						@foreach (config('employee_vars.designation') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->job_desig? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>
				@elseif ($cell == 'job_group')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Job Group --</option>
						@foreach (config('employee_vars.job_group') as $item)
							<option value="{{ $item }}" {{ $item == @$employee->job_group? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'engagement_type')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Engagement Type --</option>
						@foreach (['Temporary', 'Permanent', 'Pensionable', 'Contract'] as $item)
							<option value="{{ $item }}" {{ $item == @$employee->engagement_type? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>		
				@elseif ($cell == 'work_county')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						@foreach (config('employee_vars.county') as $i => $item)
							<option value="{{ $item }}" {{ $item == @$employee->work_county? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>	
				@elseif ($cell == 'bank')
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					<select name="{{ $cell }}" id="{{ $cell }}" class="form-select">
						<option value="">-- Bank --</option>
						@foreach (config('employee_vars.bank') as $i => $item)
							<option value="{{ $item }}" {{ $item == @$employee->bank? 'selected' : '' }}>{{ $item }}</option>
						@endforeach
					</select>				
				@elseif (strpos($cell, 'date') !== false)
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					{{ Form::date($cell, null, ['class' => 'form-control', 'id' => $cell, 'placeholder' => 'YYYYMMDD']) }}
				@else
					<label for="{{ $cell }}">{{ str_replace('_', ' ', ucfirst($cell))  }}</label>
					{{ Form::text($cell, null, ['class' => 'form-control']) }}
				@endif
			</div>
		@endforeach
	</div>
@endforeach

@section('script')
<script>
    
</script>    
@stop