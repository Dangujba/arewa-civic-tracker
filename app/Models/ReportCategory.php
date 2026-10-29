<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReportCategory extends Model {
 protected $fillable=['name','type','is_active'];
 protected function casts():array{return ['is_active'=>'boolean'];}
}
