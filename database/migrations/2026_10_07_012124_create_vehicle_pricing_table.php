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
        Schema::create('vehicle_pricing', function (Blueprint $table) {
            $table->id()->comment('定價ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict')->comment('租戶ID');
            $table->foreignId('vehicle_id')->constrained()->onDelete('restrict')->comment('車輛ID');
            $table->decimal('weekday_price', 12, 2)->comment('平日單價');
            $table->decimal('weekend_price', 12, 2)->comment('週末單價');
            $table->decimal('holiday_price', 12, 2)->comment('國定假日單價');
            $table->timestamps();

            // 唯一約束：同一租戶下同一台車只能有一組定價
            $table->unique(['tenant_id', 'vehicle_id'])->comment('租戶+車輛唯一約束，確保同一租戶下同一車輛只有一組定價');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_pricing');
    }
};
