<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\EndingContractsExport;
use App\Models\Config;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\User;
use App\Models\Pdf as PdfModel;
use App\Models\Department;
use Dompdf\Dompdf;
use Dompdf\Options;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $contracts = Contract::active()->when($user->hasRole('seller'), function ($query) use ($user) {
            return $query->where('seller_id', $user->id);
        })->when($request->name, function ($query, $name) {
            return $query->where(function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
            });
        })->when($request->seller_id, function ($query, $seller_id) {
            return $query->where('seller_id', $seller_id);
        })->when($request->start_date, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->latest('id')->paginate(20);
        $sellers = User::seller()->where('state', 0)->active()->orderBy('name', 'asc')->get();
        $insurance_amount = Config::first()->insurance;
        $departments = Department::orderBy('name', 'asc')->get();
        return view('contracts.index', compact('contracts', 'sellers', 'insurance_amount', 'departments'));
    }

    public function ending(Request $request)
    {

        $user = auth()->user();

        $start_date = $request->start_date ? $request->start_date : now();
        $end_date = $request->end_date ? $request->end_date : now();

        $contracts = Contract::active()->when($user->hasRole('seller'), function ($query) use ($user) {
            return $query->where('seller_id', $user->id);
        })->when($request->name, function ($query, $name) {
            return $query->where(function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
            });
        })->when($request->seller_id, function ($query, $seller_id) {
            return $query->where('seller_id', $seller_id);
        })->whereDate('last_payment_date', '>=', $start_date)->whereDate('last_payment_date', '<=', $end_date)
            ->oldest('last_payment_date');

        $requested_amount = $contracts->sum('requested_amount');

        $contracts = $contracts->paginate(20);

        $sellers = User::seller()->active()->orderBy('name', 'asc')->get();


        return view('contracts.ending', compact('contracts', 'sellers', 'requested_amount'));
    }

    public function endingExcel(Request $request)
    {
        $name = "ContratosPorFinalizar_" . now()->format('d_m_Y') . ".xlsx";
        return Excel::download(new EndingContractsExport, $name);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'client_type' => 'required',
            'documents.*' => 'nullable|size:8|distinct',
            'names.*' => 'nullable|distinct',
            'addresses.*' => 'nullable',
            'address.*' => 'required',
            'document' => 'nullable|size:8',
            'name' => 'nullable',
            'group_name' => 'nullable',
            'phone' => 'nullable',
            'address' => 'nullable',
            'home_type' => 'nullable',
            'business_start_date' => 'nullable|date',
            'civil_status' => 'nullable',
            'husband_name' => 'nullable',
            'husband_document' => 'nullable|size:8',
            'seller_id' => 'required',
            'requested_amount' => 'required|numeric',
            'months_number' => 'required|numeric|min:1',
            'date' => 'required|date',
            'interest' => 'nullable|numeric',
            'type_quota' => 'required|in:1,2,4',
            'insurance_cost' => 'nullable|numeric',
        ]);

        $validator->sometimes(['document', 'name', 'phone', 'address', 'home_type', 'civil_status'], 'required', function ($request) {
            return $request->client_type == 'Personal';
        });

        $validator->sometimes(['group_name', 'documents.*', 'names.*', 'addresses.*'], 'required', function ($request) {
            return $request->client_type == 'Grupo';
        });

        $validator->sometimes(['husband_name', 'husband_document'], 'required', function ($request) {
            return $request->civil_status == 'Casado';
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $interest_percentage = floatval($request->interest);

        $insurance_cost = floatval($request->insurance_cost) * $request->months_number;
        $insurance_cost = ceil($insurance_cost * 10) / 10;

        $type_quota = (int) $request->type_quota;

        // Mapeo: según lo solicitado
        // 1 => semanal (4 cuotas por mes)
        // 2 => cada 2 semanas (2 cuotas por mes)
        // 4 => mensual (1 cuota por mes)
        $perMonthMap = [
            1 => 4,
            2 => 2,
            4 => 1,
        ];

        $quotasPerMonth = isset($perMonthMap[$type_quota]) ? $perMonthMap[$type_quota] : 4;
        $quotas = $request->months_number * $quotasPerMonth;
        // Redondear hacia arriba el número de cuotas para el loop
        $quotas_rounded = ceil($quotas);
        $percentage = $interest_percentage;

        $interest = $request->requested_amount * ($interest_percentage / 100);
        $payable_amount = $request->requested_amount + $interest + $insurance_cost;
        $quota = $payable_amount / $quotas;
        $quota = ceil($quota * 10) / 10;

        $date = Carbon::parse($request->date);

        $quota_dates = [];

        for ($i = 1; $i <= $quotas_rounded; $i++) {
            if ($type_quota === 1) {
                // semanal
                $quota_date = $date->addWeek();
            } elseif ($type_quota === 2) {
                // cada 2 semanas
                $quota_date = $date->addWeeks(2);
            } elseif ($type_quota === 4) {
                // mensual
                $quota_date = $date->addMonth();
            } else {
                // fallback a semanal
                $quota_date = $date->addWeek();
            }

            $quota_dates[] = [
                'number' => $i,
                'date' => $quota_date->format('Y-m-d')
            ];
        }

        DB::beginTransaction();

        try {

            $config = Config::first();
            $nextPagare = ($config->number_pagare ?? 0) + 1;

            $contract = new Contract;
            $contract->client_type = $request->client_type;

            $contract->number_pagare = $nextPagare;

            if ($request->client_type == 'Personal') {

                $document = $request->document;
                $contract->document = $request->document;
                $contract->name = $request->name;
                $contract->phone = $request->phone;
                $contract->phone = $request->phone;
                $contract->address = $request->address;
                $contract->district_id = $request->district_id;
                $contract->reference = $request->reference;
                $contract->home_type = $request->home_type;
                $contract->business_line = $request->business_line;
                $contract->business_address = $request->business_address;
                $contract->business_start_date = $request->business_start_date;
                $contract->civil_status = $request->civil_status;
                $contract->husband_name = $request->husband_name;
                $contract->husband_document = $request->husband_document;
                $contract->civil_status = $request->civil_status;
                $contract->civil_status = $request->civil_status;

                //derivar cuotas anteriores - TODO: preguntar como sería para préstamos grupales
                Quota::where('paid', 0)
                    ->whereHas('contract', function ($q) use ($document) {
                        $q->where('document', $document);
                    })
                    ->update(['paid' => 2]);
            } elseif ($request->client_type == 'Grupo') {

                $people = [];

                for ($i = 0; $i < count($request->documents); $i++) {
                    $people[] = [
                        'document' => $request->documents[$i],
                        'name' => $request->names[$i],
                        'address' => $request->addresses[$i],
                    ];
                }


                $group_number = DB::table('settings')->selectRaw('group_number + 1 AS number')->pluck('number')->first();

                $contract->group_name = "Grupo {$group_number} - " . $request->group_name;
                $contract->people = json_encode($people);

                DB::table('settings')->update(['group_number' => $group_number]);
            }

            $contract->seller_id = $request->seller_id;
            $contract->requested_amount = $request->requested_amount;
            $contract->months_number = $request->months_number;
            $contract->quotas_number = $quotas_rounded;
            $contract->percentage = $percentage;
            $contract->interest = $interest;
            $contract->payable_amount = $payable_amount;
            $contract->quota_amount = $quota;
            $contract->insurance_amount = $insurance_cost;
            $contract->date = $request->date;
            $contract->first_payment_date = reset($quota_dates)['date'];
            $contract->last_payment_date = end($quota_dates)['date'];

            $contract->save();

            $config->update(['number_pagare' => $nextPagare]);

            foreach ($quota_dates as $quota_date) {

                Quota::create([
                    'contract_id' => $contract->id,
                    'number' => $quota_date['number'],
                    'amount' => $quota,
                    'debt' => $quota,
                    'date' => $quota_date['date'],
                ]);
            }


            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Contract $contract)
    {
        return response()->json($contract);
    }

    public function update(Request $request, Contract $contract) {}

    public function destroy(Request $request, Contract $contract)
    {
        $contract->update([
            'deleted' => 1
        ]);

        return response()->json([
            'status' => true
        ]);
    }

    public function api(Request $request)
    {
        $user = auth()->user();
        $contracts = Contract::active()->when($user->hasRole('seller'), function ($query) use ($user) {
            return $query->where('seller_id', $user->id);
        })->where(function ($query) use ($request) {
            return $query->where('name', 'like', '%' . $request->q . '%')
                ->orWhere('group_name', 'like', '%' . $request->q . '%');
        })->where('paid', 0)->orderBy('name')->orderBy('group_name')->orderBy('date')->get();
        return response()->json(['items' => $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'client_type' => $contract->client_type,
                'name' => $contract->name,
                'group_name' => $contract->group_name,
                'requested_amount' => $contract->requested_amount,
                'date' => $contract->date->format('d/m/Y'),
            ];
        })]);
    }

    /* public function pdf(Request $request, Contract $contract)
    {
        $fpdf = new Pdf('P');

        $fpdf->AddPage();

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        $fpdf->SetFont('Montserrat', 'B', 14);

        $fpdf->Cell(190, 10, utf8_decode('CONTRATO DEL PRÉSTAMO'), 0, 1, 'C');

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 12);

        if ($contract->client_type == 'Personal') {

            $fpdf->Cell(190, 10, utf8_decode('Cliente: ' . $contract->name), 0, 1);

            $fpdf->Cell(190, 10, utf8_decode('DNI: ' . $contract->document), 0, 1);

            $fpdf->Cell(190, 10, utf8_decode('Dirección: ' . $contract->address), 0, 1);
        } elseif ($contract->client_type == 'Grupo') {

            $fpdf->Cell(190, 10, utf8_decode('Cliente: ' . $contract->group_name . ', conformado por:'), 0, 1);

            $people = json_decode($contract->people);

            foreach ($people as $client) {
                $fpdf->MultiCell(190, 5, utf8_decode('- ' . $client->document . ' / ' . $client->name . ' / ' . $client->address), 0, 1);
            }
        }


        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 12);

        $fpdf->Cell(190, 10, utf8_decode('MONTO Y CONDICIONES DEL PRÉSTAMO'), 0, 1);

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('1. Se acuerda prestar al Cliente la cantidad de ' . $contract->requested_amount . ' nuevos soles. con un interés del ' . $contract->percentage . ' %'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('2. El plazo del préstamo será de ' . $contract->months_number . ' mes(es), comenzando el ' . $contract->date->format('d/m/Y')), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('3. El Cliente se compromete a cancelar la totalidad del préstamo sumando el interés acordado, en la cantidad de ' . $contract->quotas_number . ' cuotas semanales de ' . $contract->quota_amount . ' nuevos soles cada una, a partir del ' . $contract->first_payment_date->format('d/m/Y')), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('4. El cronograma de pagos será el siguiente:'), 0, 1);

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 10);

        $fpdf->Cell(35, 6);
        $fpdf->Cell(40, 6, utf8_decode('NÚMERO'), 1, 0, 'C');
        $fpdf->Cell(40, 6, utf8_decode('CUOTA'), 1, 0, 'C');
        $fpdf->Cell(40, 6, utf8_decode('FECHA'), 1, 0, 'C');

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 10);

        foreach ($contract->quotas as $quota) {
            $fpdf->Cell(35, 6);
            $fpdf->Cell(40, 6, utf8_decode($quota->number), 1, 0, 'C');
            $fpdf->Cell(40, 6, utf8_decode($quota->amount), 1, 0, 'C');
            $fpdf->Cell(40, 6, utf8_decode($quota->date->format('d/m/Y')), 1, 0, 'C');

            $fpdf->Ln();
        }

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 12);

        $fpdf->Cell(190, 10, utf8_decode('FORMA DE PAGO'), 0, 1);

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('1. Los pagos deberán realizarse puntualmente en las fechas acordadas. Cada día de retraso, quedará evidenciado en el histórico del cliente y esto afectará un préstamo futuro.'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('2. En señal de conformidad, las partes suscriben este documento en la ciudad de Piura, el día ' . $contract->date->isoFormat('D [de] MMMM [de] YYYY') . '.'), 0, 1);


        $fpdf->Ln();
        $fpdf->Cell(180, 5, utf8_decode('__________________________'), 0, 1, 'C');
        $fpdf->Cell(180, 5, utf8_decode('CREDYFACIL RUC: 20512345678'), 0, 1, 'C');

        $fpdf->Ln();

        $fpdf->Ln();
        $fpdf->Cell(180, 5, utf8_decode('__________________________'), 0, 1, 'C');
        $fpdf->Cell(180, 5, utf8_decode('EL MUTUARIO / CLIENTE'), 0, 1, 'C');

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Ln();



        $fpdf->Ln();

        $fpdf->Ln();

        $fpdf->Cell(180, 5, utf8_decode('El presente contrato se está firmando el día ' . $contract->date->isoFormat('D [de] MMMM [de] YYYY') . '.'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(180, 5, utf8_decode('Piura, Perú.'), 0, 1);

        $fpdf->Output('D', 'contrato_' . $contract->id . '.pdf');
        exit();
    } */
    public function pdfPersonal(Request $request, Contract $contract)
    {
        // Configurar opciones de DomPDF manualmente
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', base_path());

        // Cargar relaciones de ubicación
        $contract->load('district.province.department');

        //Cantidad de soles en letras
        $contract->amount_in_words = $this->convertToWords($contract->requested_amount);

        // Datos de ubicación
        $contract->district_name = $contract->district ? $contract->district->name : '';
        $contract->province = $contract->district && $contract->district->province ? $contract->district->province->name : '';
        $contract->department = $contract->district && $contract->district->department ? $contract->district->department->name : '';

        // Tipo de cuota (Semanal, Catorcenal, Mensual)
        if (!empty($contract->months_number) && !empty($contract->quotas_number)) {
            $months = (float) $contract->months_number;
            $quotasPerMonth = (float) $contract->quotas_number / ($months ?: 1);
            $roundedQuotas = (int) round($quotasPerMonth);

            $typeMap = [
                1 => 'Mensual',
                2 => 'Catorcenal',
                4 => 'Semanal',
            ];

            $contract->quota_type = $typeMap[$roundedQuotas] ?? 'No definido';
        } else {
            $contract->quota_type = 'No definido';
        }

        // Calcular días totales del préstamo
        if ($contract->date && $contract->last_payment_date) {
            $startDate = $contract->date instanceof \Carbon\Carbon ? $contract->date : Carbon::parse($contract->date);
            $endDate = $contract->last_payment_date instanceof \Carbon\Carbon ? $contract->last_payment_date : Carbon::parse($contract->last_payment_date);
            $contract->total_days = $startDate->diffInDays($endDate);
        } else {
            $contract->total_days = 0;
        }
        // Crear instancia de DomPDF
        $dompdf = new Dompdf($options);

        // Renderizar la vista
        $html = view('contracts.pdf.pdf_personal', compact('contract'))->render();

        // Cargar HTML y generar PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retornar el PDF como stream
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="contrato_personal_' . $contract->id . '.pdf"');
    }
    public function pdf(Request $request, Contract $contract)
    {
        $fpdf = new PdfModel('P');

        $fpdf->AddPage();

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        $fpdf->SetFont('Montserrat', 'B', 14);

        // Aumentado el ancho del logo y movido un poco a la derecha según solicitud.
        // Ajuste: x=155, width=40 (antes x=150, width=30).
        $fpdf->Image(asset('assets/images/logo.png'), 155, 20, 40);

        $fpdf->Cell(190, 10, utf8_decode('CONTRATO DEL PRÉSTAMO'), 0, 1, 'C');

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 12);

        // Asegurar que date sea instancia de Carbon
        $contractDate = $contract->date instanceof \Carbon\Carbon ? $contract->date : Carbon::parse($contract->date);

        $quotaFrequencyText = 'cuotas';
        $quotaTypeName = null;

        if (!empty($contract->months_number) && !empty($contract->quotas_number)) {
            $months = (float) $contract->months_number;
            $quotasPerMonth = (float) $contract->quotas_number / ($months ?: 1);
            $roundedQuotas = (int) round($quotasPerMonth);

            $typeMap = [
                1 => ['label' => 'Mensual', 'frequency' => 'cuotas mensuales'],
                2 => ['label' => 'Catorcenal', 'frequency' => 'cuotas catorcenales'],
                4 => ['label' => 'Semanal', 'frequency' => 'cuotas semanales'],
            ];

            if (isset($typeMap[$roundedQuotas])) {
                $quotaTypeName = $typeMap[$roundedQuotas]['label'];
                $quotaFrequencyText = $typeMap[$roundedQuotas]['frequency'];
            }
        }

        if ($contract->client_type == 'Personal') {
            $fpdf->Cell(190, 8, utf8_decode('Cliente: ' . $contract->name), 0, 1);
            $fpdf->Cell(190, 8, utf8_decode('DNI: ' . $contract->document), 0, 1);
            $fpdf->Cell(190, 8, utf8_decode('Dirección: ' . $contract->address), 0, 1);
        } elseif ($contract->client_type == 'Grupo') {
            $fpdf->Cell(190, 8, utf8_decode('Cliente: ' . $contract->group_name . ', conformado por:'), 0, 1);
            $people = json_decode($contract->people);
            foreach ($people as $client) {
                $fpdf->MultiCell(190, 6, utf8_decode('- ' . $client->document . ' / ' . $client->name . ' / ' . $client->address), 0, 1);
            }
        }

        // Espacio extra antes de la sección Monto y condiciones
        $fpdf->Ln(6);

        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->Cell(190, 10, utf8_decode('MONTO Y CONDICIONES DEL PRÉSTAMO'), 0, 1);

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 6, utf8_decode('1. Se acuerda prestar al Cliente la cantidad de ' . $contract->requested_amount . ' nuevos soles, con un interés del ' . $contract->percentage . ' %.'), 0, 1);

        $fpdf->Ln(2);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 6, utf8_decode('2. El plazo del préstamo será de ' . $contract->months_number . ' mes(es), comenzando el ' . $contractDate->format('d/m/Y') . '.'), 0, 1);

        $fpdf->Ln(2);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 6, utf8_decode('3. El Cliente se compromete a cancelar la totalidad del préstamo sumando el interés acordado, en la cantidad de ' . $contract->quotas_number . ' ' . $quotaFrequencyText . ' de ' . number_format($contract->quota_amount, 2) . ' nuevos soles cada una, a partir del ' . (isset($contract->first_payment_date) ? Carbon::parse($contract->first_payment_date)->format('d/m/Y') : '') . '.'), 0, 1);

        if ($quotaTypeName) {
            $fpdf->Ln(2);
            $fpdf->Cell(10, 5);
            $fpdf->MultiCell(180, 6, utf8_decode('4. El tipo de cuota seleccionado es: ' . $quotaTypeName . '.'), 0, 1);
        }

        $fpdf->Ln(4);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 6, utf8_decode(($quotaTypeName ? '5' : '4') . '. El cronograma de pagos será el siguiente:'), 0, 1);

        $fpdf->Ln(4);

        $fpdf->SetFont('Montserrat', 'B', 10);
        $fpdf->Cell(35, 6);
        $fpdf->Cell(40, 6, utf8_decode('NÚMERO'), 1, 0, 'C');
        $fpdf->Cell(40, 6, utf8_decode('CUOTA'), 1, 0, 'C');
        $fpdf->Cell(40, 6, utf8_decode('FECHA'), 1, 0, 'C');
        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 10);
        foreach ($contract->quotas as $quota) {
            $qDate = $quota->date instanceof \Carbon\Carbon ? $quota->date : Carbon::parse($quota->date);
            $fpdf->Cell(35, 6);
            $fpdf->Cell(40, 6, utf8_decode($quota->number), 1, 0, 'C');
            $fpdf->Cell(40, 6, utf8_decode(number_format($quota->amount, 2)), 1, 0, 'C');
            $fpdf->Cell(40, 6, utf8_decode($qDate->format('d/m/Y')), 1, 0, 'C');
            $fpdf->Ln();
        }

        // Espacio extra antes de FORMA DE PAGO
        $fpdf->Ln(8);
        $fpdf->SetFont('Montserrat', 'B', 12);
        $fpdf->Cell(190, 10, utf8_decode('FORMA DE PAGO'), 0, 1);

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 6, utf8_decode('1. Los pagos deberán realizarse puntualmente en las fechas acordadas. Cada día de retraso quedará registrado en el historial del cliente y podrá afectar futuros préstamos. Las formas de pago son: efectivo, transferencia vía bcp y yape'), 0, 1);

        // ...existing code...
        $fpdf->Ln(12);
        $fpdf->MultiCell(190, 10, utf8_decode('En señal de conformidad, las partes suscriben este documento en la ciudad de Piura, el día ' . $contractDate->isoFormat('D [de] MMMM [de] YYYY') . '.'), 0, 1);

        // Bloque de firmas: alineadas a la izquierda, una debajo de la otra
        // Reducir tamaño y espaciado para que estén más próximos y más pequeños
        $fpdf->Ln(8);
        $fpdf->SetFont('Montserrat', '', 10);
        // Firma de la empresa (sangría a la izquierda)
        $fpdf->Cell(80, 5, utf8_decode('__________________________'), 0, 1, 'L');
        $fpdf->Cell(80, 5, utf8_decode('CREDYFACIL'), 0, 1, 'L');
        $fpdf->Cell(80, 5, utf8_decode('RUC: 20512345678'), 0, 1, 'L');

        // Espacio reducido antes de la firma del cliente
        $fpdf->Ln(6);

        // Firma del cliente (misma sangría y alineación)
        $fpdf->Cell(80, 5, utf8_decode('__________________________'), 0, 1, 'L');
        $fpdf->Cell(80, 5, utf8_decode('EL MUTUARIO / CLIENTE'), 0, 1, 'L');
        // ...existing code...

        $fpdf->Output('D', 'contrato_' . $contract->id . '.pdf');
        exit();
    }

    private function convertToWords($number)
    {
        $number = floatval($number);
        $entero = floor($number);
        $decimales = round(($number - $entero) * 100);

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero == 0) {
            return 'CERO';
        }

        $palabras = '';

        // Millones
        if ($entero >= 1000000) {
            $millones = floor($entero / 1000000);
            if ($millones == 1) {
                $palabras .= 'UN MILLON ';
            } else {
                $palabras .= $this->convertirGrupo($millones, $unidades, $decenas, $especiales, $centenas) . ' MILLONES ';
            }
            $entero %= 1000000;
        }

        // Miles
        if ($entero >= 1000) {
            $miles = floor($entero / 1000);
            if ($miles == 1) {
                $palabras .= 'MIL ';
            } else {
                $palabras .= $this->convertirGrupo($miles, $unidades, $decenas, $especiales, $centenas) . ' MIL ';
            }
            $entero %= 1000;
        }

        // Centenas, decenas y unidades
        if ($entero > 0) {
            $palabras .= $this->convertirGrupo($entero, $unidades, $decenas, $especiales, $centenas);
        }

        return trim($palabras);
    }

    private function convertirGrupo($numero, $unidades, $decenas, $especiales, $centenas)
    {
        $resultado = '';

        // Centenas
        $c = floor($numero / 100);
        if ($c > 0) {
            if ($c == 1 && $numero == 100) {
                $resultado .= 'CIEN ';
            } else {
                $resultado .= $centenas[$c] . ' ';
            }
        }

        $numero %= 100;

        // Decenas y unidades
        if ($numero >= 10 && $numero <= 19) {
            $resultado .= $especiales[$numero - 10] . ' ';
        } else {
            $d = floor($numero / 10);
            $u = $numero % 10;

            if ($d > 0) {
                if ($d == 2 && $u > 0) {
                    $resultado .= 'VEINTI';
                } else {
                    $resultado .= $decenas[$d];
                    if ($u > 0) {
                        $resultado .= ' Y ';
                    } else {
                        $resultado .= ' ';
                    }
                }
            }

            if ($u > 0) {
                $resultado .= $unidades[$u] . ' ';
            }
        }

        return trim($resultado);
    }
}
