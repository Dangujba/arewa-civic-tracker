<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('case_revisions',function(Blueprint $t){
   $t->id();$t->foreignId('case_id')->constrained('civic_cases');
   $t->foreignId('actor_id')->constrained('users');
   $t->unsignedInteger('version');
   $t->string('verification_status_at_edit',32);
   $t->text('reason');
   $t->json('before_values');$t->json('after_values');$t->json('changes');
   $t->timestamp('created_at')->useCurrent();
   $t->unique(['case_id','version']);
  });
 }
 public function down():void{Schema::dropIfExists('case_revisions');}
};
