<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Community extends Model {
 protected $fillable=['lga_id','name','is_active'];
 protected function casts():array{return ['is_active'=>'boolean'];}
 public function lga(){return $this->belongsTo(Lga::class);}
}
