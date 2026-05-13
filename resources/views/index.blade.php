@extends('template.app')

@section('title', 'Inicio')

@section('content')
@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
<div class="d-flex justify-content-end mb-3">
	<a class="btn btn-success" href="{{ route('reports.portfolio-daily.excel', ['date' => request()->end_date_2 ?: now()->format('Y-m-d')]) }}" target="_blank">
		<i class="ti ti-file-spreadsheet icon"></i> Reporte cartera al dia
	</a>
</div>
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
					<li class="fs-3 fw-semibold">BCP: S/{{ number_format($sales_2, 2) }}</li>
					<li class="fs-3 fw-semibold">Yape: S/{{ number_format($sales_3, 2) }}</li>
					
				</ul>
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

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        @php
            $portfolioDate = request()->end_date_4 ?? request()->end_date_2 ?? now();
            $displayDate = \Carbon\Carbon::parse($portfolioDate)->format('d/m/Y');
        @endphp
        <h3 class="card-title mb-0" style="color: #00E5E5 !important;">Reporte de Cartera al Día ({{ $displayDate }})</h3>
        <a class="btn btn-sm btn-success" href="{{ route('reports.portfolio-daily.excel', ['date' => \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d')]) }}" target="_blank">
            <i class="ti ti-file-spreadsheet icon"></i> Excel
        </a>
    </div>
    <div class="table-responsive">
        <style>
            .table-portfolio th { font-size: 0.65rem; text-align: center; vertical-align: middle; padding: 4px; background-color: #00E5E5 !important; color: #000; border: 1px solid #dee2e6; line-height: 1.1; }
            .table-portfolio td { font-size: 0.75rem; text-align: center; vertical-align: middle; border: 1px solid #dee2e6; padding: 4px; }
            .bg-grey-report { background-color: #808080 !important; color: #fff !important; }
            .bg-yellow-report { background-color: #FFFF00 !important; color: #000 !important; }
            .bg-green-report { background-color: #00F000 !important; color: #000 !important; }
            .bg-red-report { background-color: #FF0000 !important; color: #fff !important; font-weight: bold; }
            .bg-total-report { background-color: #00F000 !important; font-weight: bold; }
            .bg-total-report td { font-weight: bold; }
        </style>
        <table class="table table-bordered table-portfolio mb-0">
            <thead>
                <tr>
                    <th>ASESOR</th>
                    <th>INIC. MES<br>N° CLIENTES</th>
                    <th>AVANCE N°<br>CLIENT. AL DIA</th>
                    <th>CRECIM.<br>N° CLIENTES</th>
                    <th>NUEVOS</th>
                    <th class="bg-yellow-report">META DE<br>NUEVOS</th>
                    <th class="bg-green-report">%</th>
                    <th>INIC. MES<br>CARTERA</th>
                    <th>AVANCE<br>CARTERA</th>
                    <th>CREC.<br>CARTERA</th>
                    <th class="bg-red-report">MORA >7</th>
                    <th>DESEMB.<br>MES PASADO</th>
                    <th>N° OPER.<br>MES PASADO</th>
                    <th>AVANCE<br>DESEMB.</th>
                    <th>N° DE<br>OPER.</th>
                    <th class="bg-yellow-report">META MES</th>
                    <th class="bg-green-report">AVANCE %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($portfolioReport['rows'] as $item)
                    @php 
                        $sellerId = $item['seller_id'];
                        $row = $item['data'];
                    @endphp
                    <tr>
                        <td class="fw-bold">{{ $row[0] }}</td>
                        <td class="bg-grey-report">{{ number_format($row[1], 0) }}</td>
                        <td class="clickable-cell" style="cursor: pointer;" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver clientes">
                            <u>{{ number_format($row[2], 0) }}</u>
                        </td>
                        <td>{{ number_format($row[3], 0) }}</td>
                        <td class="bg-grey-report">{{ number_format($row[4], 0) }}</td>
                        <td class="bg-yellow-report">{{ number_format($row[5], 0) }}</td>
                        <td class="bg-green-report fw-semibold">{{ $row[6] ? number_format($row[6] * 100, 2) . '%' : '-' }}</td>
                        <td class="bg-grey-report">S/{{ number_format($row[7], 1) }}</td>
                        <td>S/{{ number_format($row[8], 1) }}</td>
                        <td>S/{{ number_format($row[9], 1) }}</td>
                        <td class="bg-red-report">{{ $row[10] ? number_format($row[10] * 100, 2) . '%' : '-' }}</td>
                        <td class="bg-grey-report">S/{{ number_format($row[11], 1) }}</td>
                        <td class="bg-grey-report">S/{{ number_format($row[12], 1) }}</td>
                        <td>S/{{ number_format($row[13], 0) }}</td>
                        <td>{{ number_format($row[14], 0) }}</td>
                        <td class="bg-yellow-report">S/{{ number_format($row[15], 0) }}</td>
                        <td class="bg-green-report fw-semibold">{{ $row[16] ? number_format($row[16] * 100, 2) . '%' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-total-report">
                    @php $totals = $portfolioReport['totals']; @endphp
                    <td>{{ $totals[0] }}</td>
                    <td>{{ number_format($totals[1], 0) }}</td>
                    <td class="clickable-cell" style="cursor: pointer;" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver todos los clientes">
                        <u>{{ number_format($totals[2], 0) }}</u>
                    </td>
                    <td>{{ number_format($totals[3], 0) }}</td>
                    <td>{{ number_format($totals[4], 0) }}</td>
                    <td>{{ number_format($totals[5], 0) }}</td>
                    <td>{{ $totals[6] ? number_format($totals[6] * 100, 2) . '%' : '-' }}</td>
                    <td>S/{{ number_format($totals[7], 1) }}</td>
                    <td>S/{{ number_format($totals[8], 1) }}</td>
                    <td>S/{{ number_format($totals[9], 1) }}</td>
                    <td>{{ $totals[10] ? number_format($totals[10] * 100, 2) . '%' : '-' }}</td>
                    <td>S/{{ number_format($totals[11], 1) }}</td>
                    <td>S/{{ number_format($totals[12], 1) }}</td>
                    <td>S/{{ number_format($totals[13], 0) }}</td>
                    <td>{{ number_format($totals[14], 0) }}</td>
                    <td>S/{{ number_format($totals[15], 0) }}</td>
                    <td>{{ $totals[16] ? number_format($totals[16] * 100, 2) . '%' : '-' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
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
<div class="modal modal-blur fade" id="clientsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Clientes al Día</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Documento/Grupo</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th>Asesor</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            <tr>
                                <td colspan="5" class="text-center">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
	$(document).ready(function(){
        $('.clickable-cell').on('click', function() {
            var sellerId = $(this).data('seller-id');
            var date = $(this).data('date');
            
            $('#clientsTableBody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div> Cargando...</td></tr>');
            $('#clientsModal').modal('show');

            $.ajax({
                url: '{{ route('reports.portfolio-daily.clients') }}',
                method: 'GET',
                data: { seller_id: sellerId, date: date },
                success: function(data) {
                    var html = '';
                    if (data.length > 0) {
                        data.forEach(function(client) {
                            html += `<tr>
                                <td>${client.name}</td>
                                <td>${client.document}</td>
                                <td class="text-nowrap">S/ ${client.amount}</td>
                                <td class="text-nowrap">${client.date}</td>
                                <td>${client.seller}</td>
                            </tr>`;
                        });
                    } else {
                        html = '<tr><td colspan="5" class="text-center">No se encontraron clientes.</td></tr>';
                    }
                    $('#clientsTableBody').html(html);
                },
                error: function() {
                    $('#clientsTableBody').html('<tr><td colspan="5" class="text-center text-danger">Error al cargar los datos.</td></tr>');
                }
            });
        });

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
