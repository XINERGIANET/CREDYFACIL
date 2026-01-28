<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'number_pagare',
        'client_type',
        'group_name',
        'people',
        'document',
        'name',
        'phone',
        'address',
        'district_id',
        'reference',
        'home_type',
        'business_line',
        'business_address',
        'business_start_date',
        'civil_status',
        'husband_name',
        'husband_document',
        'seller_id',
        'requested_amount',
        'months_number',
        'quotas_number',
        'percentage',
        'interest',
        'insurance_amount',
        'payable_amount',
        'quota_amount',
        'date',
        'first_payment_date',
        'last_payment_date',
        'paid',
        'deleted',
    ];

    protected $dates = ['date', 'first_payment_date', 'last_payment_date'];

    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }

    public function client()
    {
        if ($this->client_type == 'Personal') {
            return $this->name;
        } elseif ($this->client_type == 'Grupo') {
            return $this->group_name;
        }
    }

    public function type()
    {
        if ($this->client_type == 'Personal') {
            $contracts = Contract::where('document', $this->document)->count();

            if ($contracts > 1) {
                return 'Recurrente';
            }

            return 'Nuevo';
        }

        return 'Nuevo';
    }

    public function seller()
    {
        return $this->belongsTo(User::class);
    }

    public function quotas()
    {
        return $this->hasMany(Quota::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function people()
    {
        $html = '';


        $people = $this->people ? json_decode($this->people) : [];

        foreach ($people as $client) {
            $html .= '- ' . $client->document . ' / ' . $client->name . '<br>';
        }

        return $html;
    }
}
