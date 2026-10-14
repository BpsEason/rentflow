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
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->onDelete('restrict')->comment('取車據點ID');
            $table->foreignId('return_branch_id')->nullable()->constrained('branches')->onDelete('restrict')->comment('還車據點ID');
            $table->index(['pickup_branch_id', 'return_branch_id', 'start_at'])->comment('取還車據點+時間複合索引');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['pickup_branch_id']);
            $table->dropForeign(['return_branch_id']);
            $table->dropColumn(['pickup_branch_id', 'return_branch_id']);
        });
    }
};
