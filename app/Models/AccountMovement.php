<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method_id',
        'type',
        'amount',
        'description',
        'date',
        'deleted',
    ];

    protected $dates = ['date'];

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function typeName(): string
    {
        return $this->type === 'income' ? 'Ingreso' : 'Egreso';
    }

    public function signedAmount(): float
    {
        return $this->type === 'income' ? (float) $this->amount : -1 * (float) $this->amount;
    }
}
