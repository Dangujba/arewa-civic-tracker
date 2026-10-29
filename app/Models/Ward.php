<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Ward extends Model {
    public $timestamps=false;
    protected $fillable=['lga_id','name','source_slug','is_active'];
    protected function casts():array {return ['is_active'=>'boolean'];}
    public function lga(){return $this->belongsTo(Lga::class);}
}
