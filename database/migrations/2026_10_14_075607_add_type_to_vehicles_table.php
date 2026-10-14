<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // 車輛類型：CAR=汽車, MOTORCYCLE=機車
            $table->string('type')->default('CAR')->comment('車輛類型：CAR=汽車, MOTORCYCLE=機車');

            // 機車專用欄位
            $table->integer('engine_cc')->nullable()->comment('排氣量(cc)，僅機車需要');
            $table->integer('helmet_count')->nullable()->comment('提供安全帽數量，僅機車需要');

            // 新增索引
            $table->index(['tenant_id', 'type', 'status'])->comment('租戶+類型+狀態複合索引，用於快速過濾不同車種');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'type', 'status']);
            $table->dropColumn(['type', 'engine_cc', 'helmet_count']);
        });
    }
};
