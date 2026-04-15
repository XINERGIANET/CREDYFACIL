<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\WebController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\QuotaController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\SettingController;


Route::get('optimize', function () {
	Artisan::call('optimize:clear');
});


Route::get('login', [AuthController::class, 'login'])->name('auth.login');
Route::post('login', [AuthController::class, 'check'])->name('auth.check');
Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');


Route::middleware('auth')->group(function () {

	Route::get('/', [WebController::class, 'index']);

	Route::get('api/reniec', [WebController::class, 'apiReniec'])->name('api.reniec');
	Route::get('api/provinces', [WebController::class, 'apiProvinces'])->name('api.provinces');
	Route::get('api/districts', [WebController::class, 'apiDistricts'])->name('api.districts');

	Route::get('clients/quotas', [ClientController::class, 'quotas'])->name('clients.quotas');
	Route::get('clients/contracts', [ClientController::class, 'contracts'])->name('clients.contracts');
	Route::get('clients/details', [ClientController::class, 'details'])->name('clients.details');
	Route::get('clients/check', [ClientController::class, 'check'])->name('clients.check');
	Route::get('clients/api', [ClientController::class, 'api'])->name('clients.api');
	Route::get('clients', [ClientController::class, 'index'])->name('clients.index');

	Route::get('contracts/api', [ContractController::class, 'api'])->name('contracts.api');
	Route::put('contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
	Route::get('contracts/ending', [ContractController::class, 'ending'])->name('contracts.ending');
	Route::get('contracts/ending/excel', [ContractController::class, 'endingExcel'])->name('contracts.ending.excel');
	Route::get('contracts/sentinel/excel', [ContractController::class, 'sentinelExcel'])->name('contracts.sentinel.excel');
	Route::get('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
	Route::get('contracts/{contract}/pdfPersonal', [ContractController::class, 'pdfPersonal'])->name('contracts.pdfPersonal');
	Route::get('contracts/excel', [ContractController::class, 'excel'])->name('contracts.excel');
	Route::resource('contracts', ContractController::class);

	Route::get('quotas/api', [QuotaController::class, 'api'])->name('quotas.api');

	Route::get('payments/charges', [PaymentController::class, 'charges'])->name('payments.charges');
	Route::get('contracts/charges/excel', [PaymentController::class, 'chargesExcel'])->name('payments.charges.excel');
	Route::get('payments/dues/excel', [PaymentController::class, 'duesExcel'])->name('payments.dues.excel');
	Route::get('payments/dues', [PaymentController::class, 'dues'])->name('payments.dues');
	Route::get('payments/excel', [PaymentController::class, 'excel'])->name('payments.excel');
	Route::get('payments/{payment}/image', [PaymentController::class, 'image'])->name('payments.image');
	Route::resource('payments', PaymentController::class);

	Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
	Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

	Route::get('expenses/excel', [ExpenseController::class, 'excel'])->name('expenses.excel');
	Route::get('expenses/index_cash', [ExpenseController::class, 'index_cash'])->name('expenses.index_cash');
	Route::get('expenses/excel_cash', [ExpenseController::class, 'excel_cash'])->name('expenses.excel_cash');
	Route::resource('expenses', ExpenseController::class);
});

Route::middleware('role:admin')->group(function () {
	Route::put('sellers/drop/{id}', [SellerController::class, 'drop'])->name('sellers.drop');
	Route::put('sellers/up/{id}', [SellerController::class, 'up'])->name('sellers.up');

	Route::get('sellers/{seller}/contracts/excel', [SellerController::class, 'contractsExcel'])->name('sellers.contracts.excel');
	Route::get('sellers/{seller}/overdue-contracts/excel', [SellerController::class, 'overdueContractsExcel'])->name('sellers.overdue-contracts.excel');
	Route::get('sellers/{seller}/contracts', [SellerController::class, 'contracts'])->name('sellers.contracts');
	Route::get('sellers/{seller}/overdue-contracts', [SellerController::class, 'overdueContracts'])->name('sellers.overdue-contracts');
	Route::resource('sellers', SellerController::class);

	Route::resource('transfers', TransferController::class);

	Route::get('interests/monthly', [InterestController::class, 'index'])->name('interests.monthly');

	Route::post('config/insurance', [ConfigController::class, 'insurance'])->name('config.insurance');
});
