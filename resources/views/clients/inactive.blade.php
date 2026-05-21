@extends('template.app')

@section('title', 'Clientes inactivos')

@section('content')
<nav class="mb-2">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
		<li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clientes</a></li>
		<li class="breadcrumb-item active">Clientes inactivos</li>
	</ol>
</nav>

<div class="card">
	@if (auth()->user()->hasRole('admin'))
	<div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
		<div>
			<span class="badge bg-secondary-lt">Total: {{ $total_clients }}</span>
		</div>
		<a class="btn btn-success" href="{{ route('clients.inactive.excel', request()->all()) }}" target="_blank">
			<i class="ti ti-file-export icon"></i> Excel
		</a>
	</div>
	@endif
	<div class="card-body border-bottom">
		<form method="GET" action="{{ route('clients.inactive') }}">
			<div class="row">
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Cliente, grupo o DNI</label>
						<input type="text" class="form-control" name="name" value="{{ request()->name }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Tipo</label>
						<select class="form-select" name="client_type">
							<option value="">Todos</option>
							<option value="Personal" @if(request()->client_type == 'Personal') selected @endif>Personal</option>
							<option value="Grupo" @if(request()->client_type == 'Grupo') selected @endif>Grupo</option>
						</select>
					</div>
				</div>
				@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
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
						<label class="form-label">Prestamo desde</label>
						<input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Prestamo hasta</label>
						<input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Ultimo pago desde</label>
						<input type="date" class="form-control" name="last_payment_start_date" value="{{ request()->last_payment_start_date }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Ultimo pago hasta</label>
						<input type="date" class="form-control" name="last_payment_end_date" value="{{ request()->last_payment_end_date }}">
					</div>
				</div>
			</div>
			<button class="btn btn-primary">Filtrar</button>
			<a href="{{ route('clients.inactive') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente/Grupo</th>
					<th>DNI/Integrantes</th>
					<th>Telefono</th>
					<th>Tipo</th>
					<th>Asesor</th>
					<th>Ultimo contrato</th>
					<th>Monto</th>
					<th>Fecha prestamo</th>
					<th>Fecha ultima cuota</th>
					<th>Ultimo pago</th>
					<th>Monto ultimo pago</th>
					<th>Total pagado</th>
				</tr>
			</thead>
			<tbody>
				@if($inactive_clients->count() > 0)
				@foreach($inactive_clients as $contract)
				<tr>
					<td title="{!! $contract->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">
						{{ $contract->client() }}
					</td>
					<td>
						@if($contract->client_type == 'Personal')
							{{ $contract->document }}
						@else
							{!! $contract->people() !!}
						@endif
					</td>
					<td>{{ $contract->phone }}</td>
					<td>{{ $contract->client_type }}</td>
					<td>{{ optional($contract->seller)->name }}</td>
					<td>#{{ $contract->id }}</td>
					<td>S/ {{ number_format($contract->requested_amount, 2) }}</td>
					<td>{{ optional($contract->date)->format('d/m/Y') }}</td>
					<td>{{ optional($contract->last_payment_date)->format('d/m/Y') }}</td>
					<td>
						@if($contract->last_payment_date_value)
							{{ \Carbon\Carbon::parse($contract->last_payment_date_value)->format('d/m/Y') }}
						@else
							-
						@endif
					</td>
					<td>S/ {{ number_format($contract->last_payment_amount_value ?? 0, 2) }}</td>
					<td>S/ {{ number_format($contract->total_paid_value ?? 0, 2) }}</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="12" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($inactive_clients->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $inactive_clients->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection
