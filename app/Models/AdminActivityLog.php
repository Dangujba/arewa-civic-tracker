<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminActivityLog extends Model {
 public $timestamps=false;
 protected $fillable=['actor_id','action','subject_type','subject_id','details'];
 protected function casts(): array {return ['details'=>'array','created_at'=>'datetime'];}
 public function actor(){return $this->belongsTo(User::class,'actor_id');}
 public static function record(User $actor,string $action,?Model $subject=null,array $details=[]):self {
  return self::create(['actor_id'=>$actor->id,'action'=>$action,
   'subject_type'=>$subject?->getMorphClass(),'subject_id'=>$subject?->getKey(),'details'=>$details]);
 }
}
