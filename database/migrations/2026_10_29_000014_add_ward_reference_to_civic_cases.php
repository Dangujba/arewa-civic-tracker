<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up():void {
        Schema::create('wards',function(Blueprint $table) {
            $table->id();
            $table->foreignId('lga_id')->constrained('lgas')->cascadeOnDelete();
            $table->string('name',150);
            $table->string('source_slug',150);
            $table->boolean('is_active')->default(true);
            $table->unique(['lga_id','source_slug']);
            $table->index(['lga_id','name']);
        });
        Schema::table('civic_cases',function(Blueprint $table) {
            // Nullable preserves historical cases and existing encrypted offline outbox records.
            $table->string('ward',150)->nullable()->after('lga_id');
            $table->index(['lga_id','ward']);
        });

    }
    public function down():void {
        Schema::table('civic_cases',function(Blueprint $table){
            $table->dropIndex(['lga_id','ward']);
            $table->dropColumn('ward');
        });
        Schema::dropIfExists('wards');
    }
};
