<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'date',
        'marked',
        'user_id',
    ];

    protected $dates = ['date'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
