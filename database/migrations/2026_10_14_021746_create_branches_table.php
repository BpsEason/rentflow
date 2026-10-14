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
        Schema::create('branches', function (Blueprint $table) {
            $table->id()->comment('據點ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict')->comment('所屬租戶ID');
            $table->string('name')->comment('據點名稱');
            $table->text('address')->comment('地址');
            $table->string('phone')->comment('聯絡電話');
            $table->json('business_hours')->nullable()->comment('營業時間，JSON格式儲存');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_active'])->comment('租戶+啟用狀態複合索引');
            $table->unique(['tenant_id', 'name'])->comment('同一租戶內據點名稱不可重複');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
