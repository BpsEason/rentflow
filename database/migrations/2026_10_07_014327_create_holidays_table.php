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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id()->comment('國定假日ID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('date')->comment('國定假日日期');
            $table->string('name')->comment('國定假日名稱');
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
