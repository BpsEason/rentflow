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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('id_number')->nullable()->comment('身份證字號');
            $table->string('license_number')->nullable()->comment('駕照號碼');
            $table->date('license_expiry')->nullable()->comment('駕照有效日期');
            $table->text('address')->nullable()->comment('聯絡地址');
            $table->string('emergency_contact_name')->nullable()->comment('緊急聯絡人姓名');
            $table->string('emergency_contact_phone')->nullable()->comment('緊急聯絡人電話');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'id_number',
                'license_number',
                'license_expiry',
                'address',
                'emergency_contact_name',
                'emergency_contact_phone'
            ]);
        });
    }
};
