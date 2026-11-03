<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('civic_cases', function(Blueprint $table) {
            $table->time('observation_time')->nullable();
        });
    }
    public function down(): void {
        Schema::table('civic_cases', fn(Blueprint $table) => $table->dropColumn('observation_time'));
    }
};
