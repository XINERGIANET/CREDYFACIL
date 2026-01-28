@extends('template.app')

@section('title', 'Gestión de cobranza')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Gestión de cobranza</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header">
		<a class="btn btn-success" href="{{ route('payments.charges.excel', request()->all()) }}" target="_blank">Excel</a>
	</div>
	<div class="card-body border-bottom">
		<form>
			<div class="row">
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<input type="text" class="form-control" name="name" value="{{ request()->name }}">
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
			<a href="{{ route('payments.charges') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente</th>
					<th>Número de cuota</th>
					<th>Monto</th>
					<th>Saldo</th>
					<th>Fecha de pago</th>
				</tr>
			</thead>
			<tbody>
				@if($quotas->count() > 0)
				@foreach($quotas as $quota)
				<tr>
					<td>{{ optional($quota->contract)->client() }}</td>
					<td>{{ $quota->number }}</td>
					<td>{{ $quota->amount }}</td>
					<td>{{ $quota->debt }}</td>
					<td>{{ $quota->date->format('d/m/Y') }}</td>
				@endforeach
				@else
				<tr>
					<td colspan="5" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($quotas->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $quotas->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection