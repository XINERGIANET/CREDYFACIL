<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuxiliaryPaymentsUserSeeder extends Seeder
{
    /**
     * Create or update the auxiliary user for the payments module.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['user' => 'credypagos'],
            [
                'document' => '00000001',
                'name' => 'Usuario Auxiliar Pagos',
                'address' => null,
                'phone' => null,
                'email' => 'credypagos@credyfacil.test',
                'password' => Hash::make('AuxPagos2026*'),
                'role' => 'payments',
                'state' => 0,
                'deleted' => 0,
            ]
        );
    }
}
