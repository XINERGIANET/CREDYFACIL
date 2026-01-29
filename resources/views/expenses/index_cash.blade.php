@extends('template.app')

@section('title', 'Administrativos')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Egresos</li>
    <li class="breadcrumb-item active">Administrativos</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header d-flex justify-content-between align-items-center">
		<div>
			@if(auth()->user()->hasRole('seller') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations'))
			<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</button>
			@endif
			<a class="btn btn-success" href="{{ route('expenses.excel_cash', request()->all()) }}" target="_blank">Excel</a>
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
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Descripción</label>
						<input type="text" class="form-control" name="description" value="{{ request()->description }}">
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
			<a href="{{ route('expenses.index_cash') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Descripción</th>
					<th>Cliente/Grupo</th>
					<th>Monto</th>
					<th>Método de pago</th>
					<th>Fecha</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($expenses->count() > 0)
				@foreach($expenses as $expense)
				<tr>
					<td>{{ $expense->description }}</td>
					<td>{{ $expense->contract ? optional($expense->contract)->client() : 'Gastos generales' }}</td>
					<td>S/{{ number_format($expense->expensePayments->sum('amount'), 2) }}</td>
					<td>
						@foreach($expense->expensePayments as $payment)
							<div class="d-flex justify-content-between">
								<div>{{ optional($payment->paymentMethod)->name }}</div>
								<div class="text-end">S/{{ number_format($payment->amount, 2) }}</div>
							</div>
						@endforeach
					</td>
					<td>{{ $expense->date->format('d/m/Y') }}</td>
					<td>
						@if(auth()->user()->hasRole('seller') || auth()->user()->hasRole('admin'))
						<div class="d-flex gap-2">
							<button class="btn btn-primary btn-icon btn-edit " data-id="{{ $expense->id }}">
								<i class="ti ti-pencil icon"></i>
							</button>
							@if($expense->image)
							<a class="btn btn-primary btn-icon" href="{{ asset('storage/'.$expense->image) }}" target="_blank">
								<i class="ti ti-photo icon"></i>
							</a>
							@endif
							<button class="btn btn-icon btn-danger btn-delete" data-id="{{ $expense->id }}">
								<i class="ti ti-x icon"></i>
							</button>
						</div>
						@endif
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="7" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($expenses->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $expenses->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="storeForm" method="POST" enctype="multipart/form-data">
  			<div class="modal-header">
  			  <h5 class="modal-title">Crear nuevo</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="row">
  			  	<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Descripción</label>
  			  			<input type="text" class="form-control" name="description" id="description" autocomplete="off">
  			  		</div>
  			  	</div>
		  		<!-- monto ya se gestiona en expense_payments -->
 				<div class="col-12">
		  			<div class="mb-3">
		  				<label class="form-label required">Método de pago</label>
	 				<div class="d-flex gap-2 align-items-start w-100">
	 					<select class="form-select flex-grow-1" name="payment_method_id" id="payment_method_id" style="min-width:0;">
		 						<option value="">Seleccionar</option>
		 						@foreach($payment_methods as $payment_method)
		 						<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
		 						@endforeach
		 					</select>
	 					<input type="text" class="form-control ms-2" name="payment_amount" id="payment_amount" placeholder="Monto" style="width:140px;">
	 					<button type="button" class="btn btn-outline-primary" id="addPaymentBtn" title="Agregar otro método">+</button>
		 				</div>
		 				<!-- segundo método (solo 1 adicional) -->
		 				<div id="payment_method_block_2" class="mt-2 d-none">
	 					<div class="d-flex gap-2 align-items-start w-100">
	 						<select class="form-select flex-grow-1" name="payment_method_id_2" id="payment_method_id_2" style="min-width:0;">
		 							<option value="">Seleccionar</option>
		 							@foreach($payment_methods as $payment_method)
		 							<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
		 							@endforeach
		 						</select>
	 						<input type="text" class="form-control ms-2" name="payment_amount_2" id="payment_amount_2" placeholder="Monto" style="width:140px;">
		 					</div>
		 				</div>
		  			</div>
			</div>
			  </div>
			  <div class="row mt-2">
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label required">Fecha</label>
						<input type="date" class="form-control" name="date" id="date" value="{{ now()->format('Y-m-d') }}" @if(auth()->user()->hasRole('seller')) readonly @endif>
					</div>
				</div>
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Imagen</label>
						<input type="file" class="form-control" name="image" id="image" accept=".jpg,.jpeg,.png,.webp">
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
  		<form id="editForm" method="POST" enctype="multipart/form-data">
  			<div class="modal-header">
  			  <h5 class="modal-title">Editar</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="row">
  			  	<div class="col-lg-12">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Descripción</label>
  			  			<input type="text" class="form-control" name="description" id="editDescription" autocomplete="off">
  			  		</div>
  			  	</div>
		  		<!-- monto trasladado a expense_payments (por método) -->
				<div class="col-12">
					<div class="mb-3">
						<label class="form-label required">Método de pago</label>
	 				<div class="d-flex gap-2 align-items-start w-100">
	 					<select class="form-select flex-grow-1" name="payment_method_id" id="editPaymentMethodId" style="min-width:0;">
	 						<option value="">Seleccionar</option>
	 						@foreach($payment_methods as $payment_method)
	 						<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
	 						@endforeach
	 					</select>
	 					<input type="text" class="form-control ms-2" name="payment_amount" id="editPaymentAmount" placeholder="Monto" style="width:140px;">
	 					<button type="button" class="btn btn-outline-primary" id="editAddPaymentBtn" title="Agregar otro método">+</button>
	 				</div>
	 				<div id="edit_payment_method_block_2" class="mt-2 d-none">
	 					<div class="d-flex gap-2 align-items-start w-100">
	 						<select class="form-select flex-grow-1" name="payment_method_id_2" id="editPaymentMethodId2" style="min-width:0;">
	 							<option value="">Seleccionar</option>
	 							@foreach($payment_methods as $payment_method)
	 							<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
	 							@endforeach
	 						</select>
	 						<input type="text" class="form-control ms-2" name="payment_amount_2" id="editPaymentAmount2" placeholder="Monto" style="width:140px;">
	 					</div>
	 				</div>
	 			</div>
				</div>
			  </div>
			  <div class="row mt-2">
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Fecha</label>
						<input type="date" class="form-control" name="date" id="editDate" @if(auth()->user()->hasRole('seller')) readonly @endif>
					</div>
				</div>
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Imagen</label>
						<input type="file" class="form-control" name="image" id="editImage" accept=".jpg,.jpeg,.png,.webp">
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

	// Mostrar segundo método en creación
	$('#addPaymentBtn').on('click', function(){
		$('#payment_method_block_2').removeClass('d-none');
		$(this).prop('disabled', true);
	});

	// Mostrar segundo método en edición
	$('#editAddPaymentBtn').on('click', function(){
		$('#edit_payment_method_block_2').removeClass('d-none');
		$(this).prop('disabled', true);
	});

	// Al abrir modal de creación, resetear estado del segundo select
	$('#createModal').on('show.bs.modal', function(){
		$('#payment_method_block_2').addClass('d-none');
		$('#payment_method_id_2').val('');
		$('#payment_amount_2').val('');
		$('#addPaymentBtn').prop('disabled', false);
	});

	// Al cerrar modal de edición, resetear segundo select
	$('#editModal').on('hide.bs.modal', function(){
		$('#edit_payment_method_block_2').addClass('d-none');
		$('#editPaymentMethodId2').val('');
		$('#editPaymentAmount2').val('');
		$('#editAddPaymentBtn').prop('disabled', false);
	});

	$('#storeForm').submit(function(e){
		e.preventDefault();

		var fd = new FormData();

		fd.append('description', $('#description').val());
		fd.append('payment_method_id', $('#payment_method_id').val());
		fd.append('payment_amount', $('#payment_amount').val());
		fd.append('date', $('#date').val());
		fd.append('image', $('#image')[0].files[0]);

		// segundo método y monto
		if (!$('#payment_method_block_2').hasClass('d-none')){
			fd.append('payment_method_id_2', $('#payment_method_id_2').val());
			fd.append('payment_amount_2', $('#payment_amount_2').val());
		}

		$.ajax({
			url: '{{ route("expenses.store") }}',
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
			url: '{{ route("expenses.index") }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editDescription').val(data.description);
				$('#editPaymentMethodId').val(data.payment_method_id);
				$('#editPaymentAmount').val(data.payment_amount);
				if (data.payment_method_id_2) {
					$('#edit_payment_method_block_2').removeClass('d-none');
					$('#editPaymentMethodId2').val(data.payment_method_id_2);
					$('#editPaymentAmount2').val(data.payment_amount_2);
					$('#editAddPaymentBtn').prop('disabled', true);
				} else {
					$('#edit_payment_method_block_2').addClass('d-none');
					$('#editAddPaymentBtn').prop('disabled', false);
				}
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

		var fd = new FormData();

		fd.append('description', $('#editDescription').val());
		fd.append('payment_method_id', $('#editPaymentMethodId').val());
		fd.append('payment_amount', $('#editPaymentAmount').val());
		fd.append('date', $('#editDate').val());
		fd.append('image', $('#editImage')[0].files[0]);
		// segundo método en edición
		if (!$('#edit_payment_method_block_2').hasClass('d-none')){
			fd.append('payment_method_id_2', $('#editPaymentMethodId2').val());
			fd.append('payment_amount_2', $('#editPaymentAmount2').val());
		}
		fd.append('_method', 'patch');

		$.ajax({
			url: '{{ route("expenses.index") }}' + '/' + id + '',
			method: 'POST',
			processData: false,
			contentType: false,
			data: fd,
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
					url: '{{ route("expenses.index") }}' + '/' + id,
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
