<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Contract;
use App\Models\User;
use App\Models\Transfer;
use App\Models\Department;
use App\Models\Province;
use App\Models\District;
use App\Models\Goal;
use App\Models\AccountMovement;
use App\Models\PaymentMethod;
use App\Exports\PortfolioDailyReportExport;
use App\Exports\PortfolioDailyClientsExport;
use App\Services\ClientPortfolioService;
use Maatwebsite\Excel\Facades\Excel;

class WebController extends Controller
{
    public function portfolioDailyExcel(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();
        
        $goalsExist = Goal::where('month', $date->month)
            ->where('year', $date->year)
            ->exists();

        if (!$goalsExist) {
            return redirect()->route('goals.index', ['month' => $date->month, 'year' => $date->year])
                ->with('message', 'Debe registrar las metas de este mes antes de generar el reporte.');
        }

        $name = 'Reporte_Cartera_Credyfacil_' . $date->format('d_m_Y') . '.xlsx';

        return Excel::download(new PortfolioDailyReportExport($date->format('Y-m-d')), $name);
    }

    public function portfolioDailyClientsExcel(Request $request, ClientPortfolioService $portfolioService)
    {
        $sellerId = $request->seller_id ?: null;
        $date = $request->date ? Carbon::parse($request->date) : today();
        $metric = $request->metric ?: 'current_clients';
        $contracts = $this->contractsForPortfolioMetric($metric, $sellerId, $date, $portfolioService);
        $sellerLabel = $sellerId ? optional(User::find($sellerId))->name : 'Todos';
        $name = 'Clientes_Cartera_' . preg_replace('/[^A-Za-z0-9_]/', '_', $sellerLabel) . '_' . $date->format('d_m_Y') . '.xlsx';

        return Excel::download(
            new PortfolioDailyClientsExport($contracts, $date->format('Y-m-d')),
            $name
        );
    }

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

        $accountBalances = $this->accountBalances();

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

        $portfolioReportDate = $request->portfolio_date ?? now()->format('Y-m-d');
        $portfolioReport = (new \App\Exports\PortfolioDailyReportExport($portfolioReportDate))->data();
        $portfolioOverdueReport = $this->portfolioOverdueReport($portfolioReportDate);

        return view('index', compact(
                'today_payments', 'today_projected', 'today_real', 'active_clients', 'due_clients', 'home_sales_1', 'sales_1', 'sales_2', 'sales_3', 'sales_4', 'sales_5', 'sales_6', 'total', 'due_total', 'wallet_total', 'requested_amount', 'expenses', 'sales_totals_1', 'expenses_totals_1', 'sales_totals_2', 'expenses_totals_2', 'sellers','seller_wallet','today_timely_payments','due_quotas','portfolioReport','portfolioOverdueReport','accountBalances'));
    }

    public function accountBalances()
    {
        return PaymentMethod::active()
            ->get()
            ->map(function ($paymentMethod) {
                $payments = (float) Payment::active()
                    ->where('payment_method_id', $paymentMethod->id)
                    ->sum('amount');

                $expenses = (float) Expense::active()
                    ->whereHas('expensePayments', function ($query) use ($paymentMethod) {
                        return $query->where('payment_method_id', $paymentMethod->id);
                    })
                    ->with('expensePayments')
                    ->get()
                    ->sum(function ($expense) use ($paymentMethod) {
                        return $expense->expensePayments
                            ->where('payment_method_id', $paymentMethod->id)
                            ->sum('amount');
                    });

                $transfersOut = (float) Transfer::active()
                    ->where('type', 'payment_method')
                    ->where('from_payment_method_id', $paymentMethod->id)
                    ->sum('amount');

                $transfersIn = (float) Transfer::active()
                    ->where('type', 'payment_method')
                    ->where('to_payment_method_id', $paymentMethod->id)
                    ->sum('amount');

                $manualIn = (float) AccountMovement::active()
                    ->where('payment_method_id', $paymentMethod->id)
                    ->where('type', 'income')
                    ->sum('amount');

                $manualOut = (float) AccountMovement::active()
                    ->where('payment_method_id', $paymentMethod->id)
                    ->where('type', 'expense')
                    ->sum('amount');

                return [
                    'id' => $paymentMethod->id,
                    'name' => $paymentMethod->name,
                    'balance' => $payments - $expenses - $transfersOut + $transfersIn + $manualIn - $manualOut,
                    'payments' => $payments,
                    'expenses' => $expenses,
                    'transfers_in' => $transfersIn,
                    'transfers_out' => $transfersOut,
                    'manual_in' => $manualIn,
                    'manual_out' => $manualOut,
                ];
            });
    }

    public function apiReniec(Request $request){
        $response = Http::get('https://api.perudevs.com/api/v1/dni/simple', [
            'document' => $request->dni,
            'key' => env('APIRENIEC_KEY'),
        ]);

        $data = $response->json();

        if($response->successful() && isset($data['estado']) && $data['estado'] === true && !empty($data['resultado']['nombre_completo'])){

            return response()->json([
                'status' => true,
                'name' => $data['resultado']['nombre_completo']
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
    public function reportClients(Request $request)
    {
        $sellerId = $request->seller_id;
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();
        $metric = $request->metric ?: 'current_clients';
        $monthStart = $date->copy()->startOfMonth();
        $initialDate = $monthStart->copy()->subDay();
        $previousStart = $date->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $date->copy()->subMonthNoOverflow()->endOfMonth();

        switch ($metric) {
            case 'initial_clients':
                return response()->json($this->activeContractsDetail(
                    'INIC. MES N° CLIENTES',
                    $this->activeContractsAsOf($sellerId, $initialDate),
                    'Clientes activos al ' . $initialDate->format('d/m/Y')
                        . ': contratos creados hasta esa fecha que aun tenian deuda o pagos posteriores al corte.',
                    $initialDate
                ));
            case 'current_clients':
                return response()->json($this->activeContractsDetail(
                    'AVANCE N° CLIENT. AL DIA',
                    $this->activeContractsAsOf($sellerId, $date),
                    'Clientes activos al ' . $date->format('d/m/Y')
                        . ': contratos creados hasta esa fecha que aun tenian deuda o pagos posteriores al corte.',
                    $date
                ));
            case 'client_growth':
                return response()->json($this->summaryDetail('CRECIMIENTO N° CLIENTES', [
                    ['Clientes al ' . $date->format('d/m/Y'), $this->activeContractsAsOf($sellerId, $date)->count()],
                    ['Clientes al inicio de mes', $this->activeContractsAsOf($sellerId, $initialDate)->count()],
                    ['Crecimiento', $this->activeContractsAsOf($sellerId, $date)->count() - $this->activeContractsAsOf($sellerId, $initialDate)->count()],
                ]));
            case 'new_clients':
                return response()->json($this->contractsDetail(
                    'NUEVOS',
                    $this->newClientContracts($sellerId, $monthStart, $date),
                    'Clientes nuevos desde ' . $monthStart->format('d/m/Y') . ' hasta ' . $date->format('d/m/Y')
                ));
            case 'client_goal':
                return response()->json($this->goalDetail(
                    'META DE CLIENTES', $sellerId, $date, 'clients'
                ));

            case 'client_percent':
                $initialDate = $date->copy()->startOfMonth()->subDay();
                $currentClients = $this->activeContractsAsOf($sellerId, $date)->count();
                $initialClients = $this->activeContractsAsOf($sellerId, $initialDate)->count();
                $clientGrowth = $currentClients - $initialClients;
                $clientGoal = $this->goalValue($sellerId, $date, 'clients');
                return response()->json($this->summaryDetail('AVANCE CLIENTES %', [
                    ['Clientes al día', $currentClients],
                    ['Clientes inicio de mes', $initialClients],
                    ['Crecimiento', $clientGrowth],
                    ['Meta de clientes', $clientGoal],
                    ['Resultado', $clientGoal > 0
                        ? number_format(($clientGrowth / $clientGoal) * 100, 2) . '%'
                        : '-'],
                ]));

            case 'new_goal':
                return response()->json($this->goalDetail('META DE NUEVOS', $sellerId, $date, 'new_clients'));
            case 'new_percent':
                $newClients = $this->newClientContracts($sellerId, $monthStart, $date)->count();
                $newGoal = $this->goalValue($sellerId, $date, 'new_clients');
                return response()->json($this->summaryDetail('AVANCE NUEVOS %', [
                    ['Clientes nuevos', $newClients],
                    ['Meta de nuevos', $newGoal],
                    ['Resultado', $newGoal > 0 ? number_format(($newClients / $newGoal) * 100, 2) . '%' : '-'],
                ]));
            case 'initial_wallet':
                return response()->json($this->walletDetail('INIC. MES CARTERA', $sellerId, $initialDate));
            case 'current_wallet':
                return response()->json($this->walletDetail('AVANCE CARTERA', $sellerId, $date));
            case 'wallet_growth':
                $currentWallet = $this->walletValue($sellerId, $date);
                $initialWallet = $this->walletValue($sellerId, $initialDate);
                return response()->json($this->summaryDetail('CREC. CARTERA', [
                    ['Cartera al ' . $date->format('d/m/Y'), 'S/ ' . number_format($currentWallet, 1)],
                    ['Cartera al inicio de mes', 'S/ ' . number_format($initialWallet, 1)],
                    ['Crecimiento cartera', 'S/ ' . number_format($currentWallet - $initialWallet, 1)],
                ]));
            case 'overdue_percent':
                $wallet = $this->walletValue($sellerId, $date);
                $overdue = $this->quotaDebtValue($sellerId, $date, 7);
                return response()->json($this->summaryDetail('MORA >7', [
                    ['Deuda vencida mayor a 7 dias', 'S/ ' . number_format($overdue, 1)],
                    ['Avance cartera', 'S/ ' . number_format($wallet, 1)],
                    ['Resultado', $wallet > 0 ? number_format(($overdue / $wallet) * 100, 2) . '%' : '-'],
                ]));
            case 'mora_1_7':
                return response()->json($this->overdueQuotasDetail('MORA 1 a 7', $sellerId, $date, 1, 7));
            case 'mora_8_30':
                return response()->json($this->overdueQuotasDetail('MORA 8 a 30', $sellerId, $date, 8, 30));
            case 'mora_gt_7':
                return response()->json($this->overdueQuotasDetail('MORA >7', $sellerId, $date, 8));
            case 'mora_gt_60':
                return response()->json($this->overdueQuotasDetail('MORA >60', $sellerId, $date, 61));
            case 'mora_total':
                return response()->json($this->overdueQuotasDetail('MORA TOTAL', $sellerId, $date, 1));
            case 'previous_disbursement':
                return response()->json($this->contractsDetail(
                    'DESEMBOLSO MES PASADO',
                    $this->contractsBetween($sellerId, $previousStart, $previousEnd),
                    'Contratos desde ' . $previousStart->format('d/m/Y') . ' hasta ' . $previousEnd->format('d/m/Y')
                ));
            case 'previous_operations':
                return response()->json($this->contractsDetail(
                    'N° OPER. MES PASADO',
                    $this->contractsBetween($sellerId, $previousStart, $previousEnd),
                    'Operaciones desde ' . $previousStart->format('d/m/Y') . ' hasta ' . $previousEnd->format('d/m/Y')
                ));
            case 'current_disbursement':
                return response()->json($this->contractsDetail(
                    'AVANCE DESEMB.',
                    $this->contractsBetween($sellerId, $monthStart, $date),
                    'Contratos desde ' . $monthStart->format('d/m/Y') . ' hasta ' . $date->format('d/m/Y')
                ));
            case 'current_operations':
                return response()->json($this->contractsDetail(
                    'N° DE OPER.',
                    $this->contractsBetween($sellerId, $monthStart, $date),
                    'Operaciones desde ' . $monthStart->format('d/m/Y') . ' hasta ' . $date->format('d/m/Y')
                ));
            case 'disbursement_goal':
                return response()->json($this->goalDetail('META MES', $sellerId, $date, 'disbursement', true));
            case 'disbursement_percent':
                $disbursement = $this->contractsBetween($sellerId, $monthStart, $date)->sum('requested_amount');
                $goal = $this->goalValue($sellerId, $date, 'disbursement');
                return response()->json($this->summaryDetail('AVANCE DESEMBOLSOS %', [
                    ['Avance desembolsos', 'S/ ' . number_format($disbursement, 0)],
                    ['Meta mes', 'S/ ' . number_format($goal, 0)],
                    ['Resultado', $goal > 0 ? number_format(($disbursement / $goal) * 100, 2) . '%' : '-'],
                ]));
            default:
                return response()->json($this->contractsDetail(
                    'Detalle',
                    $this->activeContractsAsOf($sellerId, $date),
                    'Clientes activos al ' . $date->format('d/m/Y')
                ));
        }
    }

    private function activeContractsAsOf($sellerId, Carbon $date)
    {
        return app(ClientPortfolioService::class)->activeContractsAsOf($sellerId, $date);
    }

    private function newClientContracts($sellerId, Carbon $start, Carbon $end)
    {
        return app(ClientPortfolioService::class)->newClientContracts($sellerId, $start, $end);
    }

    private function contractsForPortfolioMetric(string $metric, $sellerId, Carbon $date, ClientPortfolioService $service)
    {
        $monthStart = $date->copy()->startOfMonth();
        $initialDate = $monthStart->copy()->subDay();
        $previousStart = $date->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $date->copy()->subMonthNoOverflow()->endOfMonth();

        switch ($metric) {
            case 'initial_clients':
                return $service->activeContractsAsOf($sellerId, $initialDate);
            case 'new_clients':
                return $service->newClientContracts($sellerId, $monthStart, $date);
            case 'previous_disbursement':
            case 'previous_operations':
                return $this->contractsBetween($sellerId, $previousStart, $previousEnd);
            case 'current_disbursement':
            case 'current_operations':
                return $this->contractsBetween($sellerId, $monthStart, $date);
            case 'current_clients':
            default:
                return $service->activeContractsAsOf($sellerId, $date);
        }
    }

    private function contractsBetween($sellerId, Carbon $start, Carbon $end)
    {
        return Contract::active()
            ->where('approved', 1)
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->with('seller')
            ->orderBy('date')
            ->get();
    }

    private function contractsDetail(string $title, $contracts, string $subtitle): array
    {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => ['Cliente', 'Documento/Grupo', 'Monto', 'Fecha', 'Asesor'],
            'rows' => $contracts->map(function ($contract) {
                return [
                    $contract->client(),
                    $contract->document ?? $contract->group_name,
                    'S/ ' . number_format($contract->requested_amount, 2),
                    optional($contract->date)->format('d/m/Y'),
                    $contract->seller ? $contract->seller->name : 'N/A',
                ];
            })->values(),
        ];
    }

    private function activeContractsDetail(string $title, $contracts, string $subtitle, Carbon $date): array
    {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => ['Cliente', 'Documento/Grupo', 'Monto contrato', 'Deuda al corte', 'Fecha contrato', 'Asesor', 'Motivo'],
            'rows' => $contracts->map(function ($contract) use ($date) {
                return [
                    $contract->client(),
                    $contract->document ?? $contract->group_name,
                    'S/ ' . number_format($contract->requested_amount, 2),
                    'S/ ' . number_format($this->contractDebtAsOf($contract->id, $date), 2),
                    optional($contract->date)->format('d/m/Y'),
                    $contract->seller ? $contract->seller->name : 'N/A',
                    $this->contractActiveReason($contract->id, $date),
                ];
            })->values(),
        ];
    }

    private function contractDebtAsOf($contractId, Carbon $date): float
    {
        return app(ClientPortfolioService::class)->contractDebtAsOf($contractId, $date);
    }

    private function contractActiveReason($contractId, Carbon $date): string
    {
        $hasDebt = Quota::where('contract_id', $contractId)
            ->where('debt', '>', 0)
            ->exists();

        $hasFuturePayments = Payment::active()
            ->whereDate('payments.date', '>', $date)
            ->whereHas('quota', function ($query) use ($contractId) {
                return $query->where('contract_id', $contractId);
            })
            ->exists();

        if ($hasDebt && $hasFuturePayments) {
            return 'Deuda pendiente y pagos posteriores al corte';
        }

        if ($hasDebt) {
            return 'Deuda pendiente al corte';
        }

        if ($hasFuturePayments) {
            return 'Pago registrado despues del corte';
        }

        return 'Activo al corte';
    }

    private function summaryDetail(string $title, array $rows): array
    {
        return [
            'title' => $title,
            'subtitle' => 'Detalle del calculo mostrado en la celda',
            'headers' => ['Concepto', 'Valor'],
            'rows' => $rows,
        ];
    }

    private function goalDetail(string $title, $sellerId, Carbon $date, string $field, bool $money = false): array
    {
        $goals = Goal::where('month', $date->month)
            ->where('year', $date->year)
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->with('seller')
            ->get();

        return [
            'title' => $title,
            'subtitle' => 'Metas registradas para ' . $date->format('m/Y'),
            'headers' => ['Asesor', 'Meta'],
            'rows' => $goals->map(function ($goal) use ($field, $money) {
                $value = $goal->{$field};
                return [
                    $goal->seller ? $goal->seller->name : 'N/A',
                    $money ? 'S/ ' . number_format($value, 0) : number_format($value, 0),
                ];
            })->values(),
        ];
    }

    private function goalValue($sellerId, Carbon $date, string $field)
    {
        return (float) Goal::where('month', $date->month)
            ->where('year', $date->year)
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->sum($field);
    }

    private function walletDetail(string $title, $sellerId, Carbon $date): array
    {
        $currentDebt = $this->quotaDebtValue($sellerId, $date);
        $futurePayments = $this->futurePaymentValue($sellerId, $date);

        return $this->summaryDetail($title, [
            ['Deuda actual de cuotas activas al ' . $date->format('d/m/Y'), 'S/ ' . number_format($currentDebt, 1)],
            ['Pagos posteriores a esa fecha', 'S/ ' . number_format($futurePayments, 1)],
            ['Total cartera', 'S/ ' . number_format($currentDebt + $futurePayments, 1)],
        ]);
    }

    private function overdueQuotasDetail(string $title, $sellerId, Carbon $date, int $fromDays, ?int $toDays = null): array
    {
        $quotas = Quota::active()
            ->whereHas('contract', function ($query) use ($sellerId, $date) {
                $query->where('approved', 1)
                    ->when($sellerId, function ($q, $sellerId) {
                        return $q->where('seller_id', $sellerId);
                    })
                    ->whereDate('date', '<=', $date);
            })
            ->where('paid', 0)
            ->whereDate('date', '<', $date)
            ->whereDate('date', '<=', $date->copy()->subDays($fromDays))
            ->when($toDays, function ($query, $toDays) use ($date) {
                return $query->whereDate('date', '>=', $date->copy()->subDays($toDays));
            })
            ->with('contract.seller')
            ->orderBy('date')
            ->get();

        return [
            'title' => $title,
            'subtitle' => 'Cuotas impagas vencidas al ' . $date->format('d/m/Y')
                . '. Total: S/ ' . number_format($quotas->sum('debt'), 2),
            'headers' => ['Cliente', 'Asesor', 'Numero de cuota', 'Monto', 'Saldo', 'Fecha de pago', 'Dias de mora'],
            'rows' => $quotas->map(function ($quota) use ($date) {
                return [
                    optional($quota->contract)->client(),
                    optional(optional($quota->contract)->seller)->name,
                    $quota->number,
                    'S/ ' . number_format($quota->amount, 2),
                    'S/ ' . number_format($quota->debt, 2),
                    optional($quota->date)->format('d/m/Y'),
                    optional($quota->date)->diffInDays($date),
                ];
            })->values(),
        ];
    }

    private function walletValue($sellerId, Carbon $date, ?int $overdueDays = null): float
    {
        if ($overdueDays !== null) {
            return $this->quotaDebtValue($sellerId, $date, $overdueDays);
        }

        return $this->quotaDebtValue($sellerId, $date, $overdueDays) + $this->futurePaymentValue($sellerId, $date, $overdueDays);
    }

    private function portfolioOverdueReport($date): array
    {
        $date = Carbon::parse($date)->startOfDay();
        $rows = [];
        $totals = [
            'wallet' => 0.0,
            'mora_1_7' => 0.0,
            'mora_8_30' => 0.0,
            'mora_gt_7' => 0.0,
            'mora_gt_60' => 0.0,
            'mora_total' => 0.0,
        ];

        $sellers = User::seller()
            ->active()
            ->where('state', 0)
            ->orderBy('name')
            ->get();

        foreach ($sellers as $seller) {
            $wallet = $this->walletValue($seller->id, $date);
            $mora1To7 = $this->overdueRangeValue($seller->id, $date, 1, 7);
            $mora8To30 = $this->overdueRangeValue($seller->id, $date, 8, 30);
            $moraGt7 = $this->walletValue($seller->id, $date, 7);
            $moraGt60 = $this->walletValue($seller->id, $date, 60);
            $moraTotal = $mora1To7 + $moraGt7;

            $row = [
                'seller_id' => $seller->id,
                'seller' => $this->shortSellerName($seller->name),
                'wallet' => $wallet,
                'mora_1_7' => $mora1To7,
                'mora_1_7_percent' => $this->ratio($mora1To7, $wallet),
                'mora_8_30' => $mora8To30,
                'mora_8_30_percent' => $this->ratio($mora8To30, $wallet),
                'mora_gt_7' => $moraGt7,
                'mora_gt_7_percent' => $this->ratio($moraGt7, $wallet),
                'mora_gt_60' => $moraGt60,
                'mora_gt_60_percent' => $this->ratio($moraGt60, $wallet),
                'mora_total' => $moraTotal,
                'mora_total_percent' => $this->ratio($moraTotal, $wallet),
            ];

            $rows[] = $row;

            foreach ($totals as $key => $value) {
                $totals[$key] += $row[$key];
            }
        }

        $totals['mora_1_7_percent'] = $this->ratio($totals['mora_1_7'], $totals['wallet']);
        $totals['mora_8_30_percent'] = $this->ratio($totals['mora_8_30'], $totals['wallet']);
        $totals['mora_gt_7_percent'] = $this->ratio($totals['mora_gt_7'], $totals['wallet']);
        $totals['mora_gt_60_percent'] = $this->ratio($totals['mora_gt_60'], $totals['wallet']);
        $totals['mora_total_percent'] = $this->ratio($totals['mora_total'], $totals['wallet']);

        return [
            'date' => $date,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function overdueRangeValue($sellerId, Carbon $date, int $fromDays, int $toDays): float
    {
        $fromDate = $date->copy()->subDays($toDays);
        $toDate = $date->copy()->subDays($fromDays);

        return $this->quotaDebtRangeValue($sellerId, $date, $fromDate, $toDate);
    }

    private function quotaDebtRangeValue($sellerId, Carbon $date, Carbon $fromDate, Carbon $toDate): float
    {
        return (float) Quota::whereHas('contract', function ($query) use ($sellerId, $date) {
                $query->active()
                    ->where('approved', 1)
                    ->when($sellerId, function ($q, $sellerId) {
                        return $q->where('seller_id', $sellerId);
                    })
                    ->whereDate('date', '<=', $date);
            })
            ->where('paid', 0)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->sum('debt');
    }

    private function futurePaymentRangeValue($sellerId, Carbon $date, Carbon $fromDate, Carbon $toDate): float
    {
        return (float) Payment::active()
            ->whereDate('payments.date', '>', $date)
            ->whereHas('quota.contract', function ($query) use ($sellerId, $date) {
                $query->active()
                    ->where('approved', 1)
                    ->when($sellerId, function ($q, $sellerId) {
                        return $q->where('seller_id', $sellerId);
                    })
                    ->whereDate('date', '<=', $date);
            })
            ->whereHas('quota', function ($q) use ($fromDate, $toDate) {
                $q->whereDate('date', '>=', $fromDate)
                    ->whereDate('date', '<=', $toDate);
            })
            ->sum('amount');
    }

    private function ratio($value, $total): float
    {
        return $total > 0 ? $value / $total : 0;
    }

    private function shortSellerName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));

        return strtoupper($parts[0] ?? $name);
    }

    private function quotaDebtValue($sellerId, Carbon $date, ?int $overdueDays = null): float
    {
        return (float) Quota::whereHas('contract', function ($query) use ($sellerId, $date) {
                $query->active()
                    ->where('approved', 1)
                    ->when($sellerId, function ($q, $sellerId) {
                        return $q->where('seller_id', $sellerId);
                    })
                    ->whereDate('date', '<=', $date);
            })
            ->where('paid', 0)    
            ->when($overdueDays, function ($query, $days) use ($date) {
                return $query->whereDate('date', '<', $date->copy()->subDays($days));
            })
            ->sum('debt');
    }

    private function futurePaymentValue($sellerId, Carbon $date, ?int $overdueDays = null): float
    {
        return (float) Payment::active()
            ->whereDate('payments.date', '>', $date)
            ->whereHas('quota.contract', function ($query) use ($sellerId, $date) {
                $query->active()
                    ->where('approved', 1)
                    ->when($sellerId, function ($q, $sellerId) {
                        return $q->where('seller_id', $sellerId);
                    })
                    ->whereDate('date', '<=', $date);
            })
            ->when($overdueDays, function ($query, $days) use ($date) {
                return $query->whereHas('quota', function ($q) use ($date, $days) {
                    $q->whereDate('date', '<', $date->copy()->subDays($days));
                });
            })
            ->sum('amount');
    }
}
