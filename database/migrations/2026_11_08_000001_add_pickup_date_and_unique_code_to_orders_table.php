<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('pickup_date')->nullable()->after('pickup_code');
        });

        DB::table('orders')->update(['pickup_date' => DB::raw('DATE(created_at)')]);

        Schema::table('orders', function (Blueprint $table) {
            $table->date('pickup_date')->nullable(false)->change();
            $table->unique(['stall_id', 'pickup_date', 'pickup_code']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['stall_id', 'pickup_date', 'pickup_code']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_date');
        });
    }
};
