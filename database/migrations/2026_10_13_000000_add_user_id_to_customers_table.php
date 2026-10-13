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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->comment('關聯的平台會員ID');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');

            // 同一用戶在同一租戶下只能有一個Customer記錄
            $table->unique(['tenant_id', 'user_id'])->comment('同一租戶內同一會員只能有一個客戶資料');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['tenant_id', 'user_id']);
            $table->dropColumn('user_id');
        });
    }
};
