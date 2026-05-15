<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month ?: Carbon::now()->month;
        $year = $request->year ?: Carbon::now()->year;

        $sellers = User::seller()->active()->where('state', 0)->get();

        $goals = Goal::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('seller_id');

        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return view('goals.index', compact('sellers', 'goals', 'month', 'year', 'months'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'goals' => 'required|array',
        ]);

        foreach ($request->goals as $seller_id => $data) {
            Goal::updateOrCreate(
                [
                    'seller_id' => $seller_id,
                    'month' => $request->month,
                    'year' => $request->year,
                ],
                [
                    'clients' => $data['clients'] ?? 0,
                    'new_clients' => $data['new_clients'] ?? 0,
                    'disbursement' => $data['disbursement'] ?? 0,
                ]
            );
        }

        return redirect()->route('goals.index', ['month' => $request->month, 'year' => $request->year])
            ->with('message', 'Metas actualizadas correctamente');
    }
}
