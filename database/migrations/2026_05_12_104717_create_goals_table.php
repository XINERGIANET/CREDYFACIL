<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('seller_id');
            $table->integer('month');
            $table->integer('year');
            $table->integer('clients')->default(0);
            $table->integer('new_clients')->default(0);
            $table->decimal('disbursement', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('users');
            $table->unique(['seller_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('goals');
    }
}
