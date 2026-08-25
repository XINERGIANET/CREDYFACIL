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
        'approved',
        'type_quota',
    ];

    protected $dates = ['date', 'first_payment_date', 'last_payment_date'];

    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('deleted', 0);
    }

    public function scopeSearchClient($query, $search, $tablePrefix = '')
    {
        if (empty(trim((string) $search))) {
            return $query;
        }

        $words = array_filter(explode(' ', trim((string) $search)));
        $nameCol = $tablePrefix ? $tablePrefix . '.name' : 'name';
        $groupCol = $tablePrefix ? $tablePrefix . '.group_name' : 'group_name';
        $docCol = $tablePrefix ? $tablePrefix . '.document' : 'document';
        $peopleCol = $tablePrefix ? $tablePrefix . '.people' : 'people';

        return $query->where(function ($q) use ($words, $nameCol, $groupCol, $docCol, $peopleCol) {
            foreach ($words as $word) {
                $q->where(function ($sub) use ($word, $nameCol, $groupCol, $docCol, $peopleCol) {
                    $sub->where($nameCol, 'like', '%' . $word . '%')
                        ->orWhere($groupCol, 'like', '%' . $word . '%')
                        ->orWhere($docCol, 'like', '%' . $word . '%')
                        ->orWhere($peopleCol, 'like', '%' . $word . '%');
                });
            }
        });
    }

    public function getQuotaTypeAttribute()
    {
        if (isset($this->attributes['quota_type']) && !empty($this->attributes['quota_type'])) {
            return $this->attributes['quota_type'];
        }

        $quotaTypeMap = [1 => 'Semanal', 2 => 'Catorcenal', 4 => 'Mensual'];

        if (!is_null($this->type_quota) && isset($quotaTypeMap[(int) $this->type_quota])) {
            return $quotaTypeMap[(int) $this->type_quota];
        }

        $firstTwo = $this->quotas()->orderBy('date')->limit(2)->get();
        if ($firstTwo->count() > 1) {
            $daysDiff = \Carbon\Carbon::parse($firstTwo[0]->date)->diffInDays(\Carbon\Carbon::parse($firstTwo[1]->date));
            if ($daysDiff >= 25 && $daysDiff <= 35) {
                return 'Mensual';
            } elseif ($daysDiff >= 12 && $daysDiff <= 16) {
                return 'Catorcenal';
            } elseif ($daysDiff >= 5 && $daysDiff <= 9) {
                return 'Semanal';
            }
        }

        return 'Semanal';
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
        return app(\App\Services\ClientPortfolioService::class)->portfolioClientType($this);
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
    public function expenses()
    {
        return $this->hasMany(Expense::class)->active();
    }
}
