<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONTRATO DE PRESTAMO PERSONAL</title>
    <style>
        body {
            text-align: justify;
            font-family: Helvetica, sans-serif;
            font-size: 10pt;
            width: 85%;
            margin: auto;
            padding: auto;
        }
    </style>
</head>

<body>
    <h4 style="text-align: center;">CONTRATO DE PRESTAMO PERSONAL</h4>
    <p>Conste por el presente documento el contrato de mutuo que celebran de una parte: </p>
    <p style="text-align: justify"><strong>CREDYFACIL SOLUCIONES S.A.C</strong> identificado con registro único con
        constituyente N° 20615044394 que
        se encuentra debidamente inscrita en la Partida Electrónica N° 11325302 del Registro de Personas Jurídicas de la
        Zona Registral N° I – Sede Piura / Oficina Registral Piura, y de la otra parte.
    </p>

    <p>{{ $contract->name }}, identificado con D.N.I. N° {{ $contract->document }} de estado civil
        {{ $contract->civil_status }} y con domicilio en {{ $contract->address }}, distrito de
        {{ $contract->district_name != null ? $contract->district_name : '___________' }}, provincia de {{ $contract->province != null ? $contract->province : '___________' }}, departamento de {{ $contract->department != null ? $contract->department : '___________' }}; a quien en lo sucesivo se denominará
        <strong>EL MUTUATARIO / CLIENTE.</strong>
    </p>

    <h4><strong><u>ANTECEDENTES:</u></strong></h4>
    <p><strong>PRIMERA.- CREDYFACIL SOLUCIONES S.A.C</strong> es una persona jurídica, a la concesión de créditos sin
        intermediación financiera; Ante ello, mediante el presente contrato a solicitud del <strong>EL MUTUATARIO/
            CLIENTE</strong> se otorga préstamo dinerario previa evaluación crediticia y cumpliendo con los requisitos
        que requiera <strong>CREDYFACIL SOLUCIONES S.A.C.</strong></p>
    <h4><strong><u>OBJETO DEL CONTRATO:</u></strong></h4>
    <P><strong>SEGUNDA.-</strong> Por el presente contrato, <strong>CREDYFACIL SOLUCIONES S.A.C</strong> se obligan a
        entregar en calidad de préstamo (mutuo Dinerario), en favor de EL MUTUATARIO / CLIENTE, la suma de S/
        ({{ $contract->amount_in_words }} 00/100 nuevos soles). </P>
    <p><strong>EL MUTUATARIO / CLIENTE</strong>, se obliga a devolver a <strong>CREDYFACIL SOLUCIONES S.A.C</strong>, la
        suma de dinero estipulada en el párrafo anterior, conforme al calendarios de pagos y condiciones pactadas en el
        presente contrato.</p>
    <h4><strong><u>OBLIGACIONES DE LAS PARTES:</u></strong></h4>
    <p><strong>TERCERA.- CREDYFACIL SOLUCIONES S.A.C</strong> se obligan a entregar la suma de dinero objeto de la
        prestación a su cargo en el momento de la firma de este documento, sin más constancia que las firmas de las
        partes puestas en el comprobante de entrega en anexo 1 -A al presente contrato.</p>
    <p><strong>CUARTA.- EL MUTUATARIO/ CLIENTE</strong> se obliga a devolver el íntegro del dinero objeto del mutuo, en
        un plazo de {{ $contract->total_days }} días como máximo, a partir de la firma del presente contrato, su pago
        será de manera
        {{ $contract->quota_type }}, generando un interés de {{ $contract->percentage }}% mensual, el cual no podrá ser
        modificado de manera
        unilateral por CREDYFACIL SOLUCIONES S.A.C, salvo que implique condiciones más favorables para EL MUTUATARIO/
        CLIENTE; Por tanto, EL MUTUATARIO/ CLIENTE devolverá la suma de S/
        {{ number_format($contract->payable_amount, 2) }} como
        consecuencia del cumplimiento de la obligación pactada; Asimismo, se obliga a cumplir con el pago en las
        oportunidades que se indican en el calendarios de pagos, indicado en el anexo 1-B del presente contrato.
    </p>
    <p><strong>QUINTA.- EL MUTUATARIO/ CLIENTE</strong> autoriza que la empresa <strong>CREDYFACIL SOLUCIONES
            S.A.C</strong>, pueda efectuar el cobro de la obligación contraída y de cada cuota descrita en el cronograma
        de pagos anexo 1-B, mediante personal debidamente acreditado, en el domicilio de <strong>EL MUTUATARIO/
            CLIENTE</strong> declarado en el presente contrato.</p>
    <p>En caso que <strong>EL MUTUATARIO/ CLIENTE</strong> efectúen pagos mediante aplicativos Yape, Plin, transferencia
        bancaria, interbancaria, el pago será comunicada a <strong>CREDYFACIL SOLUCIONES S.A.C</strong> siendo la
        empresa quien confirma el pago, comunicando el descargo de la cuota a <strong>EL MUTUATARIO/ CLIENTES.</strong>
    </p>
    <p>Para la validez de todas las comunicaciones y notificaciones a las partes, con motivo de la ejecución de este
        contrato, ambas señalan como sus respectivos domicilios los indicados en la introducción de este documento. El
        cambio de domicilio de cualquiera de las partes surtirá efecto desde la fecha de comunicación de dicho cambio a
        la otra parte, por cualquier medio escrito, y la aceptación debe ser previa evaluación de <strong>CREDYFACIL
            SOLUCIONES S.A.C.</strong></p>
    <p><strong>SEXTA.- EL MUTUATARIO/ CLIENTE</strong> se obliga a cumplir fielmente con el cronograma de pagos indicado
        en la cláusula
        anterior. En caso de incumplimiento en el pago de una de las armadas, cualquiera que sea, quedarán vencidas
        todas las demás, y en consecuencia <strong>CREDYFACIL SOLUCIONES S.A.C</strong> estará facultado para exigir el
        pago del íntegro
        de la suma de dinero mutuada, más los intereses que se generes, quedando facultado para efectuar acciones pre
        judiciales y judiciales de cobranza para la recuperación del crédito.</p>
    <p><strong>SEPTIMA.- EL MUTUATARIO/ CLIENTE</strong> autoriza el pago de S/ 6.00 (seis con 00/100 soles) adicional,
        al integro de la obligación descrita en la clausula Cuarta, por concepto de gastos operativos.</p>
    <p>OCTAVA.- En respaldo de la obligación asumida, frente a <strong>CREDYFACIL SOLUCIONES S.A.C</strong>, EL <strong>
            MUTUATARIO/ CLIENTE</strong>,
        suscribe un pagare N° {{ str_pad($contract->number_pagare == null ? '______' : $contract->number_pagare, 6, '0', STR_PAD_LEFT) }} emitido en forma incompleta. Los importes que no sean pagados por EL
        <strong>MUTUATARIO/ CLIENTE</strong>, en las oportunidades debidas devengaran por todo el tiempo que demore el
        pago, más
        intereses moratorios, compensatorios y gastos judiciales que genere la recuperación del crédito.
    </p>
    <p><strong>NOVENA.-</strong> Ambas partes convienen en que el presente contrato de mutuo se celebra a título
        oneroso, en
        consecuencia, EL <strong>MUTUATARIO/CLIENTE</strong> está obligado al pago de intereses compensatorios en favor
        de <strong>CREDYFACIL
            SOLUCIONES S.A.C</strong>, de acuerdo a la tasa y forma de pago a que se refiere el primer párrafo de la
        clausula cuarta
        del presente contrato.</p>
    <p><strong>DECIMO.-</strong> En lo no previsto por las partes en el presente contrato, ambas se someten a lo
        establecido por las normas del Código Civil y demás del sistema jurídico que resulten aplicables.</p>
    <p>Para efectos de cualquier controversia que se genere con motivo de la celebración y ejecución de este contrato,
        las partes se someten a la competencia territorial de los jueces y tribunales de la provincia del PIURA, </p>
    <p>En señal de conformidad las partes suscriben este documento en la ciudad de Piura, el dia
        {{ \Carbon\Carbon::parse($contract->date)->format('d/m') }} del
        {{ \Carbon\Carbon::parse($contract->date)->format('Y') }}.
    </p>

    <br><br><br><br>

    <table style="width: 100%; border: none;">
        <tr>
            <!-- Firma Empresa -->
            <td style="width: 50%; text-align: center; vertical-align: top; padding-right: 10px;">
                _______________________________________<br>
                <strong>CREDYFACIL SOLUCIONES S.A.C</strong><br>
                <strong>RUC N° 20615044394</strong><br>
                <div style="font-size: 8pt; margin-top: 5px;">
                    Poderes inscritos: Partida Electrónica N° 11325302 del Registro de Personas Jurídicas de la Zona
                    Registral N° I – Sede Piura / Oficina Registral Piura.
                </div>
            </td>

            <!-- Firma Cliente -->
            <td style="width: 50%; text-align: center; vertical-align: top; padding-left: 10px;">
                _______________________________________<br>
                <strong>EL MUTUATARIO/ CLIENTE</strong><br>
                <div style="margin-top: 5px;">
                    {{ $contract->name }}<br>
                    DNI: {{ $contract->document }}
                </div>
            </td>
        </tr>
    </table>
    <div style="page-break-after: always;"></div>
    <h4 style="text-align: center;"><strong>ANEXO 1 – A. –COMPROBANTE DE ENTREGA.</strong></h4>
    <p style="margin: 3px 0; line-height: 1.3;">NUMERO DE CONTRATO: N° {{ $contract->id }}</p>
    <p style="margin: 3px 0; line-height: 1.3;">RECIBI DE: <strong>CREDYFACIL SOLUCIONES S.A.C</strong></p>
    <p style="margin: 3px 0; line-height: 1.3;">LA SUMA DE: ({{ $contract->amount_in_words }} 00/100 nuevos soles) </p>
    <p style="margin: 3px 0; line-height: 1.3;">POR CONCEPTO: DE PRESTAMO DE DINERO PARA DEVOLVER</p>
    <p style="margin: 3px 0; line-height: 1.3;">CLIENTE: {{ $contract->name }}</p>
    <p style="margin: 3px 0; line-height: 1.3;">DNI N°: {{ $contract->document }}</p>

    <P>RUTA / ASESOR</P>
    <table style="width: 100%; border-collapse: collapse; border: 2px solid black; margin-top: 10px;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 9pt;">NUMERO
                    DE<br>PRÉSTAMO</th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 9pt;">APELLIDOS
                    Y<br>NOMBRE DEL<br>CLIENTE</th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 9pt;">NUMERO<br>DE DNI
                </th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 9pt;">DIRECCIÓN</th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 9pt;">MONTO</th>
                <th style="border: 1px solid black; padding: 8px; text-align: center; font-size: 9pt;">FIRMA.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid black; padding: 10px; text-align: center;">{{ $contract->id }}</td>
                <td style="border: 1px solid black; padding: 10px;">{{ $contract->name }}</td>
                <td style="border: 1px solid black; padding: 10px;">{{ $contract->document }}</td>
                <td style="border: 1px solid black; padding: 10px;">{{ $contract->address }}</td>
                <td style="border: 1px solid black; padding: 10px;">{{ number_format($contract->requested_amount, 2) }}
                </td>
                <td style="border: 1px solid black; padding: 10px;">&nbsp;</td>
            </tr>
        </tbody>
    </table>
    <br>
    <h4 style="text-align: center;"><strong>ANEXO 1 – B. –CRONOGRAMA DE PAGOS.</strong></h4>

    <p style="text-align: justify;">
        <strong>EL MUTUATARIO/ CLIENTE</strong>, bajo el presente documento deja constancia que toma conocimiento del
        cronograma de pagos que se indica el contrato de MUTUO DINERARIO.
    </p>

    <p style="margin-top: 15px; margin-bottom: 5px;"><strong><u>Datos del Prestamo:</u></strong></p>

    <table style="width: 100%; border: none; margin-bottom: 15px;">
        <tr>
            <td style="width: 33%; padding: 5px;">
                <strong>Monto:</strong> S/. {{ number_format($contract->requested_amount, 2) }}
            </td>
            <td style="width: 34%; padding: 5px;">
                <strong>Tasa:</strong> {{ $contract->percentage }}% mensual
            </td>
            <td style="width: 33%; padding: 5px;">
                <strong>Cuotas:</strong> {{ $contract->quotas_number }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 5px;">
                <strong>Monto a Devolver:</strong> S/. {{ number_format($contract->payable_amount, 2) }}
            </td>
            <td style="padding: 5px;">
                <strong>Periodicidad:</strong> {{ strtoupper($contract->quota_type) }}
            </td>
        </tr>
    </table>
    <h4 style="text-align: center; margin-top: 20px; margin-bottom: 10px;">CRONOGRAMA</h4>

    <table style="width: 100%; border-collapse: collapse; border: 2px solid black;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 1px solid black; padding: 10px; text-align: center; font-weight: bold;">CUOTA</th>
                <th style="border: 1px solid black; padding: 10px; text-align: center; font-weight: bold;">FECHA</th>
                <th style="border: 1px solid black; padding: 10px; text-align: center; font-weight: bold;">TOTAL A PAGAR
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contract->quotas as $quota)
                <tr>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $quota->number }}</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">
                        {{ \Carbon\Carbon::parse($quota->date)->format('d/m/Y') }}</td>
                    <td style="border: 1px solid black; padding: 8px; text-align: center;">S/.
                        {{ number_format($quota->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br><br><br>

    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 35%; text-align: center; border-bottom: 2px solid black;">
                &nbsp;
            </td>
            <td style="width: 30%;">
                &nbsp;
            </td>
            <td style="width: 35%; text-align: center; border-bottom: 2px solid black;">
                &nbsp;
            </td>
        </tr>
        <tr>
            <td style="width: 35%; text-align: center; padding-top: 2px;">
                <strong>{{ $contract->name }}</strong>
            </td>
            <td style="width: 30%;">
                &nbsp;
            </td>
            <td style="width: 35%; text-align: center; padding-top: 2px;">
                <strong>CREDYFACIL SOLUCIONES S.A.C</strong>
            </td>
        </tr>
    </table>
    <div style="page-break-after: always;"></div>
    <h2 style="text-align: center; margin-bottom: 20px;">PAGARE</h2>
    
    <p style="margin: 5px 0; line-height: 1.5;">
        <strong>PAGARE N°</strong> : 
        @if(empty($contract->number_pagare))
            _____________________
        @else
            {{ str_pad($contract->number_pagare, 6, '0', STR_PAD_LEFT) }}
        @endif
    </p>
    <p style="margin: 5px 0; line-height: 1.5;">
        <strong>FECHA DE EMISIÓN:</strong> {{ \Carbon\Carbon::parse($contract->date)->format('d/m/Y') }}
        <span style="float: right;"><strong>IMPORTE DEUDOR S/</strong> {{ number_format($contract->payable_amount, 2) }}</span>
    </p>
    <p style="margin: 5px 0; line-height: 1.5;">
        <strong>IMPORTE ORIGINAL:</strong> {{ number_format($contract->requested_amount, 2) }}
    </p>
    <p style="margin: 5px 0; line-height: 1.5;">
        <strong>FECHA DE VENCIMIENTO:</strong> {{ \Carbon\Carbon::parse($contract->last_payment_date)->format('d/m/Y') }}
    </p>
    <p style="margin: 5px 0; line-height: 1.5;">
        <strong>YO:</strong> {{ $contract->name }}
        <span style="float: right;"><strong>identificado con D.N.I. N.°</strong> {{ $contract->document }}</span>
    </p>

    <p style="margin-top: 15px;">
        Reconozco/ reconocemos que adeudo y pagare/pagaremos solidariamente, incondicionalmente en la fecha de
        vencimientos consignado en el presente Pagaré, a la orden de <strong>CREDYFACIL SOLUCIONES S.A.C</strong> la
        cantidad de S/ <u>{{ number_format($contract->payable_amount, 2) }}</u> soles, sin lugar a reclamo de alguna
        clase, para cuyo fiel y exacto cumplimiento.
    </p>

    <h4 style="margin-top: 15px;"><strong>CLAUSULAS ESPECIALES.</strong></h4>

    <ol style="text-align: justify; line-height: 1.4; font-size: 9pt;">
        <li style="margin-bottom: 8px;">
            Este pagaré debe ser pagado en la misma moneda que expresa el título valor.
        </li>
        <li style="margin-bottom: 8px;">
            A su vencimiento, podrá de ser prorrogado por <strong>CREDYFACIL SOLUCIONES S.A.C</strong>, o por su tenedor
            por el plazo que este señale en el mismo documento, sin que sea necesaria intervención alguna del obligado
            principal.
        </li>
        <li style="margin-bottom: 8px;">
            El importe de este Pagaré, y/o de las cuotas del crédito que representa, generará desde la fecha de emisión
            hasta la fecha de su respectivo (s) vencimiento(s), un interés compensatorio que se pacta en la tasa de
            <strong>{{ $contract->percentage }}%</strong> mensual.
        </li>
        <li style="margin-bottom: 8px;">
            El importe deudor se les aplicará los intereses compensatorios e intereses moratorios a las tasas máximas
            autorizadas por <strong>CREDYFACIL SOLUCIONES S.A.C</strong> o permitidas a su último Tenedor.
            <br>
            En caso de no ser cancelado el importe de una o más cuotas del crédito que representa este Pagaré, los
            constituye en mora aplicándose los intereses moratorios desde la fecha de vencimiento hasta su total
            cancelación, sin que sea necesario requerimiento alguno de pago para constituir mora al obligado principal,
            incurriéndose en ésta automáticamente por el sólo hecho del vencimiento
        </li>
        <li style="margin-bottom: 8px;">
            El deudor acepta que la tasa de interés compensatorio y/o moratorio pueden ser variadas por <strong>CREDYFACIL
                SOLUCIONES S.A.C</strong> y /o su ultimo tenedor sin necesidad de aviso previo, de acuerdo a las tasas que
            ésta tenga vigente.
        </li>
        <li style="margin-bottom: 8px;">
            Este título no está sujeto a protesto por falta de pago, salvo lo dispuesto en el artículo 81.2 de la ley 27287
            y sus normas complementarias.
        </li>
        <li style="margin-bottom: 8px;">
            La empresa <strong>CREDYFACIL SOLUCIONES S.A.C</strong> y/o tenedor podrá entablar acción judicial para efectuar
            el cobro de este Pagaré donde lo tuviera conveniente, para todos los efectos y consecuencias que pudieran
            derivarse de la emisión del presente Pagaré, el indicado en este documento, lugar donde se enviaran los avisos y
            se harán llegar todas las comunicaciones y/o notificaciones judiciales que resulten necesarias. El presente
            Pagaré está sujeto a la Ley Peruana de Títulos Valores vigente a la fecha de suscripción de Pagaré.
        </li>
    </ol>

    <p style="text-align: right; margin-top: 30px;">
        Piura __________________ 2026.
    </p>

    <table style="width: 100%; border-collapse: collapse; border: 2px solid black; margin-top: 20px;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="border: 2px solid black; padding: 8px; text-align: center; font-size: 9pt; width: 30%;">
                    N° Apellidos y<br>nombres
                </th>
                <th style="border: 2px solid black; padding: 8px; text-align: center; font-size: 9pt; width: 15%;">
                    DNI N°
                </th>
                <th style="border: 2px solid black; padding: 8px; text-align: center; font-size: 9pt; width: 25%;">
                    Dirección
                </th>
                <th style="border: 2px solid black; padding: 8px; text-align: center; font-size: 9pt; width: 20%;">
                    Firma
                </th>
                <th style="border: 2px solid black; padding: 8px; text-align: center; font-size: 9pt; width: 20%;">
                    Huella
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 2px solid black; padding: 20px 8px; height: 80px;">{{ $contract->name }}</td>
                <td style="border: 2px solid black; padding: 20px 8px;">{{ $contract->document }}</td>
                <td style="border: 2px solid black; padding: 20px 8px;">{{ $contract->address }}</td>
                <td style="border: 2px solid black; padding: 30px 8px;"></td>&nbsp;</td>
                <td style="border: 2px solid black; padding: 40px 8px;">&nbsp;</td>
            </tr>
        </tbody>
    </table>
    
</body>

</html>
