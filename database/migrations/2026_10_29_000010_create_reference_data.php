<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
 public function up():void {
  Schema::create('report_categories',function(Blueprint $t){
   $t->id();$t->string('type',24);$t->string('name',120);$t->boolean('is_active')->default(true);$t->timestamps();
   $t->unique(['type','name']);
  });
  Schema::create('communities',function(Blueprint $t){
   $t->id();$t->foreignId('lga_id')->constrained('lgas');$t->string('name',150);
   $t->boolean('is_active')->default(true);$t->timestamps();$t->unique(['lga_id','name']);
  });
  Schema::table('civic_cases',function(Blueprint $t){
   $t->foreignId('report_category_id')->nullable()->constrained('report_categories')->nullOnDelete();
  });
  Schema::create('workflow_status_labels',function(Blueprint $t){
   $t->id();$t->string('kind',20);$t->string('code',32);$t->string('label',100);
   $t->timestamps();$t->unique(['kind','code']);
  });
  $now=now();
  foreach([
   'verification'=>['submitted','under_review','clarification_requested','verified','approved','rejected'],
   'progress'=>['observed','not_started','ongoing','stalled','completed','resolved','other']
  ] as $kind=>$codes){
   foreach($codes as $code){
    DB::table('workflow_status_labels')->insert([
     'kind'=>$kind,'code'=>$code,'label'=>ucwords(str_replace('_',' ',$code)),
     'created_at'=>$now,'updated_at'=>$now
    ]);
   }
  }
 }
 public function down():void {
  Schema::dropIfExists('workflow_status_labels');
  Schema::table('civic_cases',function(Blueprint $t){$t->dropConstrainedForeignId('report_category_id');});
  Schema::dropIfExists('communities');Schema::dropIfExists('report_categories');
 }
};
