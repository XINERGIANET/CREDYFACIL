@extends('template.app')

@section('title', 'Pagos')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Pagos</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header d-flex justify-content-between align-items-center">
		<div>
		<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
			<i class="ti ti-plus icon"></i> Crear nuevo
		</button>
		</div>
		<div class="text-center">
			<span class="d-block small">
				Tienes un total de:
			</span>
			<span class="fs-2 fw-bold text-primary">
				S/{{ number_format($total, 2) }}
			</span>
		</div>
	</div>
	
	<div class="card-body border-bottom">
		<form>
			<div class="row">
				@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<input type="text" class="form-control" name="name" value="{{ request()->name }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Método de pago</label>
						<select class="form-select" name="payment_method_id">
							<option value="">Seleccionar</option>
							@foreach($payment_methods as $payment_method)
							<option value="{{ $payment_method->id }}" @if($payment_method->id == request()->payment_method_id) selected @endif>{{ $payment_method->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Asesor comercial</label>
						<select class="form-select" name="seller_id">
							<option value="">Seleccionar</option>
							@foreach($sellers as $seller)
							<option value="{{ $seller->id }}" @if($seller->id == request()->seller_id) selected @endif>{{ $seller->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				@endif
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Fecha inicial</label>
						<input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Fecha final</label>
						<input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
					</div>
				</div>
			</div>
			<button class="btn btn-primary">Filtrar</button>
			<a href="{{ route('payments.index') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente</th>
					<th>Número de cuota</th>
					<th>Monto</th>
					<th>Método de pago</th>
					<th>Fecha de pago</th>
					<th>Días de mora</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($payments->count() > 0)
				@foreach($payments as $payment)
				@php
				$contract = optional(optional($payment->quota)->contract);
				@endphp
				<tr>
					<td>
						{{ $contract->client().' - S/'.number_format($contract->requested_amount, 2).' - '.optional($contract->date)->format('d/m/Y') }}
					</td>
					<td>{{ optional($payment->quota)->number }}</td>
					<td>{{ $payment->amount }}</td>
					<td>{{ optional($payment->payment_method)->name }}</td>
					<td>{{ $payment->date->format('d/m/Y') }}</td>
					<td>{{ $payment->due_days }}</td>
					<td>
						<div class="d-flex gap-2">
							<div class="d-flex gap-2">
								@if($payment->people)
								<button class="btn btn-primary btn-icon" title="{!! $payment->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">
									<i class="ti ti-eye icon"></i>
								</button>
								@endif
								
								@if(auth()->user()->hasRole('admin'))
								<button class="btn btn-primary btn-icon btn-edit " data-id="{{ $payment->id }}">
									<i class="ti ti-pencil icon"></i>
								</button>
								<button class="btn btn-danger btn-icon btn-delete" data-id="{{ $payment->id }}">
									<i class="ti ti-x icon"></i>
								</button>
								@endif

								@if($payment->image)
								<a class="btn btn-primary btn-icon" href="{{ asset('storage/'.$payment->image) }}" target="_blank">
									<i class="ti ti-photo icon"></i>
								</a>
								@endif
							</div>
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="8" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($payments->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $payments->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="storeForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Crear nuevo</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="row">
  			  	<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Cliente/Grupo</label>
  			  			<select class="form-select ts-contracts" name="contract_id" id="contract_id">
  			  				<option value="">Seleccionar</option>
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Cuota</label>
  			  			<select class="form-select" name="quota_id" id="quota_id">
  			  				<option value="">Seleccionar</option>
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Monto</label>
  			  			<input type="text" class="form-control" name="amount" id="amount">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Método de pago</label>
  			  			<select class="form-select" name="payment_method_id" id="payment_method_id">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Fecha</label>
  			  			@if(auth()->user()->hasRole('admin'))
  			  			<input type="date" class="form-control" name="date" id="date" value="{{ now()->format('Y-m-d') }}">
  			  			@else
  			  			<input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" disabled>
  			  			<input type="hidden" name="date" id="date" value="{{ now()->format('Y-m-d') }}">
  			  			@endif
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label">Imagen</label>
  			  			<input type="file" class="form-control" name="image" id="image" accept=".jpg,.jpeg,.png,.webp">
  			  		</div>
  			  	</div>
  			  </div>
  			  <div class="mb-3">
  			  	<label class="form-label">Lista de personas</label>
  			  	<div id="divPeople"></div>
  			  </div>

  			</div>
  			<div class="modal-footer">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  			  <button type="submit" class="btn btn-primary" id="btn-save"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="editForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Editar</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="row">
  			  	<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Cliente/Grupo</label>
  			  			<input type="text" class="form-control" disabled id="editClient">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Cuota</label>
  			  			<input type="text" class="form-control" disabled id="editQuota">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Monto</label>
  			  			<input type="text" class="form-control" disabled id="editAmount">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Método de pago</label>
  			  			<select class="form-select" name="payment_method_id" id="editPaymentMethodId">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Fecha</label>
  			  			<input type="text" class="form-control" disabled id="editDate">
  			  		</div>
  			  	</div>
  			  </div>
  			</div>
  			<div class="modal-footer">
  				<input type="hidden" id="editId">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  			  <button type="submit" class="btn btn-primary" id="btn-save"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>


@endsection

@section('scripts')
<script>

	$(document).ready(function(){

		new TomSelect('.ts-contracts', {
			valueField: 'id',
			labelField: ['name', 'group_name'],
			searchField: ['name', 'group_name'],
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
			optionClass:'dropdown-item',
			load: function(query, callback){
				$.ajax({
					url: '{{ route('contracts.api') }}?q=' + encodeURIComponent(query),
					method: 'GET',
					success: function(data){
						callback(data.items);
					},
					error: function(err){
						console.log(err);
					}
				})
			},
			render: {
				item: function(data, escape) {
					return `<div>${ data.client_type == 'Personal' ? escape(data.name) : escape(data.group_name) } - S/${escape(data.requested_amount)} - ${escape(data.date)}</div>`;
				},
				option: function(data, escape) {
					return `<div>${ data.client_type == 'Personal' ? escape(data.name) : escape(data.group_name) } - S/${escape(data.requested_amount)} - ${escape(data.date)}</div>`;
				},
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			},
			onItemAdd: function(value, $item){

				getQuotas(value);

			}
		});

	});

	function getQuotas(contract_id){
		$.ajax({
			url: '{{ route('quotas.api') }}?contract_id=' + contract_id,
			method: 'GET',
			success: function(data){
				var html = '';

				data.quotas.forEach(function(quota){
					html += `<option value="${quota.id}">Cuota ${quota.number} - Monto ${quota.amount} - Saldo: ${quota.debt} - Fecha: ${quota.date}</option>`;
				});

				$('#quota_id').html(html);

				getPeople(data.contract);

			}

		});
	}

	function getPeople(contract){
		var people = JSON.parse(contract.people);
		var html = '';
		if(people != null && people.length > 0){
			people.forEach(function(client){
				html += `
			  	<div class="form-check">
						<input class="form-check-input" type="checkbox" name="people[]" value="${client.document}">
						<span class="form-check-label">${client.name}</span>
					</div>
				`;
			});
		}

		$('#divPeople').html(html);

	}

	$('#storeForm').submit(function(e){
		$('#btn-save').prop('disabled', true);

		e.preventDefault();

		var fd = new FormData();

		fd.append('contract_id', $('#contract_id').val());
		fd.append('quota_id', $('#quota_id').val());
		fd.append('amount', $('#amount').val());
		fd.append('payment_method_id', $('#payment_method_id').val());
		fd.append('date', $('#date').val());
		fd.append('image', $('#image')[0].files[0]);

	$('input[type="checkbox"][name="people[]"]').each(function() {
		if(this.checked) {
			fd.append($(this).attr('name'), $(this).val());
		}
	});
		

		$.ajax({
			url: '{{ route('payments.store') }}',
			method: 'POST',
			processData: false,
			contentType: false,
			data: fd,
			success: function(data){
				if(data.status){
					$('#createModal').modal('hide');
					$('#storeForm')[0].reset();
					
					ToastMessage.fire({ text: 'Registro guardado' })
						.then(() => location.reload());

				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
					$('#btn-save').prop('disabled', false);
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
				$('#btn-save').prop('disabled', false);
			}
		});

	});

	$(document).on('click', '.btn-edit', function(){

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('payments.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editClient').val(data.client);
				$('#editQuota').val(`Cuota ${data.quota.number} - Monto ${data.quota.amount}`);
				$('#editAmount').val(data.amount);
				$('#editPaymentMethodId').val(data.payment_method_id);
				$('#editDate').val(data.date);				
				$('#editId').val(data.id);
				$('#editModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$('#editForm').submit(function(e){
		e.preventDefault();

		var id = $('#editId').val();

		$.ajax({
			url: '{{ route('payments.index') }}' + '/' + id + '',
			method: 'PATCH',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#editModal').modal('hide');
					$('#editForm')[0].reset();
					
					ToastMessage.fire({ text: 'Registro actualizado' })
						.then(() => location.reload());

				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-delete', function(){

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro que deseas borrar el registro?',
		}).then((result) => {
			if(result.isConfirmed){
				$.ajax({
					url: '{{ route('payments.index') }}' + '/' + id,
					method: 'DELETE',
					success: function(data){
						ToastMessage.fire({ text: 'Registro eliminado' })
							.then(() => location.reload());
					},
					error: function(err){
						ToastError.fire({ text: 'Ocurrió un error' });
					}
				});
			}
		});

	});

</script>
@endsection