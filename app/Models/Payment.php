<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'quota_id',
        'amount',
        'payment_method_id',
        'date',
        'due_days',
        'image',
        'people',
        'deleted'
    ];

    protected $dates = ['date'];

    public $timestamps = false;

    public function scopeActive($query){
        return $query->where('deleted', 0);
    }

    public function scopeBcp($query)
    {
        $ids = PaymentMethod::whereRaw('UPPER(name) LIKE ?', ['%BCP%'])->pluck('id');

        if ($ids->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('payment_method_id', $ids);
    }

    public function quota(){
        return $this->belongsTo(Quota::class);
    }

    public function payment_method(){
        return $this->belongsTo(PaymentMethod::class);
    }

    public function people(){
        $html = '';


        $people = $this->people ? json_decode($this->people) : [];

        foreach($people as $client){
            $html .= '- '.$client->document.' / '.$client->name.'<br>';
        }

        return $html;
    }

    /**
     * Reparte el monto del pago entre capital, interés y seguro según la composición del contrato.
     *
     * @return array{capital: float, interest: float, insurance: float}
     */
    public function capitalInterestInsuranceBreakdown(): array
    {
        $contract = optional(optional($this->quota)->contract);
        $payable = (float) ($contract->payable_amount ?? 0);
        $amount = (float) $this->amount;

        if (!$contract || $payable <= 0 || $amount <= 0) {
            return ['capital' => 0.0, 'interest' => 0.0, 'insurance' => 0.0];
        }

        $requested = (float) ($contract->requested_amount ?? 0);
        $interest = (float) ($contract->interest ?? 0);

        $capital = round($amount * $requested / $payable, 2);
        $interestPart = round($amount * $interest / $payable, 2);
        $insurancePart = round($amount - $capital - $interestPart, 2);

        return [
            'capital' => $capital,
            'interest' => $interestPart,
            'insurance' => $insurancePart,
        ];
    }
}
