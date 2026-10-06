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
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id()->comment('關聯ID');
            $table->foreignId('tenant_id')->comment('租戶ID')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->comment('使用者ID')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member')->comment('使用者在租戶中的角色');
            $table->timestamp('created_at')->comment('建立時間');
            $table->timestamp('updated_at')->comment('更新時間');

            $table->unique(['tenant_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
