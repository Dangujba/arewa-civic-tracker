<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('case_email_outbox',function(Blueprint $t){
   $t->id();$t->uuid('notification_id')->unique();
   $t->foreignId('recipient_id')->constrained('users');
   $t->foreignId('case_id')->constrained('civic_cases');
   $t->string('recipient_email');$t->string('subject');$t->text('detail');
   $t->string('status',20)->default('pending')->index();
   $t->unsignedTinyInteger('attempts')->default(0);
   $t->timestamp('next_attempt_at')->nullable();$t->timestamp('sent_at')->nullable();
   $t->string('error_code',50)->nullable();$t->timestamps();
  });
 }
 public function down():void {Schema::dropIfExists('case_email_outbox');}
};
