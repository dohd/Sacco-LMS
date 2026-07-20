<script>
	const config = {
		customerSelect2: {
			allowClear: true,
			ajax: {
                url: "{{ route('invoices.search_contacts') }}",
                dataType: 'json',
                delay: 250,
                type: 'GET',
                data: ({term}) => ({
                	contact_type: 'customer',
                	contact_name_contains: term,
                }),            
                processResults: response => {
                    return { 
                    	results: (response.contacts || []).map(v => ({
                    		id: v.contact_id,
                    		text: v.contact_name, 
                    		name: v.contact_name, 
                    	})) 
                    }
                },
            }
		},
		salesPersonSelect2: {
			allowClear: true,
			ajax: {
                url: "{{ route('invoices.search_salespersons') }}",
                dataType: 'json',
                delay: 250,
                type: 'GET',
                data: ({term}) => ({
                	salesperson_name_contains: term,
                }),            
                processResults: response => {
                    return { 
                    	results: (response.data || []).map(v => ({
                    		id: v.salesperson_id,
                    		text: v.salesperson_name + (v.salesperson_email? ` (${v.salesperson_email})` : ''),
                    		email: v.salesperson_email,
                    		name: v.salesperson_name,
                    	})) 
                    }
                },
            }
		},
		locationSelect2: {
			allowClear: true,
			ajax: {
                url: "{{ route('invoices.itemlocations') }}",
                dataType: 'json',
                delay: 250,
                type: 'GET',
                data: ({term}) => ({
                	location_name_contains: term,
                }),            
                processResults: response => {
                	let results = [];
                	if (response.locations) {
                		results = (response.locations || []).map(v => ({
                    		id: v.location_id,
                    		text: v.location_identification_number? 
                    			`${v.location_identification_number} - ${v.location_name}`:
                    			v.location_name,
                    	}));
                	}
                	if (response.warehouses) {
                		results = (response.warehouses || []).map(v => ({
                    		id: v.warehouse_id,
                    		text: v.warehouse_name,	                    			
                    	}));
                	}

                	return {results};
                },
            }
		},
	};

	const Form = {
		invoice: @json(@$invoice),
		updateUrl: '',

		headerRowHtml: $('#itemTbl .header-row:first').clone(),
		itemRowHtml: $('#itemTbl .item-row:first').clone(),
		itemSpinner: '<li>{!! spinner() !!}</li>',
		btnSpinner: '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>',

		init() {
			$('#customer').select2(config.customerSelect2);
			$('#salesPerson').select2(config.salesPersonSelect2);
			$('#location').select2(config.locationSelect2);

			$('form').submit(Form.onSubmit);
			Form.loadPaymentTerms();
			Form.loadCurrencies();

			$('#customer').change(Form.onChangeCustomer);
			$('#date, #paymentTerms').change(Form.updateDuedate);
			$('#terms').change(Form.onChangeTerms);
			$('#salesPerson').change(Form.onChangeSalesPerson);
			$('#location').change(Form.onChangeLocation);
			$('#addRow').click(Form.onClickAddRow);
			$('#addHeader').click(Form.onClickAddHeader);
			$('#itemTbl').on('click', '.del', Form.onClickDelete);
			$('#itemTbl').on('keyup', '.qty, .rate', Form.onKeyQtyRate);
			$('#itemTbl').on('click', '.dropdown-item', Form.onClickItem);
			
			$('#itemTbl').on('keyup', '.name', Form.debounce(Form.onKeyUpName, 300));
			$('#itemTbl').on('click', '.name', Form.onKeyUpName);
			$('#itemTbl').on('keydown', '.name', Form.onKeyDownName);

			// Edit Mode
			const invoice = @json(@$invoice);
			if (invoice && invoice.id) {
				@isset($invoice)
				Form.updateUrl = "{{ route('invoices.update', $invoice) }}";
				@endisset
				$('#itemTbl tbody tr:eq(1)').remove(); 
				$('#itemTbl .rate:last').keyup();
				Form.updateRowIndx();
			}
		},

		onSubmit(e) {
			e.preventDefault();
			$('#submitBtn').attr('disabled', true).html(`Submit ${Form.btnSpinner}`);
			const formData = new FormData(this);

			$.ajax({
			    url: Form.updateUrl? Form.updateUrl :  "{{ route('invoices.store') }}",
			    type: 'POST',
			    data: formData,
			    contentType: false,
			    processData: false,
			    success: function(response) {
			      $('#submitBtn').attr('disabled', false).html('Submit');
			      flashMessage(response);			      
			    },
			    error: function(xhr,status,err) {
			      // console.error('Upload failed:', xhr.responseText, err);
			      $('#submitBtn').attr('disabled', false).html('Submit');
			      flashMessage(xhr);
			    }
			});
		},

		updateDuedate() {
			// update due-date
			$.get("{{ route('invoices.get_duedate') }}", {
				date: $('#date').val(),
				terms: $('#paymentTerms').val(),
			})
			.then(resp => {
				if (resp.duedate) {
					$('#duedate').val(resp.duedate);
				}
			})
			.fail((xhr,status,err) => {
				flashMessage({status: 'error', message: 'Error updating Due Date'});
			});
		},

		loadCurrencies() {
			$.get("{{ route('invoices.currencies') }}")
			.then(resp => {
				if (resp.currencies && resp.currencies.length) {
					const currencies = resp.currencies;
					currencies.forEach(v => {
						if (v.is_base_currency) {
							$('#currencyId').val(v.currency_id);
							$('#currencyCode').val(v.currency_code);
							$('#currencyRate').val(v.exchange_rate);
						}
					});
				}
			})
			.fail((xhr,status,err) => {
				console.log(err)
			});			
		},

		loadPaymentTerms() {
			$.get("{{ route('invoices.paymentterms') }}")
			.then(resp => {
				if (resp.data && resp.data.payment_terms.length) {
					const terms = resp.data.payment_terms;
					$('#terms').html('');
					terms.forEach(term => {
						const optionEl = `
							<option 
								value="${term.payment_terms_id}"
								terms="${term.payment_terms}"
								label="${term.payment_terms_label}"
							>
								${term.payment_terms_label}
							</option>`;
						$('#terms').append(optionEl);
					});

					// Due on Receipt Term
					$(`#terms option[terms="0"]`).attr('selected', true);
					if (Form.invoice && Form.invoice.id) {
						$(`#terms option[terms="${Form.invoice.payment_terms}"]`).attr('selected', true);
					}
					$('#terms').change();
				}
			})
			.fail((xhr,status,err) => {
				console.log(err)
			});			
		},

		onChangeLocation() {
			const data = $(this).select2('data')[0];
			$('#locationName').val(data.text);
		},

		onChangeCustomer() {
			let data = $(this).select2('data');
			if (data.length) {
				$('#customerName').val(data[0]['name']);
			}
		},

		onChangeSalesPerson() {
			let data = $(this).select2('data');
			if (data.length) {
				$('#salesPersonName').val(data[0]['name']);
				$('#salesPersonEmail').val(data[0]['email']);
			}
		},

		onChangeTerms() {
			const opt = $(this).find(':selected');
			$('#paymentTermsLabel').val(opt.attr('label'));
			$('#paymentTerms').val(opt.attr('terms')).change();
		},

		onClickItem() {
			const rate = accounting.unformat($(this).attr('rate'));
			const tr = $(this).parents('tr:first');
			tr.find('.rate').val(accounting.formatNumber(rate)).keyup();
			tr.find('.item-id').val($(this).attr('item_id'));
			tr.find('.name').val($(this).attr('name'));
			tr.find('.descr').val($(this).attr('descr'));
			tr.find('.sku').val($(this).attr('sku'));
			tr.find('.item-type').val($(this).attr('item_type'));	
			tr.find('.unit').val($(this).attr('unit'));	
		},

		onClickAddRow() {
			const row = Form.itemRowHtml.clone();
			$('#itemTbl tbody').append(row);
			Form.updateRowIndx();
		},
		onClickAddHeader() {
			const row = Form.headerRowHtml.clone();
			row.removeAttr('style');
			$('#itemTbl tbody').append(row);
			Form.updateRowIndx();
		},
		onClickDelete() {
			$(this).parents('tr:first').remove();
			Form.updateRowIndx();
		},
		updateRowIndx() {
			$('#itemTbl tbody tr').each(function() {
				$(this).find('.row-indx').val($(this).index());
			});
		},

		debounce(fn, delay) {
			let timer;
			return function(...args) {
			    clearTimeout(timer);
			    timer = setTimeout(() => fn.apply(this, args), delay);
			};
		},

		onKeyDownName() {
		  const dropdown = bootstrap.Dropdown.getOrCreateInstance(this);
		  const menu = $(this).siblings('.dropdown-menu');
		  // Only show if it's currently hidden
		  if (!menu.hasClass('show')) {
		    dropdown.show();
		  }
		},

		currentNameSearchReq: null,
		onKeyUpName() {
			const dropdown = $(this).next();
			dropdown.html(Form.itemSpinner);

			// Abort previous request if still running
			if (Form.currentNameSearchReq && Form.currentNameSearchReq.readyState !== 4) {
				Form.currentNameSearchReq.abort();
			}

			Form.currentNameSearchReq = $.ajax({
			    url: "{{ route('invoices.search_items') }}",
			    data: {
			      name_contains: $(this).val(),
			      filter_by: 'Status.Active',
			      per_page: 6,
			    },
			    success: resp => {
			    	return resp? dropdown.html(resp) : dropdown.html('<li class="text-danger ps-2">Item could not be found ...<li>');
			    },
			    error: (xhr, status, err) => {
			      if (status === 'abort') {
			        // Friendly handling for canceled requests
			        dropdown.html('<li class="text-muted ps-2">Searching…</li>');
			      } else {
			        dropdown.html('<li class="text-danger ps-2">Error loading data</li>');
			        console.error(err);
			      }
			    }
			});
		},

		onKeyQtyRate() {
			const tr = $(this).parents('tr:first');
			const qty = accounting.unformat(tr.find('.qty').val());
			const rate = accounting.unformat(tr.find('.rate').val());
			const amount = qty * rate;
			tr.find('.amount').html(accounting.formatNumber(amount));
			tr.find('.amount-inp').val(accounting.formatNumber(amount));
			Form.computeTotals();
		},

		computeTotals() {
			let taxable = 0;
			let tax = 0;
			let subtotal = 0;
			let total = 0;
			$('#itemTbl .amount').each(function() {
				const amount = accounting.unformat($(this).html());
				subtotal += amount;
				total += amount;
			});
			$('.subtotal').html(accounting.formatNumber(subtotal));
			$('.total').html(accounting.formatNumber(total));
			$('#subtotal').val(accounting.formatNumber(subtotal));
			$('#total').val(accounting.formatNumber(total));
		},
	}

	$(Form.init);
</script>