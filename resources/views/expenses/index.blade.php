@extends('template.app')

@section('title', 'Prestamos')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Egresos</li>
    <li class="breadcrumb-item active">Prestamos</li>
  </ol>
</nav>

<div class="card mb-3 border-primary">
	<div class="card-header bg-primary-lt">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
			<h3 class="card-title mb-0">Control de desembolsos del día</h3>
			<div class="d-flex flex-wrap gap-2">
				<a class="btn btn-sm btn-success" href="{{ route('expenses.daily.excel', ['daily_date' => $dailyDate, 'daily_seller_id' => $dailySellerId]) }}" target="_blank">Excel del día</a>
				<a class="btn btn-sm btn-danger" href="{{ route('expenses.daily.pdf', ['daily_date' => $dailyDate, 'daily_seller_id' => $dailySellerId]) }}" target="_blank">PDF del día</a>
			</div>
		</div>
	</div>
	<div class="card-body border-bottom">
		<form method="GET" action="{{ route('expenses.index') }}" class="row g-2 align-items-end">
			<input type="hidden" name="description" value="{{ request()->description }}">
			<input type="hidden" name="seller_id" value="{{ request()->seller_id }}">
			<input type="hidden" name="payment_method_id" value="{{ request()->payment_method_id }}">
			<input type="hidden" name="start_date" value="{{ request()->start_date }}">
			<input type="hidden" name="end_date" value="{{ request()->end_date }}">
			<div class="col-md-3">
				<label class="form-label">Fecha del día</label>
				<input type="date" class="form-control" name="daily_date" value="{{ $dailyDate }}">
			</div>
			@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations'))
			<div class="col-md-3">
				<label class="form-label">Asesor</label>
				<select class="form-select" name="daily_seller_id">
					<option value="">Todos</option>
					@foreach($sellers as $seller)
					<option value="{{ $seller->id }}" @if($dailySellerId == $seller->id) selected @endif>{{ $seller->name }}</option>
					@endforeach
				</select>
			</div>
			@endif
			<div class="col-md-2">
				<button class="btn btn-primary w-100">Ver día</button>
			</div>
		</form>
	</div>
	<div class="card-body pb-0">
		<div class="row g-3 mb-3">
			<div class="col-md-2 col-6">
				<div class="border rounded p-2 text-center">
					<div class="small text-muted">Aprobados</div>
					<div class="fs-3 fw-bold">{{ $dailySummary['approved_count'] }}</div>
				</div>
			</div>
			<div class="col-md-2 col-6">
				<div class="border rounded p-2 text-center border-success">
					<div class="small text-muted">Desembolsados</div>
					<div class="fs-3 fw-bold text-success">{{ $dailySummary['disbursed_count'] }}</div>
				</div>
			</div>
			<div class="col-md-2 col-6">
				<div class="border rounded p-2 text-center border-warning">
					<div class="small text-muted">Pendientes</div>
					<div class="fs-3 fw-bold text-warning">{{ $dailySummary['pending_count'] }}</div>
				</div>
			</div>
			<div class="col-md-2 col-6">
				<div class="border rounded p-2 text-center">
					<div class="small text-muted">Solicitado</div>
					<div class="fw-bold">S/{{ number_format($dailySummary['total_requested'], 2) }}</div>
				</div>
			</div>
			<div class="col-md-2 col-6">
				<div class="border rounded p-2 text-center border-info">
					<div class="small text-muted">Retanqueo BCP</div>
					<div class="fw-bold text-info">S/{{ number_format($dailySummary['total_retainage'], 2) }}</div>
				</div>
			</div>
			<div class="col-md-2 col-6">
				<div class="border rounded p-2 text-center border-primary">
					<div class="small text-muted">Neto a entregar</div>
					<div class="fw-bold text-primary">S/{{ number_format($dailySummary['total_net'], 2) }}</div>
				</div>
			</div>
		</div>
		@if($dailySummary['pending_count'] > 0)
		<div class="alert alert-warning py-2">
			<strong>Atención:</strong> Hay {{ $dailySummary['pending_count'] }} contrato(s) aprobado(s) del día sin registro de desembolso en egresos.
		</div>
		@else
		<div class="alert alert-success py-2">
			Todos los contratos del día tienen desembolso registrado.
		</div>
		@endif
	</div>
	@if($dayBcpPayments->count() > 0)
	<div class="card-body border-top pt-3 pb-0">
		<h4 class="mb-2">Pagos BCP del día (retanqueos)</h4>
		<div class="table-responsive mb-3">
			<table class="table table-sm table-bordered">
				<thead>
					<tr>
						<th>Cliente</th>
						<th>Contrato prev.</th>
						<th>Cuota</th>
						<th>Asesor</th>
						<th>Monto</th>
						<th>Última cuota</th>
						<th>Fecha</th>
					</tr>
				</thead>
				<tbody>
					@foreach($dayBcpPayments as $bcp)
					<tr>
						<td>{{ $bcp['client'] }}</td>
						<td>#{{ $bcp['contract_id'] }}</td>
						<td>{{ $bcp['quota_number'] }}</td>
						<td>{{ $bcp['seller_name'] }}</td>
						<td>S/{{ number_format($bcp['amount'], 2) }}</td>
						<td>
							@if($bcp['is_last_quota'])
							<span class="badge bg-info">Sí</span>
							@else
							<span class="badge bg-secondary">No</span>
							@endif
						</td>
						<td>{{ $bcp['date'] }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table table-vcenter table-striped card-table" id="dailyDisbursementsTable">
			<thead>
				<tr>
					<th class="w-1" title="Marcar revisión manual">OK</th>
					<th>Cliente</th>
					<th>Asesor</th>
					<th>Tipo</th>
					<th>Solicitado</th>
					<th>BCP (ret.)</th>
					<th>Neto</th>
					<th>Estado</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				@if($dailyRows->count() > 0)
				@foreach($dailyRows as $row)
				<tr class="@if($row['pending']) table-warning @endif" data-contract-id="{{ $row['id'] }}">
					<td>
						<input type="checkbox" class="form-check-input daily-check" data-contract-id="{{ $row['id'] }}" @if($row['marked']) checked @endif @if($row['disbursed']) disabled @endif>
					</td>
					<td>
						<strong>{{ $row['client'] }}</strong>
						<div class="small text-muted">{{ $row['document'] ?: $row['group_name'] }}</div>
					</td>
					<td>{{ $row['seller_name'] }}</td>
					<td><span class="badge bg-azure-lt">{{ $row['contract_type'] }}</span></td>
					<td>S/{{ number_format($row['requested_amount'], 2) }}</td>
					<td>
						@if($row['bcp_retainage'] > 0)
						<span class="text-info fw-semibold">S/{{ number_format($row['bcp_retainage'], 2) }}</span>
						@if(count($row['bcp_payments']) > 0)
						<div class="small text-muted">{{ count($row['bcp_payments']) }} pago(s) BCP del día</div>
						@endif
						@else
						<span class="text-muted">—</span>
						@endif
					</td>
					<td class="fw-bold text-primary">S/{{ number_format($row['net_amount'], 2) }}</td>
					<td>
						@if($row['disbursed'])
						<span class="badge bg-success">Desembolsado</span>
						<div class="small">S/{{ number_format($row['disbursed_amount'], 2) }}</div>
						@else
						<span class="badge bg-warning text-dark">Pendiente</span>
						@endif
					</td>
					<td>
						<div class="d-flex gap-1">
							<button type="button" class="btn btn-sm btn-outline-primary btn-daily-detail" data-id="{{ $row['id'] }}" title="Ver detalle">
								<i class="ti ti-eye"></i>
							</button>
							@if($row['pending'] && (auth()->user()->hasRole('seller') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations')))
							<button type="button" class="btn btn-sm btn-primary btn-daily-disburse" data-id="{{ $row['id'] }}" data-net="{{ $row['net_amount'] }}" data-seller="{{ $row['seller_id'] }}" data-client="{{ $row['client'] }}" title="Registrar desembolso">
								<i class="ti ti-cash"></i>
							</button>
							@endif
						</div>
					</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="9" class="text-center text-muted">No hay contratos aprobados para esta fecha</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>

<div class="card">
	<div class="card-header d-flex justify-content-between align-items-center">
		<div>
			@if(auth()->user()->hasRole('seller') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations'))
			<button class="btn btn-primary btn-open-create" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</button>
			@endif
			<a class="btn btn-success" href="{{ route('expenses.excel', request()->all()) }}" target="_blank">Excel</a>
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
				@if(auth()->user()->hasRole('admin'))
				<div class="col-md-3">
					<div class="mb-3">
						<label class="form-label">Asesor comercial</label>
						<select class="form-select" name="seller_id">
							<option value="">Seleccionar</option>
							@foreach($sellers as $seller)
							<option value="{{ $seller->id }}" @if($seller->id == request()->seller_id) selected @endif >{{ $seller->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				@endif
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
			<a href="{{ route('expenses.index') }}" class="btn btn-danger">Limpiar</a>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Descripción</th>
					<th>Asesor comercial</th>
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
					<td>{{ optional($expense->seller)->name }}</td>
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
							<a class="btn btn-primary btn-icon" href="{{ route('expenses.image', ['expense' => $expense->id, 'slot' => 1]) }}" target="_blank" title="Foto 1">
								<i class="ti ti-photo icon"></i>
							</a>
							@endif
							@if($expense->image_2)
							<a class="btn btn-primary btn-icon" href="{{ route('expenses.image', ['expense' => $expense->id, 'slot' => 2]) }}" target="_blank" title="Foto 2">
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
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Descripción</label>
  			  			<input type="text" class="form-control"  value="Desembolso" name="description" id="description" autocomplete="off">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label">Asesor comercial</label>
  			  			<select class="form-select" name="seller_id" id="seller_id" @if(auth()->user()->hasRole('seller')) readonly @endif>
  			  				@if(auth()->user()->hasRole('admin'))
  			  				<option value="">Seleccionar</option>
  			  				@foreach($sellers as $seller)
  			  				<option value="{{ $seller->id }}">{{ $seller->name }}</option>
  			  				@endforeach
  			  				@else
  			  				<option value="{{ auth()->user()->id }}" selected>{{ auth()->user()->name }}</option>
  			  				@endif
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-12" id="contractSelectWrapper">
  			  		<div class="mb-3">
  			  			<label class="form-label">Cliente/Grupo</label>
  			  			<select class="form-select ts-contracts" id="contract_id_ts">
  			  				<option value="">Seleccionar</option>
  			  			</select>
  			  			<input type="hidden" name="contract_id" id="contract_id_hidden" value="">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-12 d-none" id="contractLockedDisplay">
  			  		<div class="mb-3">
  			  			<label class="form-label">Cliente/Grupo</label>
  			  			<input type="text" class="form-control" id="contractLockedLabel" readonly>
  			  		</div>
  			  	</div>
				<div class="col-lg-12 d-none" id="contractDisbursedAlert">
					<div class="alert alert-warning py-2 mb-0">
						<strong>Desembolso ya registrado.</strong> Este préstamo ya tiene un egreso activo por S/<span id="infoDisbursedAmt">0.00</span>. No puede registrar otro desembolso para el mismo préstamo.
					</div>
				</div>
				<div class="col-lg-12 d-none" id="contractRetanqueoAlert">
					<div class="alert alert-info py-2 mb-0">
						<div><strong>Monto solicitado:</strong> S/<span id="infoRequested">0.00</span></div>
						<div><strong>Retanqueo BCP (cuotas pagadas ese día):</strong> S/<span id="infoBcp">0.00</span></div>
						<div><strong>Neto sugerido a entregar:</strong> S/<span id="infoNet" class="fw-bold">0.00</span></div>
						<div class="small mt-1" id="infoBcpDetail"></div>
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
						<label class="form-label">Imagen 1</label>
						<input type="file" class="form-control" name="image" id="image" accept=".jpg,.jpeg,.png,.webp">
					</div>
				</div>
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Imagen 2</label>
						<input type="file" class="form-control" name="image_2" id="image_2" accept=".jpg,.jpeg,.png,.webp">
					</div>
				</div>
			  </div>
  			</div>
  			<div class="modal-footer">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  			  <button type="submit" class="btn btn-primary" id="storeSubmitBtn"><i class="ti ti-device-floppy icon"></i> Guardar</button>
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
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label required">Descripción</label>
  			  			<input type="text" class="form-control" name="description" id="editDescription" autocomplete="off">
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label">Asesor comercial</label>
  			  			<select class="form-select" name="seller_id" id="editSellerId">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($sellers as $seller)
  			  				<option value="{{ $seller->id }}">{{ $seller->name }}</option>
  			  				@endforeach
  			  			</select>
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
						<label class="form-label">Imagen 1</label>
						<div id="editImage1Current" class="small mb-1 d-none">
							<a href="#" target="_blank" id="editImage1Link">Ver imagen actual</a>
						</div>
						<input type="file" class="form-control" name="image" id="editImage" accept=".jpg,.jpeg,.png,.webp">
					</div>
				</div>
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label">Imagen 2</label>
						<div id="editImage2Current" class="small mb-1 d-none">
							<a href="#" target="_blank" id="editImage2Link">Ver imagen actual</a>
						</div>
						<input type="file" class="form-control" name="image_2" id="editImage2" accept=".jpg,.jpeg,.png,.webp">
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

<div class="modal modal-blur fade" id="dailyDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  		  <h5 class="modal-title">Detalle del contrato</h5>
  		  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
  		<div class="modal-body" id="dailyDetailBody">
  			<div class="text-center text-muted py-4">Cargando...</div>
  		</div>
  		<div class="modal-footer">
  		  <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
  		  <button type="button" class="btn btn-primary d-none" id="dailyDetailDisburseBtn">Registrar desembolso</button>
  		</div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>

	var dailyDate = '{{ $dailyDate }}';
	var tsContracts;
	var contractLockedFlag = false;

	function getSelectedContractId(){
		return $('#contract_id_hidden').val() || (tsContracts ? tsContracts.getValue() : '');
	}

	function syncContractIdHidden(){
		var val = tsContracts ? tsContracts.getValue() : '';
		$('#contract_id_hidden').val(val || '');
	}

	function lockContractSelection(contractId, clientLabel){
		$('#contract_id_hidden').val(contractId);
		$('#contractSelectWrapper').addClass('d-none');
		$('#contractLockedDisplay').removeClass('d-none');
		$('#contractLockedLabel').val(clientLabel || '');
		if(tsContracts){
			tsContracts.clear(true);
			tsContracts.disable();
		}
	}

	function unlockContractSelection(){
		$('#contract_id_hidden').val('');
		$('#contractSelectWrapper').removeClass('d-none');
		$('#contractLockedDisplay').addClass('d-none');
		$('#contractLockedLabel').val('');
		if(tsContracts){
			tsContracts.enable();
			tsContracts.clear(true);
		}
	}

	function loadContractRetanqueoInfo(contractId){
		if(!contractId){
			$('#contractRetanqueoAlert').addClass('d-none');
			$('#contractDisbursedAlert').addClass('d-none');
			$('#storeSubmitBtn').prop('disabled', false);
			return;
		}
		$.get('{{ url("expenses/contract-info") }}/' + contractId, { date: $('#date').val() || dailyDate }, function(res){
			if(!res.status) return;
			var d = res.data;
			if(d.disbursed){
				$('#infoDisbursedAmt').text(parseFloat(d.disbursed_amount || 0).toFixed(2));
				$('#contractDisbursedAlert').removeClass('d-none');
				$('#contractRetanqueoAlert').addClass('d-none');
				$('#storeSubmitBtn').prop('disabled', true);
				return;
			}
			$('#contractDisbursedAlert').addClass('d-none');
			$('#storeSubmitBtn').prop('disabled', false);
			$('#infoRequested').text(parseFloat(d.requested_amount).toFixed(2));
			$('#infoBcp').text(parseFloat(d.bcp_retainage).toFixed(2));
			$('#infoNet').text(parseFloat(d.net_amount).toFixed(2));
			var detail = '';
			if(d.bcp_payments && d.bcp_payments.length){
				d.bcp_payments.forEach(function(p){
					detail += '<div>• ' + p.contract_label + ' cuota #' + p.quota_number + ': S/' + parseFloat(p.amount).toFixed(2) + '</div>';
				});
			} else {
				detail = 'Sin retanqueo BCP en contratos previos ese día.';
			}
			$('#infoBcpDetail').html(detail);
			$('#contractRetanqueoAlert').removeClass('d-none');
			if(!$('#payment_amount').val()){
				$('#payment_amount').val(parseFloat(d.net_amount).toFixed(2));
			}
			if(d.seller_id){
				$('#seller_id').val(d.seller_id);
			}
		});
	}

	function renderDailyDetail(d){
		var bcpHtml = '';
		if(d.bcp_payments && d.bcp_payments.length){
			bcpHtml = '<ul class="mb-0">';
			d.bcp_payments.forEach(function(p){
				bcpHtml += '<li>' + p.contract_label + ' — cuota #' + p.quota_number + ': S/' + parseFloat(p.amount).toFixed(2) + '</li>';
			});
			bcpHtml += '</ul>';
		} else {
			bcpHtml = '<span class="text-muted">No aplica retanqueo BCP ese día.</span>';
		}
		var prevHtml = '';
		if(d.previous_contracts && d.previous_contracts.length){
			prevHtml = '<table class="table table-sm"><thead><tr><th>Contrato</th><th>Monto</th><th>Fecha</th><th>Asesor</th></tr></thead><tbody>';
			d.previous_contracts.forEach(function(c){
				prevHtml += '<tr><td>' + c.client + '</td><td>S/' + parseFloat(c.requested_amount).toFixed(2) + '</td><td>' + c.date + '</td><td>' + (c.seller_name || '') + '</td></tr>';
			});
			prevHtml += '</tbody></table>';
		} else {
			prevHtml = '<span class="text-muted">Sin contratos previos.</span>';
		}
		var status = d.disbursed ? '<span class="badge bg-success">Desembolsado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>';
		return '<div class="row g-3">'
			+ '<div class="col-md-6"><strong>Cliente:</strong> ' + d.client + '</div>'
			+ '<div class="col-md-6"><strong>Asesor:</strong> ' + (d.seller_name || '') + '</div>'
			+ '<div class="col-md-6"><strong>Documento/Grupo:</strong> ' + (d.document || d.group_name || '') + '</div>'
			+ '<div class="col-md-6"><strong>Tipo:</strong> ' + d.contract_type + '</div>'
			+ '<div class="col-md-6"><strong>Teléfono:</strong> ' + (d.phone || '—') + '</div>'
			+ '<div class="col-md-6"><strong>Dirección:</strong> ' + (d.address || '—') + '</div>'
			+ '<div class="col-md-4"><strong>Solicitado:</strong> S/' + parseFloat(d.requested_amount).toFixed(2) + '</div>'
			+ '<div class="col-md-4"><strong>Retanqueo BCP:</strong> S/' + parseFloat(d.bcp_retainage).toFixed(2) + '</div>'
			+ '<div class="col-md-4"><strong>Neto:</strong> S/' + parseFloat(d.net_amount).toFixed(2) + '</div>'
			+ '<div class="col-md-6"><strong>Cuotas:</strong> ' + d.quotas_number + ' — Cuota: S/' + parseFloat(d.quota_amount).toFixed(2) + '</div>'
			+ '<div class="col-md-6"><strong>Estado desembolso:</strong> ' + status + '</div>'
			+ '<div class="col-12"><strong>Retanqueos BCP detectados:</strong>' + bcpHtml + '</div>'
			+ '<div class="col-12"><strong>Contratos anteriores del cliente:</strong>' + prevHtml + '</div>'
			+ '</div>';
	}

	function openDisburseForContract(contractId, netAmount, sellerId, clientLabel){
		contractLockedFlag = true;
		lockContractSelection(contractId, clientLabel || '');
		$('#date').val(dailyDate);
		$('#createModal').modal('show');
		if(netAmount){
			$('#payment_amount').val(parseFloat(netAmount).toFixed(2));
		}
		if(sellerId){
			$('#seller_id').val(sellerId);
		}
		loadContractRetanqueoInfo(contractId);
	}

	$(document).ready(function(){

		tsContracts = new TomSelect('.ts-contracts', {
			valueField: 'id',
			labelField: ['name', 'group_name'],
			searchField: ['name', 'group_name'],
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
			optionClass:'dropdown-item',
			load: function(query, callback){
				$.ajax({
					url: '{{ route("contracts.api") }}?q=' + encodeURIComponent(query),
					method: 'GET',
					success: function(data){
						callback(data.items);
					},
					error: function(err){
						console.log(err);
					}
				})
			},
			render: {
				item: function(data, escape) {
					return `<div>${ data.client_type == 'Personal' ? escape(data.name) : escape(data.group_name) } - S/${escape(data.requested_amount)}</div>`;
				},
				option: function(data, escape) {
					return `<div>${ data.client_type == 'Personal' ? escape(data.name) : escape(data.group_name) } - S/${escape(data.requested_amount)}</div>`;
				},
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			},
			onChange: function(value){
				syncContractIdHidden();
				loadContractRetanqueoInfo(value);
			},
		});

		$('.btn-open-create').on('click', function(){
			contractLockedFlag = false;
			unlockContractSelection();
		});

	});

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
	$('#createModal').on('hidden.bs.modal', function(){
		contractLockedFlag = false;
		unlockContractSelection();
	});

	$('#createModal').on('show.bs.modal', function(){
		if(!contractLockedFlag){
			unlockContractSelection();
		}
		$('#payment_method_block_2').addClass('d-none');
		$('#payment_method_id_2').val('');
		$('#payment_amount_2').val('');
		$('#addPaymentBtn').prop('disabled', false);
		$('#contractRetanqueoAlert').addClass('d-none');
		$('#contractDisbursedAlert').addClass('d-none');
		$('#storeSubmitBtn').prop('disabled', false);
	});

	$('#date').on('change', function(){
		var cid = getSelectedContractId();
		loadContractRetanqueoInfo(cid);
	});

	$(document).on('change', '.daily-check', function(){
		var $el = $(this);
		$.post('{{ route("expenses.daily-check") }}', {
			_token: '{{ csrf_token() }}',
			contract_id: $el.data('contract-id'),
			date: dailyDate,
			marked: $el.is(':checked') ? 1 : 0
		}).fail(function(){
			ToastError.fire({ text: 'No se pudo guardar la marca' });
			$el.prop('checked', !$el.is(':checked'));
		});
	});

	$(document).on('click', '.btn-daily-detail', function(){
		var id = $(this).data('id');
		$('#dailyDetailBody').html('<div class="text-center text-muted py-4">Cargando...</div>');
		$('#dailyDetailDisburseBtn').addClass('d-none');
		$('#dailyDetailModal').modal('show');
		$.get('{{ url("expenses/daily-contract") }}/' + id, { date: dailyDate }, function(res){
			if(!res.status){
				$('#dailyDetailBody').html('<div class="text-danger">No se pudo cargar el detalle</div>');
				return;
			}
			var d = res.data;
			$('#dailyDetailBody').html(renderDailyDetail(d));
			if(d.pending){
				$('#dailyDetailDisburseBtn').removeClass('d-none').off('click').on('click', function(){
					$('#dailyDetailModal').modal('hide');
					openDisburseForContract(d.id, d.net_amount, d.seller_id, d.client);
				});
			}
		});
	});

	$(document).on('click', '.btn-daily-disburse', function(){
		openDisburseForContract($(this).data('id'), $(this).data('net'), $(this).data('seller'), $(this).data('client'));
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

		var $submitBtn = $('#storeSubmitBtn');
		if($submitBtn.prop('disabled')){
			return;
		}
		$submitBtn.prop('disabled', true);

		var fd = new FormData();

		fd.append('description', $('#description').val());
		fd.append('seller_id', $('#seller_id').val());
		fd.append('contract_id', getSelectedContractId());
	fd.append('payment_method_id', $('#payment_method_id').val());
		fd.append('payment_amount', $('#payment_amount').val());
		fd.append('date', $('#date').val());
		if ($('#image')[0].files[0]) {
			fd.append('image', $('#image')[0].files[0]);
		}
		if ($('#image_2')[0].files[0]) {
			fd.append('image_2', $('#image_2')[0].files[0]);
		}

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
					$submitBtn.prop('disabled', false);
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				$submitBtn.prop('disabled', false);
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
				$('#editSellerId').val(data.seller_id);
				// total mostrado, no hay input global editAmount
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
				if (data.has_image) {
					$('#editImage1Current').removeClass('d-none');
					$('#editImage1Link').attr('href', '{{ url("expenses") }}/' + data.id + '/image/1');
				} else {
					$('#editImage1Current').addClass('d-none');
				}
				if (data.has_image_2) {
					$('#editImage2Current').removeClass('d-none');
					$('#editImage2Link').attr('href', '{{ url("expenses") }}/' + data.id + '/image/2');
				} else {
					$('#editImage2Current').addClass('d-none');
				}
				$('#editImage').val('');
				$('#editImage2').val('');
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
		fd.append('seller_id', $('#editSellerId').val());
	fd.append('payment_method_id', $('#editPaymentMethodId').val());
	fd.append('payment_amount', $('#editPaymentAmount').val());
		fd.append('date', $('#editDate').val());
		if ($('#editImage')[0].files[0]) {
			fd.append('image', $('#editImage')[0].files[0]);
		}
		if ($('#editImage2')[0].files[0]) {
			fd.append('image_2', $('#editImage2')[0].files[0]);
		}
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
