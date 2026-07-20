<style type="text/css">
	.select-w {
		width: 100%;
	}
	tfoot td {
		border: none !important;
	}
	.cursor-pointer {
		cursor: pointer;
	}
</style>

<div class="row g-3 align-items-center bg-light pb-3 mb-4">
	<div class="col-md-2">
		<label for="customer" class="col-form-label text-danger">Customer Name*</label>
	</div>
	<div class="col-md-4">
		{{ Form::hidden('customer_name', null, ['id' => 'customerName']) }}
		<select name="customer_id" id="customer" class="form-select select-w" data-placeholder="Search Customer">
			<option></option>
			@isset ($invoice)
				<option value="{{ $invoice->customer_id }}" selected>
					{{ $invoice->customer_name }}
				</option>
			@endisset
		</select>
	</div>
	<div class="col-md-6">
		<div class="row g-3 align-items-center bg-light">
			<div class="col-md-2">
				<label for="currencyCode" class="col-form-label">Currency</label>
			</div>
			<div class="col-md-4">
				{{ Form::text('currency_code', null, ['class' => 'form-control bg-light', 'id' => 'currencyCode', 'readonly' => 'readonly']) }}
				{{ Form::hidden('currency_id', null, ['id' => 'currencyId']) }}
				{{ Form::hidden('currency_rate', null, ['id' => 'currencyRate']) }}
			</div>
		</div>		
	</div>
</div>

<div class="row g-3 align-items-center mb-4">
	<div class="col-md-2">
		<label for="invoiceDate" class="col-form-label text-danger">Invoice Date</label>
	</div>
	<div class="col-md-4">
		{{ Form::date('date', @$invoice->date ?: date('Y-m-d'), ['class' => 'form-control', 'id' => 'date']) }}
	</div>
	<div class="col-md-6">
		<div class="row g-3 align-items-center ps-2">
			<div class="col-md-2">
				<label for="terms" class="col-form-label">Terms</label>
			</div>
			<div class="col-md-4">
				{{ Form::hidden('payment_terms_label', null, ['id' => 'paymentTermsLabel']) }}
				{{ Form::hidden('payment_terms', null, ['id' => 'paymentTerms']) }}
				<select name="payment_terms_id" id="terms" class="form-select" required></select>				
			</div>
		</div>
	</div>
</div>

<div class="row g-3 align-items-center mb-4">
	<div class="col-md-2">
		<label for="dueDate" class="col-form-label">Due Date</label>
	</div>
	<div class="col-md-4">
		{{ Form::date('due_date', @$invoice->due_date, ['class' => 'form-control', 'id' => 'duedate']) }}
	</div>
</div>

<div class="row g-3 align-items-center mb-4">
	<div class="col-md-2">
		<label for="orderNo" class="col-form-label">Order Number</label>
	</div>
	<div class="col-md-4">
		{{ Form::text('order_no', null, ['class' => 'form-control', 'id' => 'orderNo']) }}
	</div>
</div>
<hr>
<div class="row g-3 align-items-center mb-3">
	<div class="col-md-2">
		<label for="salesPerson" class="col-form-label">Sales Person</label>
	</div>
	<div class="col-md-4">
		{{ Form::hidden('salesperson_name', null, ['id' => 'salesPersonName']) }}
		{{ Form::hidden('salesperson_email', null, ['id' => 'salesPersonEmail']) }}
		<select name="salesperson_id" id="salesPerson" class="form-control select-w" data-placeholder="Search Sales Person">
			<option></option>
			@isset ($invoice)
				<option value="{{ $invoice->salesperson_id }}" selected>
					{{ $invoice->salesperson_name }} {{ $invoice->salesperson_email? '('.$invoice->salesperson_email.')' : '' }}
				</option>
			@endisset
		</select>				
	</div>
</div>
<div class="row g-3 align-items-center mb-3">
	<div class="col-md-6">
		<div class="row g-3 align-items-center ps-2">
			<div class="col-md-4">
				<label for="terms" class="col-form-label">Loyalty Qualifier</label>
			</div>
			<div class="col-md-8">
				@php
					$qualifiers = [
						"Other treatments",
						"Braces / Cosmetic treatment",
						"Referral of a new patient",
						"Adhering to 6-month hygiene recall",
						"Free dental kit",
						"Cleaning / Fluoride",
						"Whitening / Night guard",
						"Free cleaning / Child consultation",
						"Orthodontic / Cosmetic treatment",
					];
				@endphp
				<select name="cf_loyalty_points_qualifier" id="loyalty-quailifier" class="form-select">
					<option value="">-- Select Loyalty Points Qualifier --</option>
					@foreach ($qualifiers as $value)
						<option value="{{ $value }}" {{ @$invoice->cf_loyalty_points_qualifier === $value? 'selected' : '' }}>
							{{ $value }}
						</option>
					@endforeach
				</select>				
			</div>
		</div>
	</div>	
	<div class="col-md-6">
		<div class="row g-3 align-items-center ps-2">
			<div class="col-md-4">
				<div class="form-check">
				 	<input class="form-check-input" type="checkbox" name="cf_redeem_points" 
				 		value="1" id="redeem-points" @isset($invoice->cf_redeem_points) checked @endisset>
				    <label class="form-check-label" for="redeemPoints">Redeem Points</label>				  
				</div>
			</div>
		</div>
	</div>	
</div>
<div class="row g-3 align-items-center mb-4 mt-3">
	<div class="col-md-2">
		<label for="description" class="col-form-label">Subject</label>
	</div>
	<div class="col-md-4">
		{{ Form::textarea('description', null, ['class' => 'form-control', 'id' => 'description', 'rows' => '1', 'placeholder' => 'What is this invoice for']) }}
	</div>
</div>
<hr>

<!-- Item Table -->
<div class="row mt-3">
	<div class="col-md-12">
		<div class="mb-2">
			<i class="bi bi-info-circle-fill"></i>
			<i>Service Items with replica names of composite items will adjust stock levels on posting invoice. 
			<br>Components of the composite items will be implicitly reduced via inventory adjustment.</i>
		</div>

		<div class="row g-3 align-items-center mt-2 mb-4">
			<div class="col-md-2">
				<label for="location" class="col-form-label text-danger">Location</label>
			</div>
			<div class="col-md-4">
				{{ Form::hidden('location_name', null, ['id' => 'locationName']) }}
				<select name="location_id" id="location" class="form-control select-w" data-placeholder="Search Location or Warehouse">
					<option></option>
					@isset ($invoice->location_name)
					<option value="{{ $invoice->location_id }}" selected>{{ $invoice->location_name }}</option>
					@endisset
				</select>
			</div>
		</div>	

		<table class="table table-bordered" id="itemTbl">
			<thead>
				<tr>
					<th width="40%">ITEM DETAILS</th>
					<th class="text-end">QTY</th>
					<th class="text-end">RATE</th>
					<th class="text-end">Amount</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr class="header-row" style="display: none;">
					<td colspan="4"><input type="text" name="name[]" class="form-control"></td>
					<td><span class="cursor-pointer del"><i class="bi bi-x-circle text-danger"></i></span></td>
					<input type="hidden" name="item_id[]">
					<input type="hidden" name="row_index[]" class="row-indx">
					<input type="hidden" name="quantity[]">
					<input type="hidden" name="rate[]">
					<input type="hidden" name="description[]">
					<input type="hidden" name="amount[]">
					<input type="hidden" name="unit[]">
					<input type="hidden" name="tax_id[]" class="tax-id">
					<input type="hidden" name="tax_percentage[]" class="tax-perc">
					<input type="hidden" name="item_tax[]" class="item-tax">
					<input type="hidden" name="product_type[]" class="item-type">
					<input type="hidden" name="sku[]" class="sku">
				</tr>
				<tr class="item-row">
					<td>
						<div class="dropdown">
							<textarea name="name[]" class="form-control dropdown-toggle name" data-bs-toggle="dropdown" aria-expanded="false" rows="2" placeholder="Type or click to select an item"></textarea>
							<ul class="dropdown-menu" style="width: 500px; max-height: 300px; overflow-y: scroll;">
								<li class="ps-2">Type to search an item</li>
							</ul>							
						</div>
					</td>
					<td><input type="text" name="quantity[]" class="form-control qty text-end" value="1.00"></td>
					<td><input type="text" name="rate[]" class="form-control rate text-end" value="0.00"></td>
					<td class="text-end"><h5 class="amount fw-bold" style="margin-top: 0.5rem;">0.00</h5></td>
					<td><span class="cursor-pointer del"><i class="bi bi-x-circle text-danger"></i></span></td>
					<input type="hidden" name="item_id[]" class="item-id">
					<input type="hidden" name="row_index[]" class="row-indx">
					<input type="hidden" name="description[]" class="descr">
					<input type="hidden" name="amount[]" class="amount-inp">
					<input type="hidden" name="unit[]" class="unit">
					<input type="hidden" name="tax_id[]" class="tax-id">
					<input type="hidden" name="tax_percentage[]" class="tax-perc">
					<input type="hidden" name="item_tax[]" class="item-tax">
					<input type="hidden" name="product_type[]" class="item-type">
					<input type="hidden" name="sku[]" class="sku">
				</tr>

				<!-- Edit invoice items -->
				@foreach (@$invoice->items ?: [] as $i => $row)
					@if ($row->item_id)
						<tr class="item-row">
							<td>
								<div class="dropdown">
									<textarea name="name[]" class="form-control dropdown-toggle name" data-bs-toggle="dropdown" aria-expanded="false" rows="2" placeholder="Type or click to select an item">{{ $row->name }}</textarea>
									<ul class="dropdown-menu" style="width: 500px; max-height: 300px; overflow-y: scroll;">
										<li class="ps-2">Type to search an item</li>
									</ul>									
								</div>
							</td>
							<td><input type="text" name="quantity[]" value="{{ +$row->quantity }}" class="form-control qty text-end" value="1.00"></td>
							<td><input type="text" name="rate[]" value="{{ numberFormat($row->rate) }}" class="form-control rate text-end" value="0.00"></td>
							<td class="text-end"><h5 class="amount fw-bold" style="margin-top: 0.5rem;">{{ numberFormat($row->amount) }}</h5></td>
							<td><span class="cursor-pointer del"><i class="bi bi-x-circle text-danger"></i></span></td>
							<input type="hidden" name="item_id[]" value="{{ $row->item_id }}" class="item-id">
							<input type="hidden" name="row_index[]" value="{{ $row->row_index }}" class="row-indx">
							<input type="hidden" name="description[]" value="{{ $row->description }}" class="descr">
							<input type="hidden" name="amount[]" value="{{ $row->amount }}" class="amount-inp">
							<input type="hidden" name="unit[]" value="{{ $row->unit }}" class="unit">
							<input type="hidden" name="tax_id[]" value="{{ $row->tax_id }}" class="tax-id">
							<input type="hidden" name="tax_percentage[]" value="{{ $row->tax_percentage }}" class="tax-perc">
							<input type="hidden" name="item_tax[]" value="{{ $row->item_tax }}" class="item-tax">
							<input type="hidden" name="product_type[]" value="{{ $row->product_type }}" class="item-type">
							<input type="hidden" name="sku[]" value="{{ $row->sku }}" class="sku">
						</tr>
					@else
						<tr class="header-row" style="display: none;">
							<td colspan="4"><input type="text" name="name[]" value="{{ $row->name }}" class="form-control"></td>
							<td><span class="cursor-pointer del"><i class="bi bi-x-circle text-danger"></i></span></td>
							<input type="hidden" name="item_id[]">
							<input type="hidden" name="row_index[]" class="row-indx">
							<input type="hidden" name="quantity[]">
							<input type="hidden" name="rate[]">
							<input type="hidden" name="description[]">
							<input type="hidden" name="amount[]">
							<input type="hidden" name="unit[]">
							<input type="hidden" name="tax_id[]" class="tax-id">
							<input type="hidden" name="tax_percentage[]" class="tax-perc">
							<input type="hidden" name="item_tax[]" class="item-tax">
							<input type="hidden" name="product_type[]" class="item-type">
							<input type="hidden" name="sku[]" class="sku">
						</tr>
					@endif
				@endforeach
			</tbody>

			<tfoot>
				<tr>
					<td>
						<div class="form-group">
							<div class="d-inline">
								<span id="addRow" class="badge bg-primary mr-1 cursor-pointer"><i class="bi bi-plus-circle-fill"></i> Add New Row</span>
								<span id="addHeader" class="badge bg-success cursor-pointer"><i class="bi bi-plus-circle-fill"></i> Add New Header</span>
							</div>
						</div>
						<div class="form-group mt-2">
							<div><label for="notes" class="col-form-label">Customer Notes</label></div>
							{{ Form::textarea('notes', null, ['class' => 'form-control', 'id' => 'notes', 'rows' => '2', 'placeholder' => 'Thanks for your business']) }}
						</div>
					</td>
					<td colspan="3">
						<div class="p-2 mt-1 bg-light">
							<input type="hidden" name="taxable" value="0">
							<input type="hidden" name="tax" value="0">
							<div class="row g-3 align-items-center mt-1">
								<div class="col-md-6">
									<h5 class="text-end fw-bold">Subtotal</h5>
								</div>
								<div class="col-md-6">
									<h5 class="subtotal text-end">0.00</h5>
									<input type="hidden" name="subtotal" id="subtotal">
								</div>
							</div>
							<hr class="m-1">
							<div class="row g-3 align-items-center mt-1">
								<div class="col-md-6">
									<h5 class="text-end fw-bold">Total</h5>
								</div>
								<div class="col-md-6">
									<h5 class="total text-end">0.00</h5>
									<input type="hidden" name="total" id="total">
								</div>
							</div>
						</div>
					</td>
					<td></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
<br>

@section('script')
@include('invoices.form_js')
@stop