<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaseRevision extends Model {
 public $timestamps=false;
 protected $fillable=['case_id','actor_id','version','verification_status_at_edit','reason','before_values','after_values','changes','created_at'];
 protected function casts():array{return ['before_values'=>'array','after_values'=>'array','changes'=>'array','created_at'=>'datetime'];}
 public function actor(){return $this->belongsTo(User::class,'actor_id');}
 public function case(){return $this->belongsTo(CivicCase::class,'case_id');}
}
