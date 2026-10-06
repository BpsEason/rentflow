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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id()->comment('租戶ID');
            $table->string('name')->comment('租戶名稱');
            $table->string('slug')->unique()->comment('租戶唯一識別碼');
            $table->timestamp('created_at')->comment('建立時間');
            $table->timestamp('updated_at')->comment('更新時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
