<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountMovementsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('account_movements');

        Schema::create('account_movements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('payment_method_id');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->date('date');
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_movements');
    }
}
