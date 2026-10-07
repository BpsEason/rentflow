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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id()->comment('車輛ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict')->comment('租戶ID');
            $table->string('name')->comment('車輛名稱');
            $table->string('plate_number')->comment('車牌號碼');
            $table->string('status')->comment('車輛狀態：AVAILABLE=可租用, MAINTENANCE=維修中, INACTIVE=停用');
            $table->integer('seats')->comment('座位數');
            $table->text('description')->nullable()->comment('車輛描述');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status'])->comment('租戶+狀態複合索引，用於快速過濾同一租戶下不同狀態的車輛');
            $table->unique(['tenant_id', 'plate_number'])->comment('同一租戶內車牌號碼不可重複');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};