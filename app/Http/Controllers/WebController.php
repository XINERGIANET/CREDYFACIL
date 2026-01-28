<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Contract;
use App\Models\User;
use App\Models\Transfer;
use App\Models\Department;
use App\Models\Province;
use App\Models\District;

class WebController extends Controller
{
    public function index(Request $request){
        $user = auth()->user();

        /* Administrador */

        /* Inicio */

        $home_sales_1 = Payment::active()->where('payment_method_id', 1)->when($request->start_date_4, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->when($request->seller_id_1, function($query, $seller_id){
            return $query->whereHas('quota.contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->sum('amount');

        // Use accessor `amount` (calculated from expensePayments) so we must load models
        $home_expenses_1 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 1);
            })
            ->when($request->start_date_4, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_4, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->when($request->seller_id_1, function($query, $seller_id){
                return $query->where('seller_id', $seller_id);
            })->with('expensePayments')
            ->get()
            ->sum('amount');

        $home_transfers_1_from = Transfer::active()->when($request->seller_id_1, function($query, $seller_id){
            return $query->where('from_seller_id', $seller_id);
        })->when($request->start_date_4, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'seller')->sum('amount');

        $home_transfers_1_to = Transfer::active()->when($request->seller_id_1, function($query, $seller_id){
            return $query->where('to_seller_id', $seller_id);
        })->when($request->start_date_4, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_4, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->where('type', 'seller')->sum('amount');

        $home_sales_1 = $home_sales_1 - $home_expenses_1 - $home_transfers_1_from + $home_transfers_1_to;

        /* Cuadre general */

        $sales_1 = Payment::active()->where('payment_method_id', 1)->when($request->start_date_3, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_3, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');

        $expenses_1 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 1);
            })->when($request->start_date_3, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_3, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->with('expensePayments')->get()->sum('amount');

        $transfers_1_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 1)->sum('amount');
        
        $transfers_1_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 1)->sum('amount');

        $sales_2 = Payment::active()->where('payment_method_id', 2)->when($request->start_date_3, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_3, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');
        
        $expenses_2 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 2);
            })->when($request->start_date_3, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_3, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->with('expensePayments')->get()->sum('amount');

        $transfers_2_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 2)->sum('amount');
        
        $transfers_2_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 2)->sum('amount');

        $sales_3 = Payment::active()->where('payment_method_id', 3)->when($request->start_date_3, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_3, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');
        
        $expenses_3 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 3);
            })->when($request->start_date_3, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_3, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->with('expensePayments')->get()->sum('amount');

        $transfers_3_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 3)->sum('amount');
        
        $transfers_3_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 3)->sum('amount');

        $sales_4 = Payment::active()->where('payment_method_id', 4)->when($request->start_date_3, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_3, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');

        $expenses_4 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 4);
            })->when($request->start_date_3, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_3, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->with('expensePayments')->get()->sum('amount');

        $transfers_4_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 4)->sum('amount');
        
        $transfers_4_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 4)->sum('amount');

        $sales_5 = Payment::active()->where('payment_method_id', 5)->when($request->start_date_3, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_3, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->sum('amount');

        $expenses_5 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 5);
            })->when($request->start_date_3, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_3, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->with('expensePayments')->get()->sum('amount');

        $transfers_5_from = Transfer::active()->where('type', 'payment_method')->where('from_payment_method_id', 5)->sum('amount');
        
        $transfers_5_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 5)->sum('amount');

        $sales_6 = 0; // Pagos Caja chica

        $expenses_6 = Expense::active()->whereHas('expensePayments', function($q){
                return $q->where('payment_method_id', 6);
            })->when($request->start_date_3, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date_3, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->with('expensePayments')->get()->sum('amount');
        
        $transfers_6_to = Transfer::active()->where('type', 'payment_method')->where('to_payment_method_id', 6)->sum('amount');
        
        $sales_1 = $sales_1 - $expenses_1 - $transfers_1_from + $transfers_1_to;
        $sales_2 = $sales_2 - $expenses_2 - $transfers_2_from + $transfers_2_to;
        $sales_3 = $sales_3 - $expenses_3 - $transfers_3_from + $transfers_3_to;
        $sales_4 = $sales_4 - $expenses_4 - $transfers_4_from + $transfers_4_to;
        $sales_5 = $sales_5 - $expenses_5 - $transfers_5_from + $transfers_5_to;
        $sales_6 = $sales_6 - $expenses_6 + $transfers_6_to;
        $total = $sales_1 + $sales_2 + $sales_3 + $sales_4 + $sales_5 + $sales_6;


        // CARTERA TOTAL : suma de deuda entre las fechas establecidas
        $wallet_total = Quota::when($request->start_date_1, function($query, $start_date){
            return $query->whereHas('contract', function($query) use($start_date){
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_1, function($query, $end_date){
            return $query->whereHas('contract', function($query) use($end_date){
                return $query->whereDate('date', '<=', $end_date);
            });
        })->where('paid', 0)->sum('debt');

        // DEUDA TOTAL : CUOTAS QUE FALTAN PAGAR POR CLIENTES MOROSOS
        $due_total = Quota::when($request->start_date_1, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_1, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
        })->where('paid', 0)
        ->whereHas('contract', function($q){ // suma de due_days de todos los payments de las cuotas del contrato > 0
            return $q->whereRaw("(select coalesce(sum(p.due_days),0) from payments p inner join quotas qt on p.quota_id = qt.id where qt.contract_id = contracts.id) > 0");
        })->sum('debt');

        $payments = Payment::active()->when($request->start_date_1, function($query, $start_date){
            return $query->whereHas('quota.contract', function($query) use($start_date){
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_1, function($query, $end_date){
            return $query->whereHas('quota.contract', function($query) use($end_date){
                return $query->whereDate('date', '<=', $end_date);
            });
        })->sum('amount');

        // Load expenses and sum using accessor `amount` (which sums expensePayments)
        $expenses = Expense::when($request->start_date_1, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_1, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->active()->with('expensePayments')->get()->sum('amount');

        $today_real = $payments - $expenses;

        // PAGOS DE HOY : todos los pagos de now()
        $today_payments = Payment::whereDate('date', now())->sum('amount');
        // Quota::when($request->start_date_1, function($query, $start_date){
        //     return $query->whereHas('contract', function($query) use($start_date){
        //         return $query->whereDate('date', '>=', $start_date);
        //     });
        // })->when($request->end_date_1, function($query, $end_date){
        //     return $query->whereHas('contract', function($query) use($end_date){
        //         return $query->whereDate('date', '<=', $end_date);
        //     });
        // })
        // ->whereDate('date', now())->where('paid', 0)->sum('amount');

        //PAGOS PUNTUALES DE HOY : pagos de hoy de cuotas cuya fecha es hoy (puntuales)
        $today_timely_payments = Payment::whereHas('quota', function($q) {
            return $q->whereDate('date',now());
        })
        ->whereDate('date',now()) //pagos y cuotas con la misma fecha (hoy)
        ->sum('amount');

        //PROYECTADO DE HOY : todo lo que está para hoy (pagado y no pagado)
        $today_projected = Quota::whereDate('date',now())
        ->sum('amount');

        // $today_projected = $today_real + $today_payments;

        /* Asesor */

        // TOTAL DE CLIENTES (únicos por document|group_name) respetando mismos filtros
        $total_clients_count = DB::table('contracts')
            ->when($user->hasRole('seller'), function($q){
                return $q->where('seller_id', auth()->user()->id);
            })
            ->when($request->seller_id_2, function($q, $seller_id){
                return $q->where('seller_id', $seller_id);
            })
            ->when($request->start_date_2, function($q, $start_date){
                return $q->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date_2, function($q, $end_date){
                return $q->whereDate('date', '<=', $end_date);
            })
            ->where('deleted', 0)
            ->where('paid', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(document,''),'|',COALESCE(group_name,''))) as total")
            ->value('total');


        // CLIENTES CON MORA: tienen al menos un payment con due_days > 120
        // OR tienen una cuota impaga (paid = 0) cuya fecha es <= hoy - 120 días
        $cutoff = now()->subDays(120)->toDateString();

        $due_clients = DB::table('contracts')
            ->join('quotas', 'quotas.contract_id', 'contracts.id')
            ->leftJoin('payments', 'payments.quota_id', 'quotas.id')
            ->when($user->hasRole('seller'), function($q){
                return $q->where('contracts.seller_id', auth()->user()->id);
            })
            ->when($request->seller_id_2, function($q, $seller_id){
                return $q->where('contracts.seller_id', $seller_id);
            })
            ->when($request->start_date_2, function($q, $start_date){
                return $q->whereDate('contracts.date', '>=', $start_date);
            })
            ->when($request->end_date_2, function($q, $end_date){
                return $q->whereDate('contracts.date', '<=', $end_date);
            })
            ->where(function($q) use ($cutoff){
                $q->where('payments.due_days', '>=', 120)
                  ->orWhere(function($q2) use ($cutoff){
                      $q2->where('quotas.paid', 0)
                         ->whereDate('quotas.date', '<=', $cutoff);
                  });
            })
            ->where('contracts.deleted', 0)
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(contracts.document,''),'|',COALESCE(contracts.group_name,''))) as total")
            ->value('total');

       
        // CLIENTES NO MOROSOS = total - morosos (no puede ser negativo)
        $active_clients = max(0, intval($total_clients_count) - intval($due_clients));

        $seller_wallet = Quota::when($user->hasRole('seller'), function($query){
            return $query->whereHas('contract', function($query){
                return $query->where('seller_id', auth()->user()->id);
            });
        })->when($request->seller_id_2, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date_2, function($query, $start_date){
            return $query->whereHas('contract', function($query) use($start_date){
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_2, function($query, $end_date){
            return $query->whereHas('contract', function($query) use($end_date){
                return $query->whereDate('date', '<=', $end_date);
            });
        })->whereHas('contract', function($query){
            return $query->where('deleted', 0);
        })->where('paid', 0)->sum('debt');

        $requested_amount = Contract::active()->when($user->hasRole('seller'), function($query){
            return $query->where('seller_id', auth()->user()->id);
        })->when($request->seller_id_2, function($query, $seller_id){
            return $query->where('seller_id', $seller_id);
        })->when($request->start_date_2, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date_2, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
        })->sum('requested_amount');

        /* Gráficos */

        $sales_months_1 = Payment::active()->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->when($request->start_date_1, function($query, $start_date){
                return $query->whereHas('quota.contract', function($query) use($start_date){
                    return $query->whereDate('date', '>=', $start_date);
                });
            })
            ->when($request->end_date_1, function($query, $end_date){
                return $query->whereHas('quota.contract', function($query) use($end_date){
                    return $query->whereDate('date', '<=', $end_date);
                });
            })
            ->whereYear('date', date('Y'))->groupBy('month')
            ->orderBy('month', 'asc')->get();

        $sales_months_2 = Payment::active()->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->when($user->hasRole('seller'), function($query){
                return $query->whereHas('quota.contract', function($query){
                    return $query->where('seller_id', auth()->user()->id);
                });
            })
            ->when($request->seller_id_2, function($query, $seller_id){
                return $query->whereHas('quota.contract', function($query) use($seller_id){
                    return $query->where('seller_id', $seller_id);
                });
            })
            ->when($request->start_date_2, function($query, $start_date){
                return $query->whereHas('quota.contract', function($query) use($start_date){
                    return $query->whereDate('date', '>=', $start_date);
                });
            })
            ->when($request->end_date_2, function($query, $end_date){
                return $query->whereHas('quota.contract', function($query) use($end_date){
                    return $query->whereDate('date', '<=', $end_date);
                });
            })
            ->whereYear('date', date('Y'))
            ->groupBy('month')->orderBy('month', 'asc')->get();
        
        $sales_totals_1 = [0,0,0,0,0,0,0,0,0,0,0,0];

        $sales_totals_2 = [0,0,0,0,0,0,0,0,0,0,0,0];

        foreach($sales_months_1 as $sale){
            $sales_totals_1[$sale->month - 1] = $sale->total;
        }

        foreach($sales_months_2 as $sale){
            $sales_totals_2[$sale->month - 1] = $sale->total;
        }

        // Cargar expenses y agrupar por mes en PHP usando el accessor amount
        $expenses = Expense::active()
            ->when($request->start_date_1, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date_1, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })
            ->whereYear('date', date('Y'))
            ->with('expensePayments')
            ->get();

        $expenses_months_1 = $expenses->groupBy(function($item){
            return intval(date('n', strtotime($item->date)));
        })->map(function($group, $month){
            return (object)[
                'month' => intval($month),
                'total' => $group->sum('amount')
            ];
        })->values();

        // Versión filtrada por seller / filtros 2, agrupada en PHP usando el accessor amount
        $expenses2 = Expense::active()
            ->when($request->start_date_2, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date_2, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })
            ->when($user->hasRole('seller'), function($query){
                return $query->where('seller_id', auth()->user()->id);
            })
            ->when($request->seller_id_2, function($query, $seller_id){
                return $query->where('seller_id', $seller_id);
            })
            ->whereYear('date', date('Y'))
            ->with('expensePayments')
            ->get();

        $expenses_months_2 = $expenses2->groupBy(function($item){
            return intval(date('n', strtotime($item->date)));
        })->map(function($group, $month){
            return (object)[
                'month' => intval($month),
                'total' => $group->sum('amount')
            ];
        })->values();
        
        $expenses_totals_1 = [0,0,0,0,0,0,0,0,0,0,0,0];

        $expenses_totals_2 = [0,0,0,0,0,0,0,0,0,0,0,0];

        foreach($expenses_months_1 as $expense){
            $expenses_totals_1[$expense->month - 1] = $expense->total;
        }

        foreach($expenses_months_2 as $expense){
            $expenses_totals_2[$expense->month - 1] = $expense->total;
        }

        $sellers = User::seller()->active()->get();

        $due_quotas = Quota::when($user->hasRole('seller'), function($query){
            return $query->whereHas('contract', function($query){
                return $query->where('seller_id', auth()->user()->id);
            });
        })->when($request->seller_id_2, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date_2, function($query, $start_date){
            return $query->whereHas('contract', function($query) use($start_date){
                return $query->whereDate('date', '>=', $start_date);
            });
        })->when($request->end_date_2, function($query, $end_date){
            return $query->whereHas('contract', function($query) use($end_date){
                return $query->whereDate('date', '<=', $end_date);
            });
        })->whereHas('contract', function($query){
            return $query->where('deleted', 0);
        })->where('paid', 0)
        ->count();

        return view('index', compact(
                'today_payments', 'today_projected', 'today_real', 'active_clients', 'due_clients', 'home_sales_1', 'sales_1', 'sales_2', 'sales_3', 'sales_4', 'sales_5', 'sales_6', 'total', 'due_total', 'wallet_total', 'requested_amount', 'expenses', 'sales_totals_1', 'expenses_totals_1', 'sales_totals_2', 'expenses_totals_2', 'sellers','seller_wallet','today_timely_payments','due_quotas',));
    }

    public function apiReniec(Request $request){
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('apireniec.token')
        ])->get(config('apireniec.url'), [
            'numero' => $request->dni
        ]);

        $data = $response->json();

        if($response->successful()){

            return response()->json([
                'status' => true,
                'name' => $data['nombres'].' '.$data['apellidoPaterno'].' '.$data['apellidoMaterno']
            ]);

        }else{

            return response()->json([
                'status' => false
            ]);

        }
    }

    public function apiProvinces(Request $request){
        $department_id = $request->department_id;
        
        $provinces = Province::where('department_id', $department_id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($provinces);
    }

    public function apiDistricts(Request $request){
        $province_id = $request->province_id;
        
        $districts = District::where('province_id', $province_id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($districts);
    }
}
