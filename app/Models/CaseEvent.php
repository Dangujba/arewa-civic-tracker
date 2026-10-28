<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaseEvent extends Model {
    public $timestamps=false;
    const UPDATED_AT=null;
    protected $fillable=['case_id','actor_id','event_type','from_status','to_status','comment','created_at'];
    public function actor(){return $this->belongsTo(User::class,'actor_id');}
    protected function casts():array{return ['created_at'=>'datetime'];}
}
