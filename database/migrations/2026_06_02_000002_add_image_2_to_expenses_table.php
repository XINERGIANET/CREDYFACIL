<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImage2ToExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('image_2')->nullable()->after('image');
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('image_2');
        });
    }
}
