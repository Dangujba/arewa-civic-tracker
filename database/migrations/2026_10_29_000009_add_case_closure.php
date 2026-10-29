<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('civic_cases',function(Blueprint $t){
   $t->string('closure_status',16)->default('open')->index();
   $t->foreignId('closed_by')->nullable()->constrained('users');
   $t->timestamp('closed_at')->nullable();
   $t->text('closure_reason')->nullable();
  });
 }
 public function down():void {
  Schema::table('civic_cases',function(Blueprint $t){
   $t->dropForeign(['closed_by']);
   $t->dropColumn(['closure_status','closed_by','closed_at','closure_reason']);
  });
 }
};
