<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('data_governance_policies',function(Blueprint $t){
   $t->id();$t->unsignedInteger('case_retention_days')->nullable();
   $t->unsignedInteger('evidence_retention_days')->nullable();
   $t->unsignedInteger('audit_retention_days')->nullable();
   $t->string('state',16)->default('draft');$t->string('approval_reference',160)->nullable();
   $t->text('notes')->nullable();$t->foreignId('updated_by')->nullable()->constrained('users');
   $t->foreignId('approved_by')->nullable()->constrained('users');$t->timestamp('approved_at')->nullable();
   $t->timestamps();
  });
  Schema::table('civic_cases',function(Blueprint $t){
   $t->boolean('legal_hold')->default(false)->index();
   $t->text('legal_hold_reason')->nullable();
   $t->foreignId('legal_hold_by')->nullable()->constrained('users');
  });
 }
 public function down():void {
  Schema::table('civic_cases',function(Blueprint $t){
   $t->dropForeign(['legal_hold_by']);
   $t->dropColumn(['legal_hold','legal_hold_reason','legal_hold_by']);
  });
  Schema::dropIfExists('data_governance_policies');
 }
};
