<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->timestamp('email_verified_at')->nullable();$t->string('password');$t->string('role',16)->default('focal')->index();$t->boolean('is_active')->default(true);$t->rememberToken();$t->timestamps();});
        Schema::create('password_reset_tokens',function(Blueprint $t){$t->string('email')->primary();$t->string('token');$t->timestamp('created_at')->nullable();});
        Schema::create('sessions',function(Blueprint $t){$t->string('id')->primary();$t->foreignId('user_id')->nullable()->index();$t->string('ip_address',45)->nullable();$t->text('user_agent')->nullable();$t->longText('payload');$t->integer('last_activity')->index();});
        Schema::create('lgas',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->string('code',12)->unique();$t->timestamps();});
        Schema::create('sectors',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->boolean('is_active')->default(true);$t->timestamps();});
        Schema::create('civic_cases',function(Blueprint $t){$t->id();$t->uuid('uuid')->unique();$t->string('reference',32)->unique();$t->foreignId('reporter_id')->constrained('users');$t->uuid('client_submission_id');$t->string('payload_hash',64);$t->unique(['reporter_id','client_submission_id'],'unique_reporter_submission');$t->string('type',24);$t->string('title',200);$t->text('description');$t->foreignId('lga_id')->constrained('lgas');$t->string('community',150);$t->foreignId('sector_id')->constrained('sectors');$t->date('observation_date');$t->text('observations')->nullable();$t->text('duty_bearers')->nullable();$t->string('progress_status',32)->default('observed');$t->string('verification_status',32)->default('submitted')->index();$t->decimal('latitude',10,7)->nullable();$t->decimal('longitude',10,7)->nullable();$t->timestamps();$t->index(['lga_id','sector_id','verification_status'],'case_map_filters');});
        Schema::create('case_events',function(Blueprint $t){$t->id();$t->foreignId('case_id')->constrained('civic_cases');$t->foreignId('actor_id')->constrained('users');$t->string('event_type',40);$t->string('from_status',32)->nullable();$t->string('to_status',32)->nullable();$t->text('comment')->nullable();$t->timestamp('created_at')->useCurrent();$t->index(['case_id','created_at']);});
        Schema::create('case_followups',function(Blueprint $t){$t->id();$t->foreignId('case_id')->constrained('civic_cases');$t->foreignId('author_id')->constrained('users');$t->text('note');$t->timestamps();});
        Schema::create('case_evidence',function(Blueprint $t){$t->id();$t->foreignId('case_id')->constrained('civic_cases');$t->foreignId('uploaded_by')->constrained('users');$t->string('disk',32)->default('local');$t->string('path');$t->string('original_name');$t->string('mime_type',100);$t->unsignedBigInteger('size_bytes');$t->string('sha256',64);$t->timestamps();});
    }
    public function down():void {foreach(['case_evidence','case_followups','case_events','civic_cases','sectors','lgas','sessions','password_reset_tokens','users'] as $t){Schema::dropIfExists($t);}}
};
