<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_loop_demo_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_loop_id')->constrained('agent_loops')->cascadeOnDelete();
            $table->unsignedInteger('loop_number');
            $table->integer('test_exit_code')->nullable();
            $table->longText('test_output')->nullable();
            $table->longText('analysis')->nullable();
            $table->string('analysis_source')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->string('result_status');
            $table->timestamps();
            $table->unique(['agent_loop_id', 'loop_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_loop_demo_runs');
    }
};
