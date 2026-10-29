<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('notifications',function(Blueprint $t){
   $t->uuid('id')->primary();$t->string('type');
   $t->morphs('notifiable');$t->text('data');
   $t->timestamp('read_at')->nullable();$t->timestamps();
  });
  Schema::table('civic_cases',function(Blueprint $t){$t->date('review_last_notified_on')->nullable();});
 }
 public function down():void {
  Schema::table('civic_cases',function(Blueprint $t){$t->dropColumn('review_last_notified_on');});
  Schema::dropIfExists('notifications');
 }
};
