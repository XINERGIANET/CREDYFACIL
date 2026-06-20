<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'seller_id',
        'contract_id',
        'request_uid',
        'payment_method_id',
        'date',
        'image',
        'image_2',
        'deleted'
    ];

    protected $dates = ['date'];

    public $timestamps = false;

    // Exponer el atributo calculado `amount` que suma los payments asociados
    protected $appends = ['amount'];

    public function scopeActive($query){
        return $query->where('deleted', 0);
    }

    public function seller(){
        return $this->belongsTo(User::class);
    }

    public function contract(){
        return $this->belongsTo(Contract::class);
    }

    /**
     * Pagos asociados a este gasto (relación inversa)
     */
    public function expensePayments()
    {
        return $this->hasMany(ExpensePayment::class, 'expenses_id');
    }

    public function getAmountAttribute()
    {
        return (float) $this->expensePayments()->sum('amount');
    }

    public static function activeDisbursementExists(int $contractId, ?int $exceptId = null): bool
    {
        return static::active()
            ->where('contract_id', $contractId)
            ->when($exceptId, function ($query) use ($exceptId) {
                return $query->where('id', '!=', $exceptId);
            })
            ->exists();
    }

}
