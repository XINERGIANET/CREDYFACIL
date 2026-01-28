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
}
