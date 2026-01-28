@extends('template.app')

@section('title', 'Asesores comerciales')

@section('content')
<nav class="mb-2">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
		<li class="breadcrumb-item active">Asesores comerciales</li>
	</ol>
</nav>

<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</button>
		</div>
		<div>
			<form>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="Buscar" name="search" value="{{ request()->search }}" autocomplete="off">
					<button type="submit" class="btn btn btn-icon">
						<i class="ti ti-search icon"></i>
					</button>
				</div>
			</form>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>DNI</th>
					<th>Nombre</th>
					<th>Teléfono</th>
					<th>Dirección</th>
					<th>Correo electrónico</th>
					<th>Usuario</th>
					<th>Estado</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($sellers->count() > 0)
				@foreach($sellers as $seller)
				<tr>
					<td>{{ $seller->document }}</td>
					<td>{{ $seller->name }}</td>
					<td>{{ $seller->phone }}</td>
					<td>{{ $seller->address }}</td>
					<td>{{ $seller->email }}</td>
					<td>{{ $seller->user }}</td>
					<td>{{ $seller->state == 0 ? 'Activo' : 'Inactivo' }}</td>
					<td>
						<div class="d-flex gap-2">
							<button class="btn btn-primary btn-icon btn-edit " data-id="{{ $seller->id }}">
								<i class="ti ti-pencil icon"></i>
							</button>
							@if ($seller->state == 0)
							<button class="btn btn-icon btn-warning btn-drop" data-id="{{ $seller->id }}">
								<i class="ti ti-arrow-down icon"></i>
							</button>
							@else
							<button class="btn btn-icon btn-success btn-up" data-id="{{ $seller->id }}">
								<i class="ti ti-arrow-up icon"></i>
							</button>
							@endif
							<button class="btn btn-icon btn-danger btn-delete" data-id="{{ $seller->id }}">
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
	@if($sellers->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $sellers->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<form id="storeForm" method="POST">
				<div class="modal-header">
					<h5 class="modal-title">Crear nuevo</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">DNI</label>
								<input type="text" class="form-control" name="document" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">Nombre</label>
								<input type="text" class="form-control" name="name" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Teléfono</label>
								<input type="text" class="form-control" name="phone" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Dirección</label>
								<input type="text" class="form-control" name="address" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Correo electrónico</label>
								<input type="text" class="form-control" name="email" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">Usuario</label>
								<input type="text" class="form-control" name="user" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">Contraseña</label>
								<input type="password" class="form-control" name="password" autocomplete="off">
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
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<form id="editForm" method="POST">
				<div class="modal-header">
					<h5 class="modal-title">Editar</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">DNI</label>
								<input type="text" class="form-control" name="document" id="editDocument" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">Nombre</label>
								<input type="text" class="form-control" name="name" id="editName" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Teléfono</label>
								<input type="text" class="form-control" name="phone" id="editPhone" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Dirección</label>
								<input type="text" class="form-control" name="address" id="editAddress" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Correo electrónico</label>
								<input type="text" class="form-control" name="email" id="editEmail" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label required">Usuario</label>
								<input type="text" class="form-control" name="user" id="editUser" autocomplete="off">
							</div>
						</div>
						<div class="col-12 col-lg-6">
							<div class="mb-3">
								<label class="form-label">Contraseña</label>
								<input type="password" class="form-control" name="password" id="editPassword" autocomplete="off">
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
	// Configure global AJAX headers so Laravel receives the CSRF token on all requests
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': '{{ csrf_token() }}'
		}
	});
	$('#storeForm').submit(function(e) {
		e.preventDefault();

		$.ajax({
			url: '{{ route("sellers.store") }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data) {
				if (data.status) {
					$('#createModal').modal('hide');
					$('#storeForm')[0].reset();

					ToastMessage.fire({
							text: 'Registro guardado'
						})
						.then(() => location.reload());

				} else {
					ToastError.fire({
						text: data.error ? data.error : 'Ocurrió un error'
					});
				}
			},
			error: function(err) {
				ToastError.fire({
					text: 'Ocurrió un error'
				});
			}
		});

	});

	$(document).on('click', '.btn-edit', function() {

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route("sellers.edit", ":id") }}'.replace(':id', id),
			method: 'GET',
			success: function(data) {
				$('#editDocument').val(data.document);
				$('#editName').val(data.name);
				$('#editAddress').val(data.address);
				$('#editPhone').val(data.phone);
				$('#editEmail').val(data.email);
				$('#editUser').val(data.user);
				$('#editId').val(data.id);
				$('#editModal').modal('show');
			},
			error: function(err) {
				ToastError.fire({
					text: 'Ocurrió un error'
				});
			}
		});

	});

	$('#editForm').submit(function(e) {
		e.preventDefault();

		var id = $('#editId').val();

		$.ajax({
			url: '{{ route("sellers.update", ":id") }}'.replace(':id', id),
			method: 'PUT',
			data: $(this).serialize(),
			success: function(data) {
				if (data.status) {
					$('#editModal').modal('hide');
					$('#editForm')[0].reset();

					ToastMessage.fire({
							text: 'Registro actualizado'
						})
						.then(() => location.reload());

				} else {
					ToastError.fire({
						text: data.error ? data.error : 'Ocurrió un error'
					});
				}
			},
			error: function(err) {
				ToastError.fire({
					text: 'Ocurrió un error'
				});
			}
		});

	});

	$(document).on('click', '.btn-delete', function() {

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro que deseas borrar el registro?',
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: '{{ route("sellers.destroy", ":id") }}'.replace(':id', id),
					method: 'DELETE',
					success: function(data) {
						ToastMessage.fire({
								text: 'Registro eliminado'
							})
							.then(() => location.reload());
					},
					error: function(err) {
						ToastError.fire({
							text: 'Ocurrió un error'
						});
					}
				});
			}
		});

	});

	$(document).on('click', '.btn-drop', function() {

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro de dar de baja al asesor seleccionado?',
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: '{{ route("sellers.drop", ":id") }}'.replace(':id', id),
					method: 'PUT',
					success: function(data) {
						ToastMessage.fire({
								text: 'Registro actualizado'
							})
							.then(() => location.reload());
					},
					error: function(err) {
						ToastError.fire({
							text: 'Ocurrió un error'
						});
					}
				});
			}
		});

	});

	$(document).on('click', '.btn-up', function() {

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro de activar al asesor seleccionado?',
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: '{{ route("sellers.up", ":id") }}'.replace(':id', id),
					method: 'PUT',
					success: function(data) {
						ToastMessage.fire({
								text: 'Registro actualizado'
							})
							.then(() => location.reload());
					},
					error: function(err) {
						ToastError.fire({
							text: 'Ocurrió un error'
						});
					}
				});
			}
		});

	});

	$(document).on('click', '.btn-active', function() {

		var id = $(this).data('id');

		ToastConfirm.fire({
			text: '¿Estás seguro que deseas recuperar el registro?',
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: '{{ url("sellers/:id/active") }}'.replace(':id', id),
					method: 'POST',
					success: function(data) {
						ToastMessage.fire({
								text: 'Registro recuperado'
							})
							.then(() => location.reload());
					},
					error: function(err) {
						ToastError.fire({
							text: 'Ocurrió un error'
						});
					}
				});
			}
		});

	});
</script>
@endsection