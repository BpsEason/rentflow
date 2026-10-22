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
        Schema::table('agent_loops', function (Blueprint $table) {
            $table->integer('current_loop')->default(0)->comment('目前的迴圈輪次');
            $table->integer('max_loops')->default(3)->comment('最大允許的迴圈次數');
            $table->text('test_path')->nullable()->comment('測試檔案/路徑');
            $table->integer('last_test_exit_code')->nullable()->comment('上次測試退出碼');
            $table->text('last_test_output')->nullable()->comment('上次測試輸出');
            $table->text('last_test_summary')->nullable()->comment('上次測試摘要');
            $table->json('modified_files')->nullable()->comment('本次修改的檔案清單');
            $table->json('patch_history')->nullable()->comment('修補歷史，用於復原');
            $table->boolean('auto_apply_fixes')->default(false)->comment('是否自動套用修正');
            $table->timestamp('loop_started_at')->nullable()->comment('本次迴圈開始時間');
            $table->timestamp('loop_completed_at')->nullable()->comment('本次迴圈完成時間');
            $table->string('termination_reason')->nullable()->comment('終止原因');
            $table->integer('total_execution_time')->default(0)->comment('總執行時間(秒)');
            $table->json('allowed_files')->nullable()->comment('允許修改的檔案清單');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_loops', function (Blueprint $table) {
            $table->dropColumn([
                'current_loop',
                'max_loops',
                'test_path',
                'last_test_exit_code',
                'last_test_output',
                'last_test_summary',
                'modified_files',
                'patch_history',
                'auto_apply_fixes',
                'loop_started_at',
                'loop_completed_at',
                'termination_reason',
                'total_execution_time',
                'allowed_files',
            ]);
        });
    }
};
