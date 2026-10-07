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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id()->comment('預約ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict')->comment('租戶ID');
            $table->foreignId('customer_id')->constrained()->onDelete('restrict')->comment('客戶ID');
            $table->foreignId('vehicle_id')->constrained()->onDelete('restrict')->comment('車輛ID');
            $table->dateTime('start_at')->comment('預約開始時間');
            $table->dateTime('end_at')->comment('預約結束時間');
            $table->string('status')->comment('預約狀態：PENDING=待確認, CONFIRMED=已確認, PICKED_UP=已取車, RETURNED=已歸還, CANCELLED=已取消');
            $table->decimal('amount', 12, 2)->comment('預約總金額');
            $table->integer('rental_days')->comment('租用天數');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'vehicle_id', 'start_at', 'end_at'])->comment('用於快速查詢同一車輛的時間衝突');
            $table->index(['tenant_id', 'status'])->comment('租戶+狀態複合索引');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
