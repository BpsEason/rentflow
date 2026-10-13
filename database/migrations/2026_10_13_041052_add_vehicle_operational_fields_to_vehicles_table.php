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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('manufacture_year')->nullable()->comment('出廠年份');
            $table->integer('mileage')->nullable()->comment('目前里程數(公里)');
            $table->string('fuel_type')->nullable()->comment('燃料類型：PETROL=汽油, DIESEL=柴油, ELECTRIC=電動, HYBRID=油電混合');
            $table->string('transmission')->nullable()->comment('變速箱：AUTOMATIC=自排, MANUAL=手排');
            $table->dateTime('maintenance_start_at')->nullable()->comment('維修開始時間');
            $table->dateTime('maintenance_expected_end_at')->nullable()->comment('預計維修完成時間');
            $table->text('maintenance_notes')->nullable()->comment('維修備註');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'manufacture_year',
                'mileage',
                'fuel_type',
                'transmission',
                'maintenance_start_at',
                'maintenance_expected_end_at',
                'maintenance_notes',
            ]);
        });
    }
};
