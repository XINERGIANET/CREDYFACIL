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
			<input type="hidden" name="portfolio_date" value="{{ request()->portfolio_date }}">
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
		<input type="hidden" name="portfolio_date" value="{{ request()->portfolio_date }}">
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
					@foreach($accountBalances as $accountBalance)
					<li class="fs-3 fw-semibold">{{ $accountBalance['name'] }}: S/{{ number_format($accountBalance['balance'], 2) }}</li>
					@endforeach
				</ul>
				@if(auth()->user()->hasRole('admin'))
				<a href="{{ route('account-movements.index') }}" class="btn btn-sm btn-primary">
					<i class="ti ti-cash icon"></i> Cuadrar cuentas
				</a>
				@endif
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
			<input type="hidden" name="portfolio_date" value="{{ request()->portfolio_date }}">
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

@php
    $portfolioDate = request()->portfolio_date ?? now()->format('Y-m-d');
    $displayDate = \Carbon\Carbon::parse($portfolioDate)->format('d/m/Y');
@endphp
<div class="card mb-3 shadow-sm">
    <div class="card-header">
        <h3 class="card-title mb-0">Reportes de cartera</h3>
    </div>
    <div class="card-body">
        <form class="row g-3 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label">Fecha del reporte</label>
                <input type="date" class="form-control" name="portfolio_date" value="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-filter icon"></i> Filtrar reportes
                </button>
            </div>
            <input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
            <input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
            <input type="hidden" name="start_date_2" value="{{ request()->start_date_2 }}">
            <input type="hidden" name="end_date_2" value="{{ request()->end_date_2 }}">
            <input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
            <input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
            <input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
            <input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
            <input type="hidden" name="seller_id_1" value="{{ request()->seller_id_1 }}">
            <input type="hidden" name="seller_id_2" value="{{ request()->seller_id_2 }}">
        </form>
    </div>
</div>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0" style="color: #00E5E5 !important;">Reporte de Cartera al Día ({{ $displayDate }})</h3>
        <a class="btn btn-sm btn-success btn-report-excel" href="{{ route('reports.portfolio-daily.excel', ['date' => \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d')]) }}" target="_blank">
            <i class="ti ti-file-spreadsheet"></i>
            <span>Excel</span>
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
            .bg-overdue-title { background-color: #F27AF0 !important; color: #000 !important; font-size: 1rem !important; font-weight: 800; }
            .bg-overdue-alert { background-color: #FFC7CE !important; color: #C00000 !important; }
            .portfolio-detail-cell { cursor: pointer; text-decoration: underline; text-underline-offset: 2px; }
            .table-overdue th, .table-overdue td { border-color: #000 !important; color: #000; }
            .table-overdue .bg-grey-report { color: #fff !important; }
            .btn-report-excel { gap: 6px; min-height: 32px; padding: 6px 10px; line-height: 1; font-weight: 600; }
            .btn-report-excel i { font-size: 18px; line-height: 1; }
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
                        <td class="bg-grey-report portfolio-detail-cell" data-metric="initial_clients" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[1], 0) }}</td>
                        <td class="portfolio-detail-cell" data-metric="current_clients" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[2], 0) }}</td>
                        <td class="portfolio-detail-cell" data-metric="client_growth" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[3], 0) }}</td>
                        <td class="bg-grey-report portfolio-detail-cell" data-metric="new_clients" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[4], 0) }}</td>
                        <td class="bg-yellow-report portfolio-detail-cell" data-metric="new_goal" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[5], 0) }}</td>
                        <td class="bg-green-report fw-semibold portfolio-detail-cell" data-metric="new_percent" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ $row[6] ? number_format($row[6] * 100, 2) . '%' : '-' }}</td>
                        <td class="bg-grey-report portfolio-detail-cell" data-metric="initial_wallet" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($row[7], 1) }}</td>
                        <td class="portfolio-detail-cell" data-metric="current_wallet" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($row[8], 1) }}</td>
                        <td class="portfolio-detail-cell" data-metric="wallet_growth" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($row[9], 1) }}</td>
                        <td class="bg-red-report portfolio-detail-cell" data-metric="overdue_percent" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ $row[10] ? number_format($row[10] * 100, 2) . '%' : '-' }}</td>
                        <td class="bg-grey-report portfolio-detail-cell" data-metric="previous_disbursement" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($row[11], 1) }}</td>
                        <td class="bg-grey-report portfolio-detail-cell" data-metric="previous_operations" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[12], 0) }}</td>
                        <td class="portfolio-detail-cell" data-metric="current_disbursement" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($row[13], 0) }}</td>
                        <td class="portfolio-detail-cell" data-metric="current_operations" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($row[14], 0) }}</td>
                        <td class="bg-yellow-report portfolio-detail-cell" data-metric="disbursement_goal" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($row[15], 0) }}</td>
                        <td class="bg-green-report fw-semibold portfolio-detail-cell" data-metric="disbursement_percent" data-seller-id="{{ $sellerId }}" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ $row[16] ? number_format($row[16] * 100, 2) . '%' : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-total-report">
                    @php $totals = $portfolioReport['totals']; @endphp
                    <td>{{ $totals[0] }}</td>
                    <td class="portfolio-detail-cell" data-metric="initial_clients" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[1], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="current_clients" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[2], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="client_growth" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[3], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="new_clients" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[4], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="new_goal" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[5], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="new_percent" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ $totals[6] ? number_format($totals[6] * 100, 2) . '%' : '-' }}</td>
                    <td class="portfolio-detail-cell" data-metric="initial_wallet" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($totals[7], 1) }}</td>
                    <td class="portfolio-detail-cell" data-metric="current_wallet" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($totals[8], 1) }}</td>
                    <td class="portfolio-detail-cell" data-metric="wallet_growth" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($totals[9], 1) }}</td>
                    <td class="portfolio-detail-cell" data-metric="overdue_percent" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ $totals[10] ? number_format($totals[10] * 100, 2) . '%' : '-' }}</td>
                    <td class="portfolio-detail-cell" data-metric="previous_disbursement" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($totals[11], 1) }}</td>
                    <td class="portfolio-detail-cell" data-metric="previous_operations" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[12], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="current_disbursement" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($totals[13], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="current_operations" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ number_format($totals[14], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="disbursement_goal" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">S/{{ number_format($totals[15], 0) }}</td>
                    <td class="portfolio-detail-cell" data-metric="disbursement_percent" data-seller-id="" data-date="{{ \Carbon\Carbon::parse($portfolioDate)->format('Y-m-d') }}" title="Ver detalle">{{ $totals[16] ? number_format($totals[16] * 100, 2) . '%' : '-' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@php
    $overdueDate = $portfolioOverdueReport['date'];
    $overdueMoney = function ($value) {
        return $value > 0 ? 'S/ ' . number_format($value, 1) : 'S/ -';
    };
    $overduePercent = function ($value, $decimals = 1) {
        return number_format($value * 100, $decimals) . '%';
    };
@endphp
<div class="card mb-4 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-portfolio table-overdue mb-0">
            <thead>
                <tr>
                    <th class="bg-overdue-title" colspan="12">REPORTE DE CARTERA MOROSA CREDYFACIL AL {{ $overdueDate->format('d/m/Y') }}</th>
                </tr>
                <tr>
                    <th></th>
                    <th>AVANCE CARTERA</th>
                    <th>MORA<br>1 a 7</th>
                    <th>%</th>
                    <th>MORA<br>8 a 30</th>
                    <th>%</th>
                    <th>MORA<br>&gt;7</th>
                    <th>%</th>
                    <th>MORA &gt;60</th>
                    <th>%</th>
                    <th>MORA<br>TOTAL</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($portfolioOverdueReport['rows'] as $row)
                    <tr>
                        <td class="fw-bold text-start">{{ $row['seller'] }}</td>
                        <td class="bg-yellow-report fw-bold text-nowrap portfolio-detail-cell" data-metric="current_wallet" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">S/ {{ number_format($row['wallet'], 1) }}</td>
                        <td class="bg-grey-report fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_1_7" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overdueMoney($row['mora_1_7']) }}</td>
                        <td class="fw-bold portfolio-detail-cell {{ $row['mora_1_7_percent'] > 0 ? 'bg-overdue-alert' : '' }}" data-metric="mora_1_7" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($row['mora_1_7_percent']) }}</td>
                        <td class="bg-grey-report fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_8_30" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overdueMoney($row['mora_8_30']) }}</td>
                        <td class="fw-bold portfolio-detail-cell {{ $row['mora_8_30_percent'] > 0 ? 'bg-overdue-alert' : '' }}" data-metric="mora_8_30" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($row['mora_8_30_percent']) }}</td>
                        <td class="bg-grey-report fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_gt_7" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overdueMoney($row['mora_gt_7']) }}</td>
                        <td class="fw-bold portfolio-detail-cell {{ $row['mora_gt_7_percent'] > 0 ? 'bg-overdue-alert' : '' }}" data-metric="mora_gt_7" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($row['mora_gt_7_percent'], 2) }}</td>
                        <td class="bg-grey-report fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_gt_60" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overdueMoney($row['mora_gt_60']) }}</td>
                        <td class="fw-bold portfolio-detail-cell {{ $row['mora_gt_60_percent'] > 0 ? 'bg-overdue-alert' : '' }}" data-metric="mora_gt_60" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($row['mora_gt_60_percent']) }}</td>
                        <td class="bg-grey-report fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_total" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overdueMoney($row['mora_total']) }}</td>
                        <td class="fw-bold portfolio-detail-cell {{ $row['mora_total_percent'] > 0 ? 'bg-overdue-alert' : '' }}" data-metric="mora_total" data-seller-id="{{ $row['seller_id'] }}" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($row['mora_total_percent']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php $overdueTotals = $portfolioOverdueReport['totals']; @endphp
                <tr class="bg-total-report">
                    <td class="fw-bold text-start">CREDYFACIL</td>
                    <td class="fw-bold text-nowrap portfolio-detail-cell" data-metric="current_wallet" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ number_format($overdueTotals['wallet'], 1) }}</td>
                    <td class="fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_1_7" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">S/ {{ number_format($overdueTotals['mora_1_7'], 1) }}</td>
                    <td class="fw-bold portfolio-detail-cell" data-metric="mora_1_7" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($overdueTotals['mora_1_7_percent'], 2) }}</td>
                    <td class="fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_8_30" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">S/ {{ number_format($overdueTotals['mora_8_30'], 1) }}</td>
                    <td class="fw-bold portfolio-detail-cell" data-metric="mora_8_30" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($overdueTotals['mora_8_30_percent'], 2) }}</td>
                    <td class="fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_gt_7" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">S/ {{ number_format($overdueTotals['mora_gt_7'], 1) }}</td>
                    <td class="fw-bold portfolio-detail-cell" data-metric="mora_gt_7" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($overdueTotals['mora_gt_7_percent'], 2) }}</td>
                    <td class="fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_gt_60" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">S/ {{ number_format($overdueTotals['mora_gt_60'], 1) }}</td>
                    <td class="fw-bold portfolio-detail-cell" data-metric="mora_gt_60" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($overdueTotals['mora_gt_60_percent'], 2) }}</td>
                    <td class="fw-bold text-nowrap portfolio-detail-cell" data-metric="mora_total" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">S/ {{ number_format($overdueTotals['mora_total'], 1) }}</td>
                    <td class="fw-bold portfolio-detail-cell" data-metric="mora_total" data-seller-id="" data-date="{{ $overdueDate->format('Y-m-d') }}" title="Ver detalle">{{ $overduePercent($overdueTotals['mora_total_percent'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<h2>Indicadores de productividad</h2>
<div class="card mb-3 shadow-sm">
	<div class="card-body">
		<form class="row g-3 align-items-end">
			<div class="col-md-3">
				<label class="form-label">Fecha desde</label>
				<input type="date" class="form-control" name="start_date_2" value="{{ request()->start_date_2 }}">
			</div>
			<div class="col-md-3">
				<label class="form-label">Fecha hasta</label>
				<input type="date" class="form-control" name="end_date_2" value="{{ request()->end_date_2 }}">
			</div>
			@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
			<div class="col-md-3">
				<label class="form-label">Asesor comercial</label>
				<select class="form-select" name="seller_id_2">
					<option value="">Seleccionar</option>
					@foreach($sellers as $seller)
					<option value="{{ $seller->id }}" @if($seller->id == request()->seller_id_2) selected @endif>{{ $seller->name }}</option>
					@endforeach
				</select>
			</div>
			@endif
			<div class="col-md-auto">
				<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar productividad</button>
			</div>
			<input type="hidden" name="start_date_1" value="{{ request()->start_date_1 }}">
			<input type="hidden" name="end_date_1" value="{{ request()->end_date_1 }}">
			<input type="hidden" name="start_date_3" value="{{ request()->start_date_3 }}">
			<input type="hidden" name="end_date_3" value="{{ request()->end_date_3 }}">
			<input type="hidden" name="start_date_4" value="{{ request()->start_date_4 }}">
			<input type="hidden" name="end_date_4" value="{{ request()->end_date_4 }}">
			<input type="hidden" name="seller_id_1" value="{{ request()->seller_id_1 }}">
			<input type="hidden" name="portfolio_date" value="{{ request()->portfolio_date }}">
		</form>
	</div>
</div>
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
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
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
        $('.portfolio-detail-cell').on('click', function() {
            var sellerId = $(this).data('seller-id');
            var date = $(this).data('date');
            var metric = $(this).data('metric');
            
            $('#clientsModal .modal-title').text('Detalle');
            $('#clientsModal thead').html('<tr><th>Detalle</th></tr>');
            $('#clientsTableBody').html('<tr><td class="text-center"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div> Cargando...</td></tr>');
            $('#clientsModal').modal('show');

            $.ajax({
                url: '{{ route('reports.portfolio-daily.clients') }}',
                method: 'GET',
                data: { seller_id: sellerId, date: date, metric: metric },
                success: function(data) {
                    var headers = data.headers || [];
                    var rows = data.rows || [];
                    var colspan = Math.max(headers.length, 1);
                    var html = '';
                    var head = '<tr>';

                    $('#clientsModal .modal-title').text(data.title || 'Detalle');
                    headers.forEach(function(header) {
                        head += `<th>${header}</th>`;
                    });
                    head += '</tr>';
                    $('#clientsModal thead').html(head);

                    if (data.subtitle) {
                        html += `<tr><td colspan="${colspan}" class="text-muted">${data.subtitle}</td></tr>`;
                    }

                    if (rows.length > 0) {
                        rows.forEach(function(row) {
                            html += '<tr>';
                            row.forEach(function(value) {
                                html += `<td>${value ?? ''}</td>`;
                            });
                            html += '</tr>';
                        });
                    } else {
                        html += `<tr><td colspan="${colspan}" class="text-center">No se encontraron datos.</td></tr>`;
                    }
                    $('#clientsTableBody').html(html);
                },
                error: function() {
                    $('#clientsTableBody').html('<tr><td class="text-center text-danger">Error al cargar los datos.</td></tr>');
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
