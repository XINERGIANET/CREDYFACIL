@extends('template.app')

@section('title', 'Caja y cuentas')

@section('content')
<nav class="mb-2">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Caja y cuentas</li>
    </ol>
</nav>

@if(session('message'))
<div class="alert alert-success">{{ session('message') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

@php
    $balanceMap = $balances->keyBy('id')->map(function ($balance) {
        return [
            'name' => $balance['name'],
            'balance' => $balance['balance'],
        ];
    });
@endphp

<div class="row">
    @foreach($balances as $balance)
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="text-muted">{{ $balance['name'] }}</div>
                <div class="fs-2 fw-bold">S/{{ number_format($balance['balance'], 2) }}</div>
                <div class="small text-muted">
                    Ingresos: S/{{ number_format($balance['payments'] + $balance['manual_in'] + $balance['transfers_in'], 2) }}<br>
                    Egresos: S/{{ number_format($balance['expenses'] + $balance['manual_out'] + $balance['transfers_out'], 2) }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Movimientos manuales</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="ti ti-plus icon"></i> Registrar movimiento
        </button>
    </div>
    <div class="card-body border-bottom">
        <form>
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Cuenta</label>
                        <select class="form-select" name="payment_method_id">
                            <option value="">Todas</option>
                            @foreach($payment_methods as $payment_method)
                            <option value="{{ $payment_method->id }}" @if($payment_method->id == request()->payment_method_id) selected @endif>{{ $payment_method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="type">
                            <option value="">Todos</option>
                            <option value="income" @if(request()->type == 'income') selected @endif>Ingreso</option>
                            <option value="expense" @if(request()->type == 'expense') selected @endif>Egreso</option>
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
            <button class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
            <a href="{{ route('account-movements.index') }}" class="btn btn-danger">Limpiar</a>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cuenta</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement->date->format('d/m/Y') }}</td>
                    <td>{{ optional($movement->paymentMethod)->name }}</td>
                    <td>
                        <span class="badge {{ $movement->type === 'income' ? 'bg-success' : 'bg-danger' }}">
                            {{ $movement->typeName() }}
                        </span>
                    </td>
                    <td>{{ $movement->description }}</td>
                    <td class="{{ $movement->type === 'income' ? 'text-success' : 'text-danger' }} fw-bold">
                        {{ $movement->type === 'income' ? '+' : '-' }} S/{{ number_format($movement->amount, 2) }}
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-icon btn-edit" data-id="{{ $movement->id }}">
                                <i class="ti ti-pencil icon"></i>
                            </button>
                            <form method="POST" action="{{ route('account-movements.destroy', $movement) }}" onsubmit="return confirm('¿Eliminar este movimiento?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-icon">
                                    <i class="ti ti-x icon"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No se han encontrado movimientos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $movements->withQueryString()->links() }}
    </div>
    @endif
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('account-movements.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Registrar movimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('account_movements.partials.form', ['prefix' => '', 'movement' => null])
                    @include('account_movements.partials.preview', ['prefix' => ''])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Editar movimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('account_movements.partials.form', ['prefix' => 'edit_', 'movement' => null])
                    @include('account_movements.partials.preview', ['prefix' => 'edit_'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        var balances = @json($balanceMap);

        function formatMoney(value) {
            return 'S/' + Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updatePreview(prefix) {
            var accountId = $('#' + prefix + 'payment_method_id').val();
            var type = $('#' + prefix + 'type').val();
            var amount = parseFloat($('#' + prefix + 'amount').val()) || 0;
            var account = balances[accountId] || { name: 'Seleccione una cuenta', balance: 0 };
            var sign = type === 'expense' ? -1 : 1;
            var effect = amount * sign;
            var finalBalance = Number(account.balance || 0) + effect;

            $('#' + prefix + 'preview_account').text(account.name);
            $('#' + prefix + 'preview_current').text(formatMoney(account.balance));
            $('#' + prefix + 'preview_effect')
                .text((effect >= 0 ? '+ ' : '- ') + formatMoney(Math.abs(effect)))
                .toggleClass('text-success', effect >= 0)
                .toggleClass('text-danger', effect < 0);
            $('#' + prefix + 'preview_final')
                .text(formatMoney(finalBalance))
                .toggleClass('text-success', finalBalance >= 0)
                .toggleClass('text-danger', finalBalance < 0);
        }

        $(document).on('change keyup', '#payment_method_id, #type, #amount', function() {
            updatePreview('');
        });

        $(document).on('change keyup', '#edit_payment_method_id, #edit_type, #edit_amount', function() {
            updatePreview('edit_');
        });

        $('#createModal').on('shown.bs.modal', function() {
            updatePreview('');
        });

        $('.btn-edit').on('click', function() {
            var id = $(this).data('id');
            var url = '{{ route("account-movements.index") }}/' + id;

            $.get(url + '/edit', function(data) {
                $('#editForm').attr('action', url);
                $('#edit_payment_method_id').val(data.payment_method_id);
                $('#edit_type').val(data.type);
                $('#edit_amount').val(data.amount);
                $('#edit_description').val(data.description);
                $('#edit_date').val(data.date.substring(0, 10));
                updatePreview('edit_');
                $('#editModal').modal('show');
            });
        });
    });
</script>
@endsection
