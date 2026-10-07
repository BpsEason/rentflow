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
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('訂單ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict')->comment('租戶ID');
            $table->foreignId('reservation_id')->constrained()->onDelete('restrict')->comment('預約ID');
            $table->string('order_number')->unique()->comment('訂單編號');
            $table->string('status')->comment('訂單狀態：PENDING=待處理, CONFIRMED=已確認, CANCELLED=已取消, COMPLETED=已完成');
            $table->decimal('total_amount', 12, 2)->comment('訂單總金額');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'order_number'])->comment('租戶+訂單編號複合索引');
            $table->index(['tenant_id', 'status'])->comment('租戶+狀態複合索引');
            $table->unique(['tenant_id', 'reservation_id'])->comment('同一預約只能建立一個訂單');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
