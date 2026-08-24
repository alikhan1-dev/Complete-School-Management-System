<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CI Thermal Print addon settings used by Studentfee print views.
 * Columns match thermalPrint* view fields (school_name, address, footer_text, is_print).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('thermal_print')) {
            Schema::create('thermal_print', function (Blueprint $table) {
                $table->increments('id');
                $table->string('school_name', 255)->default('');
                $table->text('address')->nullable();
                $table->text('footer_text')->nullable();
                $table->tinyInteger('is_print')->default(0);
                $table->timestamps();
            });

            DB::table('thermal_print')->insert([
                'school_name' => '',
                'address' => '',
                'footer_text' => '',
                'is_print' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // CI Module_model::hasModule('thermal_print') — row must exist in permission_group.
        $exists = DB::table('permission_group')->where('short_code', 'thermal_print')->exists();
        if (! $exists) {
            DB::table('permission_group')->insert([
                'name' => 'Thermal Print',
                'short_code' => 'thermal_print',
                'is_active' => 0,
                'system' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Keep addon permission_group row; only drop settings table created here.
        Schema::dropIfExists('thermal_print');
    }
};
