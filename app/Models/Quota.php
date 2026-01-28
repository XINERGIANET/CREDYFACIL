<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quota extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'number',
        'amount',
        'debt',
        'date',
        'paid',
    ];

    protected $dates = ['date'];

    public $timestamps = false;

    public function scopeActive($query){
        return $query->whereHas('contract', function($query){
            return $query->where('deleted' , 0);
        });
    }

    public function contract(){
        return $this->belongsTo(Contract::class);
    }

    public function payments(){
        return $this->hasMany(Payment::class);
    }
}
