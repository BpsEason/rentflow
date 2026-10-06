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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('任務ID');
            $table->string('queue')->index()->comment('佇列名稱');
            $table->longText('payload')->comment('任務負載資料');
            $table->unsignedTinyInteger('attempts')->comment('重試次數');
            $table->unsignedInteger('reserved_at')->nullable()->comment('預約處理時間');
            $table->unsignedInteger('available_at')->comment('可執行時間');
            $table->unsignedInteger('created_at')->comment('建立時間');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('批次ID');
            $table->string('name')->comment('批次名稱');
            $table->integer('total_jobs')->comment('總任務數');
            $table->integer('pending_jobs')->comment('等待中任務數');
            $table->integer('failed_jobs')->comment('失敗任務數');
            $table->longText('failed_job_ids')->comment('失敗任務ID列表');
            $table->mediumText('options')->nullable()->comment('批次選項');
            $table->integer('cancelled_at')->nullable()->comment('取消時間');
            $table->integer('created_at')->comment('建立時間');
            $table->integer('finished_at')->nullable()->comment('完成時間');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('失敗任務ID');
            $table->string('uuid')->unique()->comment('通用唯一識別碼');
            $table->text('connection')->comment('連線名稱');
            $table->text('queue')->comment('佇列名稱');
            $table->longText('payload')->comment('任務負載資料');
            $table->longText('exception')->comment('例外資訊');
            $table->timestamp('failed_at')->useCurrent()->comment('失敗時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
