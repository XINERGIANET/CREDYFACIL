<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Config extends Model
{
    protected $table = 'config';
    
    protected $fillable = [
        'insurance', 'number_pagare',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::updated(function ($sale) {
            try {

                $dirtyAttributes = $sale->getDirty(); // Atributos que han cambiado
                $originalValues = [];

                foreach ($dirtyAttributes as $key => $value) {
                    $originalValues[$key] = $sale->getOriginal($key); // Valores originales de los atributos que cambiaron
                }

                Bitacora::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'UPDATE',
                    'table' => $sale->getTable(),
                    'date' => now(),
                    'before' =>json_encode($originalValues),
                    'after' => json_encode($sale->getChanges())
                ]);
            }catch (\Exception $e) {
                Log::error('Error al registrar en la bitácora: ' . $e->getMessage());
            }
        });
    }

}
