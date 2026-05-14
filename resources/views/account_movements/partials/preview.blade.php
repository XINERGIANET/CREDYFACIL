<div class="border rounded bg-light p-3 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="text-muted small">Cuenta seleccionada</div>
            <div class="fw-bold" id="{{ $prefix }}preview_account">Seleccione una cuenta</div>
        </div>
        <span class="badge bg-primary">Vista previa</span>
    </div>
    <div class="row g-2">
        <div class="col-md-4">
            <div class="border rounded bg-white p-2 h-100">
                <div class="text-muted small">Monto actual</div>
                <div class="fs-4 fw-bold" id="{{ $prefix }}preview_current">S/0.00</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-2 h-100">
                <div class="text-muted small">Movimiento</div>
                <div class="fs-4 fw-bold text-success" id="{{ $prefix }}preview_effect">+ S/0.00</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-2 h-100">
                <div class="text-muted small">Saldo final</div>
                <div class="fs-4 fw-bold" id="{{ $prefix }}preview_final">S/0.00</div>
            </div>
        </div>
    </div>
</div>
