@extends('template.app')

@section('title', 'Inicio')

@section('content')
@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
<div class="row">
	<div class="col-md-9">
		<form class="mb-4">
			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label">Fecha desde</label>
						<input type="date" class="form-control" name="start_date_4" value="{{ request()->start_date_4 }}">
					</div>
				</div>
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label">Fecha hasta</label>
						<input type="date" class="form-control" name="end_date_4" value="{{ request()->end_date_4 }}">
					</div>
				</div>
				@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label">Asesor comercial</label>
						<select class="form-select" name="seller_id_1">
							<option value="">Seleccionar</option>
							@foreach($sellers as $seller)
							<option value="{{ $seller->id }}" @if($seller->id == request()->seller_id_1) selected @endif>{{ $seller->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				@endif
			</div>
			<input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
			<input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
			<input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
			<input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
			<input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
			<input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
			<input type="hidden" name="seller_id_2" value="{{ request()->seller_id_2 }}">
			<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	<div class="col-md-3">
		<div class="card mb-4">
			<div class="card-body">
				<h5 class="card-title">
					Efectivo
				</h5>
				@if(request()->seller_id_1)
				<span class="block fs-1 text-center fw-semibold">S/{{ number_format($home_sales_1, 2) }}</span>
				@else
				<span class="block fs-1 text-center fw-semibold">S/ -</span>
				@endif
			</div>
		</div>
	</div>
	
</div>
<h2>Cuadre general</h2>
<div>
	<form class="mb-4">
		<div class="row">
			<div class="col-md-4">
				<div class="mb-3">
					<label class="form-label">Fecha desde</label>
					<input type="date" class="form-control" name="start_date_3" value="{{ request()->start_date_3 }}">
				</div>
			</div>
			<div class="col-md-4">
				<div class="mb-3">
					<label class="form-label">Fecha hasta</label>
					<input type="date" class="form-control" name="end_date_3" value="{{ request()->end_date_3 }}">
				</div>
			</div>
		</div>
		<input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
		<input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
		<input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
		<input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
		<input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
		<input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
		<input type="hidden" name="seller_id_1" value="{{ request()->seller_id_1 }}">
		<input type="hidden" name="seller_id_2" value="{{ request()->seller_id_2 }}">
		<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
	</form>
</div>
<div class="row">
	<div class="col-md-4">
		<div class="card mb-4">
			<div class="card-body">
				<h5 class="card-title">
					Dinero en cuentas
				</h5>
				<ul>
					<li class="fs-3 fw-semibold">Efectivo: S/{{ number_format($sales_1, 2) }}</li>
					<li class="fs-3 fw-semibold">Banco de la Nación: S/{{ number_format($sales_2, 2) }}</li>
					<li class="fs-3 fw-semibold">Caja Piura: S/{{ number_format($sales_3, 2) }}</li>
					<li class="fs-3 fw-semibold">BCP: S/{{ number_format($sales_4, 2) }}</li>
					<li class="fs-3 fw-semibold">BBVA: S/{{ number_format($sales_5, 2) }}</li>
				</ul>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card mb-4">
			<div class="card-body">
				<h5 class="card-title">
					Caja chica
				</h5>
				<span class="block fs-1 text-center fw-semibold">S/{{ number_format($sales_6, 2) }}</span>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card mb-4">
			<div class="card-body">
				<h5 class="card-title">
					Total
				</h5>
				<span class="block fs-1 text-center fw-semibold">S/{{ number_format($total, 2) }}</span>
			</div>
		</div>
	</div>
</div>
<h2>Indicadores de rentabilidad</h2>
<div class="row">
	<div class="col-md-6">
		<h3>Evolución de ventas vs egresos</h3>
		<div class="card">
			<div class="card-body">
				<canvas id="chart1"></canvas>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<form class="mb-4">
			<div class="row">
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Fecha desde</label>
						<input type="date" class="form-control" name="start_date_1" value="{{ request()->start_date_1 }}">
					</div>
				</div>
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Fecha hasta</label>
						<input type="date" class="form-control" name="end_date_1" value="{{ request()->end_date_1 }}">
					</div>
				</div>
			</div>
			<input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
			<input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
			<input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
			<input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
			<input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
			<input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
			<input type="hidden" name="seller_id_1" value="{{ request()->seller_id_1 }}">
			<input type="hidden" name="seller_id_2" value="{{ request()->seller_id_2 }}">
			<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
		<div class="row">
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Cartera total
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($wallet_total, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Total de deuda (morosos)
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($due_total, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Pagos de hoy
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($today_payments, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Pagos puntuales de hoy
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($today_timely_payments, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Proyectado para hoy
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($today_projected, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">
							Pago puntual
						</h5>
						<span class="d-block fs-1 text-center fw-semibold text-center">{{ $today_timely_payments > 0 ? number_format(($today_timely_payments / $today_projected) * 100, 2) : 0 }} %</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<hr>
@endif
<h2>Indicadores de productividad</h2>
<div class="row mb-4">
	<div class="col-md-6">
		<h3>Evolución de ventas vs egresos</h3>
		<div class="card">
			<div class="card-body">
				<canvas id="chart2"></canvas>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<form class="mb-4">
			<div class="row">
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Fecha desde</label>
						<input type="date" class="form-control" name="start_date_2" value="{{ request()->start_date_2 }}">
					</div>
				</div>
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Fecha hasta</label>
						<input type="date" class="form-control" name="end_date_2" value="{{ request()->end_date_2 }}">
					</div>
				</div>
				@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Asesor comercial</label>
						<select class="form-select" name="seller_id_2">
							<option value="">Seleccionar</option>
							@foreach($sellers as $seller)
							<option value="{{ $seller->id }}" @if($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				@endif
			</div>
			<input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
			<input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
			<input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
			<input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
			<input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
			<input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
			<input type="hidden" name="seller_id_1" value="{{ request()->seller_id_1 }}">
			<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
		<div class="row">
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Clientes activos
						</h5>
						<span class="block fs-1 text-center fw-semibold">{{ $active_clients }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Clientes con deuda
						</h5>
						<span class="block fs-1 text-center fw-semibold">{{ $due_clients }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Cartera del asesor
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($seller_wallet, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							Monto desembolsado
						</h5>
						<span class="block fs-1 text-center fw-semibold">S/{{ number_format($requested_amount, 2) }}</span>
					</div>
				</div>
			</div>
			<div class="col-md-6 offset-md-3">
				<div class="card mb-4">
					<div class="card-body">
						<h5 class="card-title">
							# de cuotas por pagar
						</h5>
						<span class="block fs-1 text-center fw-semibold">{{ number_format($due_quotas, 0) }}</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
	$(document).ready(function(){
		const ctx_chart1 = document.getElementById('chart1');
		const ctx_chart2 = document.getElementById('chart2');

		new Chart(ctx_chart1, {
			type: 'bar',
			data: {
				labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
				datasets: [
				{
					label: 'Ventas',
					data: @json($sales_totals_1),
					borderWidth: 1
				},
				{
					label: 'Egresos',
					data: @json($expenses_totals_1),
					borderWidth: 1
				}
				]
			},
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});

		new Chart(ctx_chart2, {
			type: 'bar',
			data: {
				labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
				datasets: [
				{
					label: 'Ventas',
					data: @json($sales_totals_2),
					borderWidth: 1
				},
				{
					label: 'Egresos',
					data: @json($expenses_totals_2),
					borderWidth: 1
				}
				]
			},
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});
	});
</script>
@endsection