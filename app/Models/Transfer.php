<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'from_seller_id',
        'to_seller_id',
        'from_payment_method_id',
        'to_payment_method_id',
        'amount',
        'reason',
        'date',
        'deleted'
    ];

    protected $dates = ['date'];

    public $timestamps = false;

    public function type(){
        if($this->type == 'seller'){
            return 'Asesor';
        }elseif($this->type == 'payment_method'){
            return 'Método de pago';
        }
    }

    public function from(){
        if($this->type == 'seller'){
            return $this->from_seller;
        }elseif($this->type == 'payment_method'){
            return $this->from_payment_method;
        }
    }

    public function to(){
        if($this->type == 'seller'){
            return $this->to_seller;
        }elseif($this->type == 'payment_method'){
            return $this->to_payment_method;
        }
    }

    public function scopeActive($query){
        return $query->where('deleted' , 0);
    }

    public function from_seller(){
        return $this->belongsTo(User::class, 'from_seller_id');
    }

    public function to_seller(){
        return $this->belongsTo(User::class, 'to_seller_id');
    }

    public function from_payment_method(){
        return $this->belongsTo(PaymentMethod::class, 'from_payment_method_id');
    }

    public function to_payment_method(){
        return $this->belongsTo(PaymentMethod::class, 'to_payment_method_id');
    }
}
