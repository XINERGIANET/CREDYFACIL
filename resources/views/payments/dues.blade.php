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
	<div class="card-header">
		<a class="btn btn-success" href="{{ route('payments.dues.excel', request()->all()) }}" target="_blank">Excel</a>
	</div>
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
					<th>Asesor</th>
					<th>Deuda total (mora)</th>
					<th>Monto prestado</th>
					<th>Fecha desembolso</th>
					<th>Cuotas pagadas</th>
					<th>Cuotas totales</th>
					<th>Importe cuota</th>
					<th>Saldo capital</th>
					<th>Cuotas en mora</th>
					<th>Días mora (máx.)</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				@if($groupedClients->count() > 0)
				@foreach($groupedClients as $row)
				<tr>
					<td>{{ $row->client_name }}</td>
					<td>{{ $row->seller_name }}</td>
					<td class="text-danger fw-semibold">S/{{ number_format($row->total_overdue_debt, 2) }}</td>
					<td>S/{{ number_format($row->requested_amount, 2) }}</td>
					<td>{{ $row->disbursement_date }}</td>
					<td>{{ $row->paid_quotas }}</td>
					<td>{{ $row->total_quotas }}</td>
					<td>S/{{ number_format($row->quota_amount, 2) }}</td>
					<td>S/{{ number_format($row->capital_balance, 2) }}</td>
					<td>{{ $row->overdue_quotas_count }}</td>
					<td>{{ $row->days_overdue }}</td>
					<td>
						@if($row->next_quota_id)
						<button class="btn btn-primary btn-pay"
							data-contract-id="{{ $row->contract_id }}"
							data-quota-id="{{ $row->next_quota_id }}"
							data-amount="{{ $row->next_quota_debt }}"
							data-client="{{ $row->client_name }}"
							data-people="{{ optional($row->contract)->people }}"
							title="Cobrar siguiente cuota">
							<i class="ti ti-cash"></i>
						</button>
						@endif
					</td>
				@endforeach
				@else
				<tr>
					<td colspan="12" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($groupedClients->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $groupedClients->withQueryString()->links() }}
	</div>
	@endif
</div>
	</div>
	</div>

	<div class="modal modal-blur fade" id="payModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<form id="payForm" method="POST">
					<div class="modal-header">
						<h5 class="modal-title">Cobrar cuota en mora</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="row">
							<input type="hidden" name="contract_id" id="pay_contract_id">
							<input type="hidden" name="quota_id" id="pay_quota_id">
							<div class="col-lg-12">
								<div class="mb-3">
									<label class="form-label">Cliente/Grupo</label>
									<input type="text" class="form-control" id="pay_client_name" readonly>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="mb-3">
									<label class="form-label required">Monto a cobrar</label>
									<input type="text" class="form-control" name="amount" id="pay_amount">
								</div>
							</div>
							<div class="col-lg-6">
								<div class="mb-3">
									<label class="form-label required">Método de pago</label>
									<select class="form-select" name="payment_method_id" id="pay_payment_method_id">
										<option value="">Seleccionar</option>
										@foreach($payment_methods as $payment_method)
											<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
										@endforeach
									</select>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="mb-3">
									<label class="form-label required">Fecha</label>
									@if(auth()->user()->hasRole('admin'))
										<input type="date" class="form-control" name="date" id="pay_date" value="{{ now()->format('Y-m-d') }}">
									@else
										<input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" disabled>
										<input type="hidden" name="date" id="pay_date" value="{{ now()->format('Y-m-d') }}">
									@endif
								</div>
							</div>
							<div class="col-lg-6">
								<div class="mb-3">
									<label class="form-label">Imagen</label>
									<input type="file" class="form-control" name="image" id="pay_image" accept=".jpg,.jpeg,.png,.webp">
								</div>
							</div>
						</div>
						<div class="mb-3" id="divPeopleContainer" style="display:none;">
							<label class="form-label">Lista de personas</label>
							<div id="divPeople"></div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
						<button type="submit" class="btn btn-primary" id="btn-save-pay">Guardar pago</button>
					</div>
				</form>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
<script>
	$(document).on('click', '.btn-pay', function() {
		var btn = $(this);
		$('#pay_contract_id').val(btn.data('contract-id'));
		$('#pay_quota_id').val(btn.data('quota-id'));
		$('#pay_client_name').val(btn.data('client'));
		$('#pay_amount').val(btn.data('amount'));
		
		var people = btn.data('people');
		var html = '';
		if(people && people != 'null'){
			try {
				var peopleArr = typeof people === 'string' ? JSON.parse(people) : people;
				if(peopleArr.length > 0){
					peopleArr.forEach(function(client){
						html += `
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="people[]" value="${client.document}">
								<span class="form-check-label">${client.name}</span>
							</div>
						`;
					});
					$('#divPeople').html(html);
					$('#divPeopleContainer').show();
				} else {
					$('#divPeopleContainer').hide();
				}
			} catch(e) {
				$('#divPeopleContainer').hide();
			}
		} else {
			$('#divPeopleContainer').hide();
		}
		
		$('#payModal').modal('show');
	});

	$('#payForm').submit(function(e) {
		e.preventDefault();
		$('#btn-save-pay').prop('disabled', true);

		var fd = new FormData(this);

		$.ajax({
			url: '{{ route('payments.store') }}',
			method: 'POST',
			processData: false,
			contentType: false,
			data: fd,
			success: function(data) {
				if (data.status) {
					$('#payModal').modal('hide');
					ToastMessage.fire({ text: 'Pago registrado correctamente' })
						.then(() => location.reload());
				} else {
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
					$('#btn-save-pay').prop('disabled', false);
				}
			},
			error: function(err) {
				ToastError.fire({ text: 'Ocurrió un error al procesar el pago' });
				$('#btn-save-pay').prop('disabled', false);
			}
		});
	});
</script>
@endsection