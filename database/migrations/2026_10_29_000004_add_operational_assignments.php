<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('focal_lga_assignments', function(Blueprint $t){
   $t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();
   $t->foreignId('lga_id')->constrained()->cascadeOnDelete();
   $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
   $t->timestamps();$t->unique(['user_id','lga_id']);
  });
  Schema::table('civic_cases',function(Blueprint $t){
   $t->foreignId('reviewer_id')->nullable()->after('reporter_id')->constrained('users')->nullOnDelete();
   $t->timestamp('review_due_at')->nullable()->after('verification_status')->index();
  });
  Schema::create('admin_activity_logs',function(Blueprint $t){
   $t->id();$t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
   $t->string('action',80);$t->string('subject_type',80)->nullable();$t->unsignedBigInteger('subject_id')->nullable();
   $t->json('details')->nullable();$t->timestamp('created_at')->useCurrent();
   $t->index(['created_at','action']);
  });
  Schema::create('organization_settings',function(Blueprint $t){
   $t->id();$t->unsignedTinyInteger('review_sla_days')->default(7);$t->boolean('require_mfa_admin')->default(false);$t->boolean('require_mfa_meal')->default(false);$t->timestamps();
  });
 }
 public function down(): void {
  Schema::dropIfExists('organization_settings');
  Schema::dropIfExists('admin_activity_logs');
  Schema::table('civic_cases',function(Blueprint $t){$t->dropConstrainedForeignId('reviewer_id');$t->dropColumn('review_due_at');});
  Schema::dropIfExists('focal_lga_assignments');
 }
};
