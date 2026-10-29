<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('civic_cases', function(Blueprint $table) {
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('civic_cases', function(Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropColumn(['published_at', 'published_by']);
        });
    }
};
