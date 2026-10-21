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
        Schema::create('agent_loops', function (Blueprint $table) {
            $table->id();
            $table->text('test_failure_content')->comment('測試失敗內容');
            $table->text('ai_analysis')->nullable()->comment('AI 分析結果');
            $table->string('status')->default('pending')->comment('狀態 (pending, processing, completed, failed, waiting_quota, paused_quota)');
            $table->text('failure_reason')->nullable()->comment('失敗原因');
            $table->integer('retry_count')->default(0)->comment('重試次數');
            $table->timestamp('next_retry_at')->nullable()->comment('下次重試時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');
            $table->timestamp('processing_started_at')->nullable()->comment('開始處理的時間，用於超時恢復');
            $table->string('provider')->nullable()->comment('使用的 AI 提供商');
            $table->string('model')->nullable()->comment('使用的模型');
            $table->timestamps();

            // 架構師建議：若背景 Worker 頻繁需透過 status 或 next_retry_at 輪詢任務，建議加上索引
            $table->index(['status', 'next_retry_at'], 'agent_loops_status_retry_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_loops');
    }
};
