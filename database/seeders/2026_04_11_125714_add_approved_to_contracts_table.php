<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovedToContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'approved')) {
                $table->integer('approved')->default(1);
            }
            if (!Schema::hasColumn('contracts', 'type_quota')) {
                $table->integer('type_quota')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'approved')) {
                $table->dropColumn('approved');
            }
            if (Schema::hasColumn('contracts', 'type_quota')) {
                $table->dropColumn('type_quota');
            }
        });
    }
}
