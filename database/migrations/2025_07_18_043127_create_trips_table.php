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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->BigInteger('user_id'); // vehicle belongs to a user
            $table->string('start_date');
            $table->string('end_date')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('start_point')->nullable();
            $table->string('end_point')->nullable();
            $table->string('seat_capacity')->nullable();
            $table->string('coach_no')->nullable();
            $table->string('trip_id')->nullable();
            $table->string('bus_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->string('bus_category')->nullable();
            $table->string('driver_mobile')->nullable();

            $table->string('challan')->nullable();
            $table->string('driver_commision')->nullable();
            $table->string('helper_commision')->nullable();
            $table->string('checker_commision')->nullable();
            $table->string('wash')->nullable();
            $table->string('omit_khoraki')->nullable();
            $table->string('supervisor_commision')->nullable();

            // fuel 
            $table->string('odometer_start')->nullable();
            $table->string('odometer_end')->nullable();
            $table->string('run_km')->nullable();
            $table->string('kpl')->nullable();
            $table->string('fuel_ltr')->nullable();
            $table->string('fuel_cost')->nullable();


            $table->string('remarks')->nullable();
            $table->string('food_cost')->nullable();
            $table->string('total_exp')->nullable();
            $table->string('total_rent')->nullable();
            $table->string('advance')->nullable();
            $table->string('due_amount')->nullable();
            $table->string('parking_cost')->nullable();
            $table->string('night_guard')->nullable();
            $table->string('toll_cost')->nullable();
            $table->string('feri_cost')->nullable();
            $table->string('police_cost')->nullable();
            $table->string('others_cost')->nullable();
            $table->string('chada')->nullable();
            $table->string('labor')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
