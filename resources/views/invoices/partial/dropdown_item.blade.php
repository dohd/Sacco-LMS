@foreach ($items as $i => $row)
	<li>
		<a 
			class="dropdown-item" 
			href="javascript:void(0);" 
			item_id="{{ $row->item_id }}" 
			rate="{{ $row->rate }}"
			name="{{ $row->item_name }}"
			descr="{{ $row->description }}"
			item_type="{{ $row->product_type }}"
			sku="{{ $row->sku }}"
			unit="{{ $row->unit }}"
		>
			<div class="d-flex justify-content-between">
				<div class="h5">{{ $row->item_name }}</div>
				@if ($row->item_type == 'inventory' && $row->product_type == 'goods')
					<div>Stock On Hand</div>
				@endif
			</div>
			<div class="d-flex justify-content-between">
				<div>SKU: <b>{{ $row->sku }}</b> &nbsp;Rate: KES {{ numberFormat($row->rate) }}</div>
				@if ($row->item_type == 'inventory' && $row->product_type == 'goods')
					<div>{{ numberFormat($row->stock_on_hand) }} {{ $row->unit }}</div>
				@endif
			</div>
		</a>
	</li>
	@if ($loop->iteration != count($items))
		<li><hr class="dropdown-divider"></li>
	@endif
@endforeach