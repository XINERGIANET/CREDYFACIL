@extends('template.app')

@section('title', 'Intereses')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Intereses mensuales</li>
  </ol>
</nav>

<div class="card">
	@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
	<div class="card-body border-bottom">
		<form>
			<div class="row">
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<input type="text" class="form-control" name="name" value="{{ request()->name }}">
					</div>
				</div>

				@php
				$monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
				@endphp

				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Mes</label>
						<select name="month" id="month-select" class="form-select">
							<option value="" {{ request()->filled('month') ? '' : 'selected' }}>Todos los meses</option>
							@foreach(range(1,12) as $m)
								<option value="{{ $m }}" {{ (string) request()->month === (string) $m ? 'selected' : '' }}>
									{{ $monthNames[$m-1] }}
								</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Año</label>
						<input type="text"
                               class="form-control"
                               name="year"
                               value="{{ request()->year ?? date('Y') }}"
                               maxlength="4"
                               pattern="[0-9]{1,4}"
                               inputmode="numeric"
                               title="Ingrese hasta 4 dígitos"
                               oninput="this.value = this.value.replace(/\D/g,'').slice(0,4);">
					</div>
				</div>

			</div>
			<button class="btn btn-primary">Filtrar</button>
			<a href="{{ route('clients.index') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente/Grupo</th>
					<th>Interés acumulado</th>
					<!-- <th>Acción</th> -->
				</tr>
			</thead>
			<tbody>
				@if($clients->count() > 0)
				@foreach($clients as $client)
				<tr>
					<td title="{!! $client->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">{{ $client->client_type == 'Personal' ? $client->name : $client->group_name }}</td>
					<td>{{ $client->filtered_interest }}</td>	
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="9" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($clients->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $clients->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  			<h5 class="modal-title">Detalles</h5>
  			 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
			<div class="table-responsive">
  			<table class="table card-table table-vcenter">
  				<thead>
  					<tr>
  						<th>DNI</th>
  						<th>Nombre</th>
  						<th>Teléfono</th>
  						<th>Dirección</th>
  						<th>Estado civil</th>
  					</tr>
  				</thead>
  				<tbody id="tbl-details"></tbody>
  			</table>
			</div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="contractsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  			<h5 class="modal-title">Contratos</h5>
  			 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
			<div class="table-responsive">
  			<table class="table card-table table-vcenter">
  				<thead>
  					<tr>
  						<th>Monto solicitado</th>
  						<th>Cuotas</th>
  						<th>Interes</th>
  						<th>Monto a pagar</th>
  						<th>Fecha de prestamo</th>
  						<th></th>
  						<th>Acción</th>
  					</tr>
  				</thead>
  				<tbody id="tbl-contracts"></tbody>
  			</table>
			</div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="quotasModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  			<h5 class="modal-title">Cuotas</h5>
  			 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
			<div class="table-responsive">
  			<table class="table card-table table-vcenter">
  				<thead>
  					<tr>
  						<th>Número</th>
  						<th>Monto</th>
  						<th>Saldo</th>
  						<th>Fecha</th>
  						<th></th>
  					</tr>
  				</thead>
  				<tbody id="tbl-quotas"></tbody>
  			</table>
			</div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>

	$(document).on('click', '.btn-details', function(){

		var doc = $(this).data('document');

		$.ajax({
			url: '{{ route('clients.details') }}',
			method: 'GET',
			data: { document: doc },
			success: function(data){
				
				var html = `
					<tr>
						<td>${data.document}</td>
						<td>${data.name}</td>
						<td>${data.phone}</td>
						<td>${data.address}</td>
						<td>${data.civil_status}</td>
					</tr>
				`;

				$('#tbl-details').html(html);
				$('#detailsModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-contracts', function(){

		var client_type = $(this).data('client-type');
		var doc = $(this).data('document');
		var group_name = $(this).data('group-name');

		$.ajax({
			url: '{{ route('clients.contracts') }}',
			method: 'GET',
			data: { client_type, document: doc, group_name },
			success: function(data){
				var html = '';

				data.forEach(function(contract){

					html += `
						<tr>
							<td>${contract.requested_amount}</td>
							<td>${contract.quotas_number}</td>
							<td>${contract.interest}</td>
							<td>${contract.payable_amount}</td>
							<td>${contract.date}</td>
							<td>${ contract.paid ? '<span class="badge bg-success"></span>' : '<span class="badge bg-danger"></span>' }</td>
							<td>
								<button class="btn btn-primary btn-icon btn-quotas" data-contract="${contract.id}" title="Quotas">
									<i class="ti ti-list icon"></i>
								</button>
							</td>
						</tr>
					`;
				
				});

				$('#tbl-contracts').html(html);
				$('#contractsModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-quotas', function(){

		var contract_id = $(this).data('contract');

		$.ajax({
			url: '{{ route('clients.quotas') }}',
			method: 'GET',
			data: { contract_id },
			success: function(data){
				var html = '';

				data.forEach(function(quota){

					html += `
						<tr>
							<td>${quota.number}</td>
							<td>${quota.amount}</td>
							<td>${quota.debt}</td>
							<td>${quota.date}</td>
							<td>${ quota.paid ? '<span class="badge bg-success"></span>' : '<span class="badge bg-danger"></span>' }</td>
						</tr>
					`;
				
				});

				$('#tbl-quotas').html(html);
				$('#quotasModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

</script>
@endsection