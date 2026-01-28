@extends('template.app')

@section('title', 'Contratos - Por finalizar')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('contracts.index') }}">Contratos</a></li>
    <li class="breadcrumb-item active">Por finalizar</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header">
		<a class="btn btn-success" href="{{ route('contracts.ending.excel', request()->all()) }}" target="_blank">Excel</a>
	</div>
	<div class="card-body border-bottom">
		<form>
			<div class="row">
				@if(auth()->user()->hasRole('admin'))
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
				@endif
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Fecha inicio de última cuota</label>
						<input type="date" class="form-control" name="start_date" value="{{ request()->start_date ? request()->start_date : now()->format('Y-m-d') }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Fecha final de última cuota</label>
						<input type="date" class="form-control" name="end_date" value="{{ request()->end_date ? request()->end_date : now()->format('Y-m-d') }}">
					</div>
				</div>
			</div>
			<button class="btn btn-primary">Filtrar</button>
			<a href="{{ route('contracts.ending') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente/Grupo</th>
					<th>Asesor C.</th>
					<th>Monto solicitado</th>
					<th>Cuotas</th>
					<th>Interés</th>
					<th>Monto a pagar</th>
					<th>Fecha de prestamo</th>
					<th>Fecha de última cuota</th>
				</tr>
			</thead>
			<tbody>
				@if($contracts->count() > 0)
				@foreach($contracts as $contract)
				<tr>
					<td title="{!! $contract->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">{{ $contract->client_type == 'Personal' ? $contract->name : $contract->group_name }}</td>
					<td>{{ optional($contract)->seller->name }}</td>
					<td>{{ $contract->requested_amount }}</td>
					<td>{{ $contract->quotas_number }}</td>
					<td>{{ $contract->interest }}</td>
					<td>{{ $contract->payable_amount }}</td>
					<td>{{ $contract->date->format('d/m/Y') }}</td>	
					<td>{{ $contract->last_payment_date->format('d/m/Y') }}</td>	
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
	@if($contracts->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $contracts->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection

