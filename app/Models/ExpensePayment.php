<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Expense;
use App\Models\PaymentMethod;

class ExpensePayment extends Model
{
    use HasFactory;
    protected $table = 'expenses_payments';

    protected $fillable = [
        'expenses_id',
        'payment_method_id',
        'amount'

    ];

    /**
     * Expense al que pertenece este pago
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expenses_id');
    }

    /**
     * Método de pago asociado
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
