<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher',
        'number',
        'date',
        'date_delivery',
        'type',
        'client_id',
        'store_id',
        'cash_id',
        'payment_method_id_1',
        'amount_1',
        'payment_method_id_2',
        'amount_2',
        'total',
        'debt',
        'payment_method_id_debt',
        'date_debt',
        'note',
        'paid',
        'deleted'
    ];

    protected $dates = ['date', 'date_delivery'];
    
    public $timestamps = false;

    public function payment_method_1(){
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id_1');
    }

    public function payment_method_2(){
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id_2');
    }

    public function payment_method_debt(){
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id_debt');
    }

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function store(){
        return $this->belongsTo(Store::class);
    }

    public function cash(){
        return $this->belongsTo(Cash::class);
    }

    public function details(){
        return $this->hasMany(SaleDetail::class);
    }


}
