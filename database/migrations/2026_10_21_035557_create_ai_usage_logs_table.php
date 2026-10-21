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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->comment('AI 供應商 (例如: gemini, openai)');
            $table->string('model')->comment('模型名稱 (例如: gemini-1.5-flash)');
            $table->date('usage_date')->comment('使用日期');
            $table->integer('request_count')->default(0)->comment('請求次數');
            $table->integer('prompt_tokens')->default(0)->comment('輸入 token 數');
            $table->integer('completion_tokens')->default(0)->comment('輸出 token 數');
            $table->integer('total_tokens')->default(0)->comment('總 token 數');
            $table->text('error_details')->nullable()->comment('錯誤資訊');

            $table->unique(['provider', 'model', 'usage_date'], 'ai_usage_logs_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
