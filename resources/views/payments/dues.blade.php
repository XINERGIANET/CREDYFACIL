@extends('template.app')

@section('title', 'Gestión de mora')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Gestión de mora</li>
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
						<label class="form-label">Fecha</label>
						<input type="date" class="form-control" name="date" value="{{ request()->date ? request()->date : now()->format('Y-m-d') }}">
					</div>
				</div>
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Días de mora (Rango)</label>
						<div class="input-group">
							<input type="number" class="form-control" name="from_days" min="1" value="{{ request()->from_days }}">
							<input type="number" class="form-control" name="to_days" min="1" value="{{ request()->to_days  }}">
						</div>
					</div>
				</div>
			</div>
			<button class="btn btn-primary">Filtrar</button>
			<a href="{{ route('payments.dues') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Cliente</th>
					<th>Número de cuota</th>
					<th>Monto</th>
					<th>Saldo</th>
					<th>Fecha de pago</th>
					<th>Días de mora</th>
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
					<td>{{ $quota->date->diffInDays(now()) }}</td>
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