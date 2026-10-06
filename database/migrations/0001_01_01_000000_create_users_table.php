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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('使用者ID');
            $table->string('name')->comment('使用者名稱');
            $table->string('email')->unique()->comment('電子郵件');
            $table->timestamp('email_verified_at')->nullable()->comment('信箱驗證時間');
            $table->string('password')->comment('密碼雜湊');
            $table->rememberToken()->comment('記住我token');
            $table->timestamp('created_at')->comment('建立時間');
            $table->timestamp('updated_at')->comment('更新時間');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('電子郵件');
            $table->string('token')->comment('密碼重設token');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('會話ID');
            $table->foreignId('user_id')->nullable()->index()->comment('使用者ID');
            $table->string('ip_address', 45)->nullable()->comment('IP位址');
            $table->text('user_agent')->nullable()->comment('使用者代理');
            $table->longText('payload')->comment('會話資料');
            $table->integer('last_activity')->index()->comment('最後活動時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
