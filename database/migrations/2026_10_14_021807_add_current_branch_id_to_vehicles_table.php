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
            $table->foreignId('current_branch_id')->nullable()->constrained('branches')->onDelete('restrict')->comment('車輛目前所在地據點ID');
            $table->index(['current_branch_id', 'status'])->comment('據點+狀態複合索引，用於快速查詢某據點的可用車輛');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['current_branch_id']);
            $table->dropColumn('current_branch_id');
        });
    }
};
