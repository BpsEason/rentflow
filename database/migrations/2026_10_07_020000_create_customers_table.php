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
        Schema::create('customers', function (Blueprint $table) {
            $table->id()->comment('客戶ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict')->comment('租戶ID');
            $table->string('name')->comment('客戶姓名');
            $table->string('email')->nullable()->comment('電子郵件');
            $table->string('phone')->comment('聯絡電話');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'is_active'])->comment('租戶+啟用狀態複合索引');
            $table->unique(['tenant_id', 'email'])->comment('同一租戶內Email不可重複');
            $table->unique(['tenant_id', 'phone'])->comment('同一租戶內電話不可重複');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
