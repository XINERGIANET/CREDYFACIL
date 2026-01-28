<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(Request $request){
        $this->request = $request;
    }

    public function collection()
    {
        return Sale::with('details')
            ->when($this->request->store_id, function($query, $store_id){
                return $query->where('store_id', $store_id);
            })
            ->when($this->request->payment_method_id, function($query, $payment_method_id){
                return $query->where(function($query) use ($payment_method_id){
                    return $query->where('payment_method_id_1', $payment_method_id)
                        ->orWhere('payment_method_id_2', $payment_method_id);
                });
            })
            ->when($this->request->start_date, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($this->request->end_date, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })
            ->when($this->request->turn, function($query, $turn){
                return $query->whereHas('cash', function($query) use($turn){
                    return $query->where('turn', $turn);
                });
            })
            ->when($this->request->number, function($query, $number){
                return $query->where('number', 'like', '%'.$number);
            })
            ->latest('date')
            ->get();
    }

    public function map($sale): array
    {
        $array = [];
        
        foreach($sale->details as $detail){
            $array[] = [
                optional($sale->store)->name,
                optional($sale->cash)->turn,
                $sale->voucher,
                $sale->number,
                $sale->date->format('d/m/Y'),
                $sale->type,
                optional($sale->payment_method_1)->name,
                optional($sale->payment_method_2)->name,
                optional($sale->client)->name,
                optional($detail->product)->name,
                $detail->price,
                $detail->quantity,
                number_format($detail->price * $detail->quantity, 2)
            ];
        };
        
        return $array;
        
    }

    public function headings(): array
    {
        return [
            'Sede',
            'Turno',
            'Comprobante',
            'Número',
            'Fecha',
            'Tipo de venta',
            'Métodos de pago',
            'Métodos de pago',
            'Cliente',
            'Producto',
            'Precio',
            'Cantidad',
            'Subtotal'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('F1:G1');
        
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
