@extends('template.app')

@section('title', 'Traslados')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Traslados</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header">
		<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
			<i class="ti ti-plus icon"></i> Crear nuevo
		</button>
	</div>
	<div class="card-body border-bottom">
		<form>
			<div class="row">
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Tipo</label>
						<select class="form-select" name="type">
							<option value="">Seleccionar</option>
							<option value="seller" @if(request()->type == 'seller') selected @endif>Asesor</option>
							<option value="payment_method" @if(request()->type == 'payment_method') selected @endif>Método de pago</option>
						</select>
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Asesor comercial</label>
						<select class="form-select" name="seller_id">
							<option value="">Seleccionar</option>
							@foreach($sellers as $seller)
							<option value="{{ $seller->id }}" @if($seller->id == request()->seller_id) selected @endif >{{ $seller->name }}</option>
							@endforeach
						</select>
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
			<a href="{{ route('transfers.index') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Tipo</th>
					<th>Origen</th>
					<th>Destino</th>
					<th>Monto</th>
					<th>Motivo</th>
					<th>Fecha</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($transfers->count() > 0)
				@foreach($transfers as $transfer)
				<tr>
					<td>{{ $transfer->type() }}</td>
					<td>{{ optional($transfer->from())->name }}</td>
					<td>{{ optional($transfer->to())->name }}</td>
					<td>{{ $transfer->amount }}</td>
					<td>{{ $transfer->reason ?? 'Sin Motivo'}}</td>
					<td>{{ $transfer->date->format('d/m/Y') }}</td>
					<td>
						<div class="d-flex gap-2">
							<button class="btn btn-primary btn-icon btn-edit " data-id="{{ $transfer->id }}">
								<i class="ti ti-pencil icon"></i>
							</button>
							<button class="btn btn-icon btn-danger btn-delete" data-id="{{ $transfer->id }}">
								<i class="ti ti-x icon"></i>
							</button>
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="6" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($transfers->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $transfers->withQueryString()->links() }}
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
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Tipo</label>
  			  			<select class="form-select" name="type" id="type">
  			  				<option value="">Seleccionar</option>
  			  				<option value="seller" @if(request()->type == 'seller') selected @endif>Asesor</option>
  			  				<option value="payment_method" @if(request()->type == 'payment_method') selected @endif>Método de pago</option>
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Asesor comercial origen</label>
  			  			<select class="form-select" name="from_seller_id" id="from_seller_id">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($sellers as $seller)
  			  				<option value="{{ $seller->id }}">{{ $seller->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Asesor comercial destino</label>
  			  			<select class="form-select" name="to_seller_id" id="to_seller_id">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($sellers as $seller)
  			  				<option value="{{ $seller->id }}">{{ $seller->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Método de pago origen</label>
  			  			<select class="form-select" name="from_payment_method_id" id="from_payment_method_id">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				@if($payment_method->id != 6)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endif
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Método de pago destino</label>
  			  			<select class="form-select" name="to_payment_method_id" id="to_payment_method_id">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Monto</label>
  			  			<input type="text" class="form-control" name="amount" autocomplete="off">
  			  		</div>
  			  	</div>
				<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Motivo</label>
  			  			<input type="text" class="form-control" name="reason" autocomplete="off">
  			  		</div>
  			  	</div>
  			  </div>
  			</div>
  			<div class="modal-footer">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  			  <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i> Guardar</button>
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
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Tipo</label>
  			  			<select class="form-select" name="type" id="editType">
  			  				<option value="">Seleccionar</option>
  			  				<option value="seller" @if(request()->type == 'seller') selected @endif>Asesor</option>
  			  				<option value="payment_method" @if(request()->type == 'payment_method') selected @endif>Método de pago</option>
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Asesor comercial origen</label>
  			  			<select class="form-select" name="from_seller_id" id="editFromSellerId">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($sellers as $seller)
  			  				<option value="{{ $seller->id }}">{{ $seller->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Asesor comercial destino</label>
  			  			<select class="form-select" name="to_seller_id" id="editToSellerId">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($sellers as $seller)
  			  				<option value="{{ $seller->id }}">{{ $seller->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Método de pago origen</label>
  			  			<select class="form-select" name="from_payment_method_id" id="editFromPaymentMethodId">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Método de pago destino</label>
  			  			<select class="form-select" name="to_payment_method_id" id="editToPaymentMethodId">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Monto</label>
  			  			<input type="text" class="form-control" name="amount" id="editAmount" autocomplete="off">
  			  		</div>
  			  	</div>
				<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Motivo</label>
  			  			<input type="text" class="form-control" name="reason" id="editReason" autocomplete="off">
  			  		</div>
  			  	</div>
  			  </div>
  			</div>
  			<div class="modal-footer">
  				<input type="hidden" id="editId">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  			  <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>

	$('#storeForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('transfers.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createModal').modal('hide');
					$('#storeForm')[0].reset();
					
					ToastMessage.fire({ text: 'Registro guardado' })
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

	$(document).on('click', '.btn-edit', function(){

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('transfers.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editType').val(data.type);
				$('#editFromSellerId').val(data.from_seller_id);
				$('#editToSellerId').val(data.to_seller_id);
				$('#editFromPaymentMethodId').val(data.from_payment_method_id);
				$('#editToPaymentMethodId').val(data.to_payment_method_id);				
				$('#editAmount').val(data.amount);			
				$('#editReason').val(data.reason);		
				$('#editId').val(data.id);

				if(data.type == 'seller'){
					$('#editToSellerId').attr('disabled', false);
					$('#editFromSellerId').attr('disabled', false);
					$('#editToPaymentMethodId').val('');
					$('#editFromPaymentMethodId').val('');
					$('#editToPaymentMethodId').attr('disabled', true);
					$('#editFromPaymentMethodId').attr('disabled', true);
				}else if(data.type == 'payment_method'){
					$('#editToPaymentMethodId').attr('disabled', false);
					$('#editFromPaymentMethodId').attr('disabled', false);
					$('#editToSellerId').val('');
					$('#editFromSellerId').val('');
					$('#editToSellerId').attr('disabled', true);
					$('#editFromSellerId').attr('disabled', true);
				}

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
			url: '{{ route('transfers.index') }}' + '/' + id + '',
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
					url: '{{ route('transfers.index') }}' + '/' + id,
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

	$('#type').change(function(){
		var type = $(this).val();

		if(type == 'seller'){
			$('#to_seller_id').attr('disabled', false);
			$('#from_seller_id').attr('disabled', false);
			$('#to_payment_method_id').val('');
			$('#from_payment_method_id').val('');
			$('#to_payment_method_id').attr('disabled', true);
			$('#from_payment_method_id').attr('disabled', true);
		}else if(type == 'payment_method'){
			$('#to_payment_method_id').attr('disabled', false);
			$('#from_payment_method_id').attr('disabled', false);
			$('#to_seller_id').val('');
			$('#from_seller_id').val('');
			$('#to_seller_id').attr('disabled', true);
			$('#from_seller_id').attr('disabled', true);
		}else{
			$('#to_seller_id').val('');
			$('#from_seller_id').val('');
			$('#to_seller_id').attr('disabled', false);
			$('#from_seller_id').attr('disabled', false);
			$('#to_payment_method_id').val('');
			$('#from_payment_method_id').val('');
			$('#to_payment_method_id').attr('disabled', false);
			$('#from_payment_method_id').attr('disabled', false);
		}
	});

	$('#editType').change(function(){
		var type = $(this).val();

		if(type == 'seller'){
			$('#editToSellerId').attr('disabled', false);
			$('#editFromSellerId').attr('disabled', false);
			$('#editToPaymentMethodId').val('');
			$('#editFromPaymentMethodId').val('');
			$('#editToPaymentMethodId').attr('disabled', true);
			$('#editFromPaymentMethodId').attr('disabled', true);
		}else if(type == 'payment_method'){
			$('#editToPaymentMethodId').attr('disabled', false);
			$('#editFromPaymentMethodId').attr('disabled', false);
			$('#editToSellerId').val('');
			$('#editFromSellerId').val('');
			$('#editToSellerId').attr('disabled', true);
			$('#editFromSellerId').attr('disabled', true);
		}else{
			$('#editToSellerId').val('');
			$('#editFromSellerId').val('');
			$('#editToSellerId').attr('disabled', false);
			$('#editFromSellerId').attr('disabled', false);
			$('#editToPaymentMethodId').val('');
			$('#editFromPaymentMethodId').val('');
			$('#editToPaymentMethodId').attr('disabled', false);
			$('#editFromPaymentMethodId').attr('disabled', false);
		}
	});
</script>
@endsection