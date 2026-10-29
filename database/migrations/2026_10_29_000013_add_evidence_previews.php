<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('case_evidence', function (Blueprint $table) {
            $table->string('preview_path')->nullable();
            $table->unsignedBigInteger('preview_size_bytes')->nullable();
            $table->string('preview_sha256',64)->nullable();
        });
    }
    public function down(): void {
        Schema::table('case_evidence', function (Blueprint $table) {
            $table->dropColumn(['preview_path','preview_size_bytes','preview_sha256']);
        });
    }
};
