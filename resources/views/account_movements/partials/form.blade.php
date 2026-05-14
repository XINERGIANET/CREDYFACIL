<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label required">Cuenta</label>
            <select class="form-select" name="payment_method_id" id="{{ $prefix }}payment_method_id" required>
                <option value="">Seleccionar</option>
                @foreach($payment_methods as $payment_method)
                <option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label required">Tipo</label>
            <select class="form-select" name="type" id="{{ $prefix }}type" required>
                <option value="">Seleccionar</option>
                <option value="income">Ingreso</option>
                <option value="expense">Egreso</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label required">Monto</label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="{{ $prefix }}amount" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label required">Fecha</label>
            <input type="date" class="form-control" name="date" id="{{ $prefix }}date" value="{{ now()->format('Y-m-d') }}" required>
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label required">Descripción</label>
            <input type="text" class="form-control" name="description" id="{{ $prefix }}description" placeholder="Ej. Ajuste de caja, depósito, comisión, retiro" required>
        </div>
    </div>
</div>
