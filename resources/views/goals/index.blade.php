@extends('template.app')

@section('title', 'Metas Mensuales')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Metas Mensuales</li>
  </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configuración de Metas por Asesor</h3>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('goals.index') }}">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Mes</label>
                        <select class="form-select" name="month">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Año</label>
                        <input type="number" class="form-control" name="year" value="{{ $year }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Consultar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if(session('message'))
        <div class="alert alert-success m-3">
            {{ session('message') }}
        </div>
    @endif

    <form method="POST" action="{{ route('goals.store') }}">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Asesor</th>
                        <th style="width: 200px;">Meta Clientes</th>
                        <th style="width: 200px;">Meta Nuevos</th>
                        <th style="width: 200px;">Meta Desembolso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sellers as $seller)
                        @php
                            $goal = $goals->get($seller->id);
                        @endphp
                        <tr>
                            <td>{{ $seller->name }}</td>
                            <td>
                                <input type="number" name="goals[{{ $seller->id }}][clients]" 
                                    class="form-control" value="{{ $goal ? $goal->clients : 0 }}" min="0">
                            </td>
                            <td>
                                <input type="number" name="goals[{{ $seller->id }}][new_clients]" 
                                    class="form-control" value="{{ $goal ? $goal->new_clients : 0 }}" min="0">
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" name="goals[{{ $seller->id }}][disbursement]" 
                                        class="form-control" value="{{ $goal ? $goal->disbursement : 0 }}" min="0" step="0.01">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-success">Guardar Metas de {{ $months[$month] }} {{ $year }}</button>
        </div>
    </form>
</div>
@endsection
