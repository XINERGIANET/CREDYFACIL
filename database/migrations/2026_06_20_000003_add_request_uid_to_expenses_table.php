<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestUidToExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('request_uid', 64)->nullable()->after('contract_id');
            $table->unique('request_uid', 'expenses_request_uid_unique');
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_request_uid_unique');
            $table->dropColumn('request_uid');
        });
    }
}
