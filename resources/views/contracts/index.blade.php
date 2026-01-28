@extends('template.app')

@section('title', 'Contratos')

@section('content')
    <nav class="mb-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Contratos</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header">
            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('operations'))
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="ti ti-plus icon"></i> Crear nuevo
                </button>
                <div class="d-flex align-items-center ms-auto gap-4">
                    <p class="mb-0 text-end">
                        El monto de seguro actualmente es: <b>S/{{ $insurance_amount }}</b>
                    </p>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#insuranceModal">
                        <i class="ti ti-pencil icon"></i> Cambiar monto
                    </button>
                </div>
            @endif
        </div>
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('credit'))
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
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}"
                                            @if ($seller->id == request()->seller_id) selected @endif>{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Inicio del préstamo</label>
                                <input type="date" class="form-control" name="start_date"
                                    value="{{ request()->start_date }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Fin del préstamo</label>
                                <input type="date" class="form-control" name="end_date"
                                    value="{{ request()->end_date }}">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-danger">Limpiar</a>
                </form>
            </div>
        @endif
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
                        <th></th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($contracts->count() > 0)
                        @foreach ($contracts as $contract)
                            <tr>
                                <td title="{!! $contract->people() !!}" data-bs-toggle="tooltip" data-bs-html="true">
                                    {{ $contract->client_type == 'Personal' ? $contract->name : $contract->group_name }}
                                </td>
                                <td>{{ optional($contract)->seller->name }}</td>
                                <td>{{ $contract->requested_amount }}</td>
                                <td>{{ $contract->quotas_number }}</td>
                                <td>{{ $contract->interest }}</td>
                                <td>{{ $contract->payable_amount }}</td>
                                <td>{{ $contract->date->format('d/m/Y') }}</td>
                                <td>
                                    @if ($contract->paid)
                                        <span class="badge bg-success"></span>
                                    @else
                                        <span class="badge bg-danger"></span>
                                    @endif
                                </td>
                                <td>

                                    <div class="d-flex gap-2">
                                        <a href="{{ route('contracts.pdfPersonal', $contract) }}" class="btn btn-primary btn-icon"
                                            title="Contrato">
                                            <i class="ti ti-file-text icon"></i>
                                        </a>
                                        @if (auth()->user()->hasRole('admin'))
                                            <button class="btn btn-icon btn-danger btn-delete"
                                                data-id="{{ $contract->id }}">
                                                <i class="ti ti-x icon"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
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
        @if ($contracts->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $contracts->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="storeForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear nuevo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" name="insurance_cost" id="insurance_cost"
                                value="{{ $insurance_amount }}">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de cliente</label>
                                    <select class="form-select" name="client_type" id="client_type">
                                        <option value="Personal">Personal</option>
                                        <option value="Grupo">Grupo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divGroupName" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label required">Nombre de grupo</label>
                                    <input type="text" class="form-control" name="group_name" id="group_name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divQuantity" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label required">Cantidad</label>
                                    <div class="w-100 btn-group">
                                        <button type="button" class="btn btn-primary w-50"
                                            id="btn-add">Agregar</button>
                                        <button type="button" class="btn btn-danger w-50"
                                            id="btn-remove">Quitar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDocument">
                                <div class="mb-3">
                                    <label class="form-label required">DNI</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control ts-document" name="document"
                                            id="document" autocomplete="off">
                                        <button type="button" class="btn btn-primary btn-icon" id="btn-search">
                                            <i class="ti ti-search icon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divName">
                                <div class="mb-3">
                                    <label class="form-label required">Nombre</label>
                                    <input type="text" class="form-control" name="name" id="name"
                                        autocomplete="off" readonly>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divPhone">
                                <div class="mb-3">
                                    <label class="form-label required">Teléfono</label>
                                    <input type="text" class="form-control" name="phone" id="phone"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divAddress">
                                <div class="mb-3">
                                    <label class="form-label required">Dirección</label>
                                    <input type="text" class="form-control" name="address" id="address"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDepartment">
                                <div class="mb-3">
                                    <label class="form-label required">Departamento</label>
                                    <select class="form-select" name="department_id" id="department_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divProvince">
                                <div class="mb-3">
                                    <label class="form-label required">Provincia</label>
                                    <select class="form-select" name="province_id" id="province_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divDistrict">
                                <div class="mb-3">
                                    <label class="form-label required">Distrito</label>
                                    <select class="form-select" name="district_id" id="district_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divReference">
                                <div class="mb-3">
                                    <label class="form-label">Referencia</label>
                                    <input type="text" class="form-control" name="reference" id="reference"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHomeType">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de vivienda</label>
                                    <select class="form-select" name="home_type" id="home_type">
                                        <option value="">Seleccionar</option>
                                        <option value="Propia">Propia</option>
                                        <option value="Alquilada">Alquilada</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessLine">
                                <div class="mb-3">
                                    <label class="form-label">Rubro de negocio</label>
                                    <input type="text" class="form-control" name="business_line" id="business_line"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessAddress">
                                <div class="mb-3">
                                    <label class="form-label">Dirección de negocio</label>
                                    <input type="text" class="form-control" name="business_address"
                                        id="business_address" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divBusinessStartDate">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de inicio de negocio</label>
                                    <input type="date" class="form-control" name="business_start_date"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divCivilStatus">
                                <div class="mb-3">
                                    <label class="form-label required">Estado civil</label>
                                    <select class="form-select" name="civil_status" id="civil_status">
                                        <option value="">Seleccionar</option>
                                        <option value="Soltero">Soltero</option>
                                        <option value="Casado">Casado</option>
                                        <option value="Divorciado">Divorciado</option>
                                        <option value="Viudo">Viudo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHusbandName" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de esposo (a)</label>
                                    <input type="text" class="form-control" name="husband_name" id="husband_name"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-4" id="divHusbandDocument" style="display: none">
                                <div class="mb-3">
                                    <label class="form-label">DNI de esposo (a)</label>
                                    <input type="text" class="form-control" name="husband_document"
                                        id="husband_document" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div id="divGroup" style="display:none">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control ts-document" name="documents[]"
                                                autocomplete="off">
                                            <button type="button" class="btn btn-primary btn-icon"
                                                id="btn-group-search">
                                                <i class="ti ti-search icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" class="form-control" name="names[]" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Dirección</label>
                                        <input type="text" class="form-control" name="addresses[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control ts-document" name="documents[]"
                                                autocomplete="off">
                                            <button type="button" class="btn btn-primary btn-icon"
                                                id="btn-group-search">
                                                <i class="ti ti-search icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Nombre</label>
                                        <input type="text" class="form-control" name="names[]" autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Dirección</label>
                                        <input type="text" class="form-control" name="addresses[]"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Asesor comercial</label>
                                    <select class="form-select" name="seller_id">
                                        <option value="">Seleccionar</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Monto solicitado</label>
                                    <input type="text" class="form-control" name="requested_amount" id="requested_amount"
                                        autocomplete="off">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="mb-3">
                                    <label class="form-label required">Número de meses</label>
                                    <input type="number" class="form-control" name="months_number" id="months_number" autocomplete="off" step="0.1" min="0">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="mb-3">
                                    <label class="form-label required">Tipo de Cuota</label>
                                    <select class="form-select" name="type_quota">
                                        <option value="1">Semanal</option>
                                        <option value="2">Catorcenal</option>
                                        <option value="4">Mensual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Fecha de prestamo</label>
                                    @if (auth()->user()->hasRole('operations'))
                                        <input type="date" class="form-control" name="date_disabled"
                                            value="{{ now()->format('Y-m-d') }}" autocomplete="off" disabled>
                                        {{-- input hidden para asegurar que la fecha se envíe en el formulario aun cuando el campo esté disabled --}}
                                        <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                    @else
                                        <input type="date" class="form-control" name="date"
                                            value="{{ now()->format('Y-m-d') }}" autocomplete="off">
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label required">Tasa de interés (%)</label>
                                    <input type="text" class="form-control" name="interest" id="interest"
                                        autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btn-save"><i
                                class="ti ti-device-floppy icon"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name="name" id="editName"
                                        autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="editId">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i>
                            Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="quotasModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cuotas pendientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Contrato</th>
                                <th>Número</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-quotas"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="confirmDerivedModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aviso importante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>El cliente tiene una deuda pendiente de: S/<b id="past-debt"></b></p>
                    <p>El nuevo contrato cuenta con una deuda total de : S/<b id="contract-debt"></b></p>
                    <p>El monto entregado deberá ser de : S/<b id="difference"></b></p>
                    <p class="text-danger" id="warning"></p>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                        Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="insuranceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="insuranceForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Editar monto de seguro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Monto</label>
                                    <input type="text" class="form-control" name="insurance_amount"
                                        id="insurance_amount" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i>
                            Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>

        var totalDebt = 0;

        $(document).ready(function() {

            var queryString = window.location.search;
            var parametros = new URLSearchParams(queryString);
            

            if (parametros.get('modal') == 'create') {
                $('#createModal').modal('show');
            }

            // Limpiar selects cuando se abre el modal
            $('#createModal').on('shown.bs.modal', function() {
                $('#province_id').html('<option value="">Seleccionar</option>');
                $('#district_id').html('<option value="">Seleccionar</option>');
            });



            // Helper: inicializa TomSelect en un input concreto (evita duplicados)
            function initTomSelect($input) {
                if (!$input || $input.data('ts-initialized')) return;
                var el = $input[0];
                var isMain = $input.is('#document');

                new TomSelect(el, {
                    create: true,
                    maxItems: 1,
                    valueField: 'document',
                    labelField: ['name', 'document'],
                    searchField: ['name', 'document'],
                    copyClassesToDropdown: false,
                    dropdownClass: 'dropdown-menu ts-dropdown',
                    optionClass: 'dropdown-item',
                    hideSelected: true,
                    load: function(query, callback) {
                        $.ajax({
                            url: '{{ route('clients.api') }}?q=' + encodeURIComponent(query),
                            method: 'GET',
                            success: function(data) {
                                callback(data.items);
                            },
                            error: function(err) {
                                console.log(err);
                            }
                        })
                    },
                    render: {
                        item: function(data, escape) {
                            return `<div data-name="${escape(data.name)}" data-phone="${escape(data.phone)}" data-address="${escape(data.address)}" data-reference="${escape(data.reference)}" data-business-line="${escape(data.business_line)}" data-business-address="${escape(data.business_address)}" data-home-type="${escape(data.home_type)}" data-civil-status="${escape(data.civil_status)}">${escape(data.document)}</div>`;
                        },
                        option: function(data, escape) {
                            return `<div>${escape(data.document)} - ${ data.name ? escape(data.name) : ''}</div>`;
                        },
                        no_results: function(data, escape) {
                            return '<div class="no-results">No se encontraron resultados</div>'
                        },
                        option_create: function(data, escape) {
                            return '<div class="create">Agregar <strong>' + escape(data.input) +
                                '</strong>&hellip;</div>';
                        }
                    },
                    onItemAdd: function(value, item) {
                        var dataset = item.dataset || {};

                        function normalizeDataValue(v) {
                            if (v === undefined || v === null) return '';
                            if (typeof v !== 'string') return v;
                            v = v.trim();
                            if (v === '') return '';
                            var low = v.toLowerCase();
                            if (low === 'null' || low === 'undefined') return '';
                            return v;
                        }
                        if (isMain) {
                            // llenar campos globales
                            if (dataset.name == 'undefined') {
                                $('#name').val('');
                            } else {
                                console.log('onItemAdd dataset:', dataset);
                                $('#name').val(dataset.name || '');
                                $('#phone').val(dataset.phone == 'null' ? '' : (dataset.phone || ''));
                                $('#address').val(dataset.address == 'null' ? '' : (dataset.address ||
                                    ''));
                                var reference = dataset.reference || dataset.ref || dataset
                                    .referencia || '';
                                $('#reference').val(reference == 'null' ? '' : reference);
                                var businessLine = dataset.businessLine || dataset.business_line ||
                                    dataset.rubro || '';
                                $('#business_line').val(businessLine == 'null' ? '' : businessLine);
                                var businessAddress = dataset.businessAddress || dataset
                                    .business_address || '';
                                $('#business_address').val(businessAddress == 'null' ? '' :
                                    businessAddress);
                                $('#home_type').val(dataset.homeType == 'null' ? '' : (dataset
                                    .homeType || dataset.home_type || ''));
                                $('#civil_status').val(dataset.civilStatus == 'null' ? '' : (dataset
                                    .civilStatus || dataset.civil_status || ''));

                                if (dataset.civil_status == 'Casado' || dataset.civilStatus ==
                                    'Casado') {
                                    $('#divHusbandName').show();
                                    $('#divHusbandDocument').show();
                                } else {
                                    $('#husband_name').val('');
                                    $('#husband_document').val('');
                                    $('#divHusbandName').hide();
                                    $('#divHusbandDocument').hide();
                                }
                            }


                        } else {
                            // si es un DNI de grupo, rellenar solo los campos de la fila correspondiente
                            var $row = $($input).closest('.row');
                            $row.find('input[name="names[]"]').val(normalizeDataValue(dataset.name));
                            $row.find('input[name="addresses[]"]').val(normalizeDataValue(dataset
                                .address));
                        }

                        // comprobar cuotas pendientes para el documento seleccionado (solo para principal)
                        $.ajax({
                            url: '{{ route('clients.check') }}',
                            method: 'GET',
                            data: {
                                document: value
                            },
                            success: function(data) {
                                if (data.status) {
                                    ToastMessage.fire({
                                        text: 'El cliente no tiene cuotas pendientes'
                                    });
                                    totalDebt = 0;
                                } else {
                                    var html = '';
                                    data.quotas.forEach(function(quota) {
                                        totalDebt += parseFloat(String(quota.debt).replace(',', '.'));
                                        html += `
										<tr>
											<td>${quota.contract_id}</td>
											<td>${quota.number}</td>
											<td>${quota.amount}</td>
											<td>${quota.debt}</td>
											<td>${quota.date}</td>
										</tr>
									`;
                                    });
                                    $('#tbl-quotas').html(html);
                                    $('#quotasModal').modal('show');
                                }
                            }
                        });

                    }
                });

                $input.data('ts-initialized', true);
            }

            // inicializar TomSelect en los inputs existentes
            $('.ts-document').each(function() {
                initTomSelect($(this));
            });

            // Validación del campo meses según tipo de cuota
            var $months = $('input[name="months_number"]');
            var $typeQuota = $('select[name="type_quota"]');

            // Cambiar atributo step según tipo de cuota
            $typeQuota.on('change', function() {
                if ($(this).val() === '4') {
                    $months.attr('step', '1');
                } else {
                    $months.attr('step', '0.1');
                }
            });

            // Inicializar step
            if ($typeQuota.val() === '4') {
                $months.attr('step', '1');
            } else {
                $months.attr('step', '0.1');
            }

        });

        $('#btn-search').click(function() {

            var dni = $('#document').val().trim();

            if (dni.length != 8) {
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: '{{ route('api.reniec') }}',
                method: 'GET',
                data: {
                    dni
                },
                success: function(data) {

                    Swal.close();

                    if (data.status) {
                        $('#name').attr('readonly', true);
                        $('#name').val(data.name);
                    } else {
                        $('#name').val('');
                        $('#name').attr('readonly', false);
                        $('#name').focus();
                    }
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            })
        });

        $(document).on('click', '#btn-group-search', function() {
            var $row = $(this).closest('.row');
            var $dniInput = $row.find('input[name="documents[]"]');
            var $nameInput = $row.find('input[name="names[]"]');
            var dni = $dniInput.val().trim();

            if (dni.length != 8) {
                $nameInput.val('');
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: '{{ route('api.reniec') }}',
                method: 'GET',
                data: {
                    dni
                },
                success: function(data) {
                    Swal.close();
                    if (data.status) {
                        $nameInput.attr('readonly', true);
                        $nameInput.val(data.name);
                    } else {
                        $nameInput.val('');
                        $nameInput.attr('readonly', false);
                        $nameInput.focus();
                    }
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });
        });

        // Cascada de Departamentos -> Provincias -> Distritos
        $(document).on('change', '#department_id', function() {
            var department_id = $(this).val();
            var $provinceSelect = $('#province_id');
            var $districtSelect = $('#district_id');
            
            // Limpiar provincias y distritos
            $provinceSelect.html('<option value="">Seleccionar</option>');
            $districtSelect.html('<option value="">Seleccionar</option>');
            
            if (!department_id) {
                return;
            }

            $.ajax({
                url: '{{ route('api.provinces') }}',
                method: 'GET',
                data: {
                    department_id: department_id
                },
                success: function(data) {
                    $.each(data, function(index, province) {
                        $provinceSelect.append('<option value="' + province.id + '">' + province.name + '</option>');
                    });
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error al cargar las provincias'
                    });
                }
            });
        });

        $(document).on('change', '#province_id', function() {
            var province_id = $(this).val();
            var $districtSelect = $('#district_id');
            
            // Limpiar distritos
            $districtSelect.html('<option value="">Seleccionar</option>');
            
            if (!province_id) {
                return;
            }

            $.ajax({
                url: '{{ route('api.districts') }}',
                method: 'GET',
                data: {
                    province_id: province_id
                },
                success: function(data) {
                    $.each(data, function(index, district) {
                        $districtSelect.append('<option value="' + district.id + '">' + district.name + '</option>');
                    });
                },
                error: function() {
                    ToastError.fire({
                        text: 'Ocurrió un error al cargar los distritos'
                    });
                }
            });
        });

        $('#storeForm').submit(function(e) {
            $('#btn-save').prop('disabled', true);
            $('#btn-confirm').prop('disabled', false);
            $('#warning').text('');
            e.preventDefault();

            if(totalDebt > 0) {
                
                var base_insurance = parseFloat(String($('#insurance_cost').val()).replace(',', '.')) || 0;
                var months_number = parseFloat(String($('#months_number').val()).replace(',', '.')) || 0;
                var insurance_cost = Math.round(base_insurance * months_number * 100) / 100;
                var interest_percentage = parseFloat(String($('#interest').val()).replace(',', '.')) || 0;
                var requested_amount = parseFloat(String($('#requested_amount').val()).replace(',', '.')) || 0;

                var interest = Math.round(requested_amount * (interest_percentage / 100) * 100) / 100;
                var contract_debt = Math.round((requested_amount + interest + insurance_cost) * 100) / 100;
                var difference = Math.round((contract_debt - totalDebt) * 100) / 100;

                $('#past-debt').text(totalDebt.toFixed(2));
                $('#contract-debt').text(contract_debt.toFixed(2));
                $('#difference').text(difference.toFixed(2));

                if(difference < 0){
                    $('#warning').text('El contrato debe tener una deuda mayor a la acumulada por el cliente');
                    $('#btn-confirm').prop('disabled', true);
                }

                $('#confirmDerivedModal').modal('show');

                $('#btn-save').prop('disabled', false);
                return;
            }

            $.ajax({
                url: '{{ route('contracts.store') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(data) {
                    if (data.status) {
                        $('#createModal').modal('hide');
                        $('#storeForm')[0].reset();
                        // Limpiar selects de provincia y distrito
                        $('#province_id').html('<option value="">Seleccionar</option>');
                        $('#district_id').html('<option value="">Seleccionar</option>');

                        ToastMessage.fire({
                                text: 'Registro guardado'
                            })
                            .then(() => location.reload());

                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                        $('#btn-save').prop('disabled', false);
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                    $('#btn-save').prop('disabled', false);
                }
            });

        });

        $(document).on('click', '#btn-confirm', function() {

            totalDebt = 0;
            
            $('#storeForm').submit();
            $('#confirmDerivedModal').modal('hide');
        });


        $(document).on('click', '.btn-edit', function() {

            var id = $(this).data('id');

            $.ajax({
                url: '{{ route('contracts.index') }}' + '/' + id + '/edit/',
                method: 'GET',
                success: function(data) {
                    $('#editName').val(data.name);
                    $('#editId').val(data.id);
                    $('#editModal').modal('show');
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });

        $('#editForm').submit(function(e) {
            e.preventDefault();

            var id = $('#editId').val();

            $.ajax({
                url: '{{ route('contracts.index') }}' + '/' + id + '',
                method: 'PATCH',
                data: $(this).serialize(),
                success: function(data) {
                    if (data.status) {
                        $('#editModal').modal('hide');
                        $('#editForm')[0].reset();

                        ToastMessage.fire({
                                text: 'Registro actualizado'
                            })
                            .then(() => location.reload());

                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });

        $(document).on('click', '.btn-delete', function() {

            var id = $(this).data('id');

            ToastConfirm.fire({
                text: '¿Estás seguro que deseas borrar el registro?',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('contracts.index') }}' + '/' + id,
                        method: 'DELETE',
                        success: function(data) {
                            ToastMessage.fire({
                                    text: 'Registro eliminado'
                                })
                                .then(() => location.reload());
                        },
                        error: function(err) {
                            ToastError.fire({
                                text: 'Ocurrió un error'
                            });
                        }
                    });
                }
            });

        });

        $('#civil_status').change(function() {
            var civil_status = $(this).val();

            if (civil_status == 'Casado') {
                $('#divHusbandName').show();
                $('#divHusbandDocument').show();
            } else {
                $('#husband_name').val('');
                $('#husband_document').val('');
                $('#divHusbandName').hide();
                $('#divHusbandDocument').hide();
            }
        });

        $('#client_type').change(function() {
            var client_type = $(this).val();

            if (client_type == 'Personal') {

                $('#storeForm')[0].reset();
                // Limpiar selects de provincia y distrito
                $('#province_id').html('<option value="">Seleccionar</option>');
                $('#district_id').html('<option value="">Seleccionar</option>');

                $('#divDocument').show();
                $('#divName').show();
                $('#divPhone').show();
                $('#divAddress').show();
                $('#divReference').show();
                $('#divHomeType').show();
                $('#divBusinessLine').show();
                $('#divBusinessAddress').show();
                $('#divBusinessStartDate').show();
                $('#divCivilStatus').show();

                $('#divGroupName').hide();
                $('#divQuantity').hide();
                $('#divGroup').hide();

            } else if (client_type == 'Grupo') {

                $('#divDocument').hide();
                $('#divName').hide();
                $('#divPhone').hide();
                $('#divAddress').hide();
                $('#divDepartment').hide();
                $('#divProvince').hide();
                $('#divDistrict').hide();
                $('#divReference').hide();
                $('#divHomeType').hide();
                $('#divBusinessLine').hide();
                $('#divBusinessAddress').hide();
                $('#divBusinessStartDate').hide();
                $('#divCivilStatus').hide();

                $('#divGroupName').show();
                $('#divQuantity').show();
                $('#divGroup').show();

            }
        });

        $('#btn-add').click(function() {
            var html = `
			<div class="row">
				<div class="col-lg-4">
					<div class="mb-3">
						<label class="form-label required">DNI</label>
						<div class="input-group">
							<input type="text" class="form-control" name="documents[]" autocomplete="off">
							<button type="button" class="btn btn-primary btn-icon" id="btn-group-search">
								<i class="ti ti-search icon"></i>
							</button>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<div class="mb-3">
						<label class="form-label required">Nombre</label>
						<input type="text" class="form-control" name="names[]" autocomplete="off">
					</div>
				</div>
				<div class="col-lg-4">
					<div class="mb-3">
						<label class="form-label required">Dirección</label>
						<input type="text" class="form-control" name="addresses[]" autocomplete="off">
					</div>
				</div>
			</div>
		`;
            $('#divGroup').append(html);
        });

        $('#btn-remove').click(function() {
            if ($('#divGroup').children().length > 2) {
                $('#divGroup').children().last().remove();
            } else {
                console.log('Deben haber 2 personas mínimo para grupo');
            }
        });

        $('#insurance_amount').on('keypress', function(e) {
            const ch = String.fromCharCode(e.which);
            if (!/[0-9.]/.test(ch)) e.preventDefault();
            // evitar segundo punto
            if (ch === '.' && $(this).val().includes('.')) e.preventDefault();
        });

        $('#insuranceForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{ route('config.insurance') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(data) {
                    if (data.status) {
                        $('#insuranceModal').modal('hide');
                        $('#insuranceForm')[0].reset();

                        ToastMessage.fire({
                                text: 'Registro actualizado'
                            })
                            .then(() => location.reload());

                    } else {
                        ToastError.fire({
                            text: data.error ? data.error : 'Ocurrió un error'
                        });
                    }
                },
                error: function(err) {
                    ToastError.fire({
                        text: 'Ocurrió un error'
                    });
                }
            });

        });
    </script>
@endsection
