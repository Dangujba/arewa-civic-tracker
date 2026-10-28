<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Lga extends Model {
 protected $fillable=['name','code'];
 public function cases(){return $this->hasMany(CivicCase::class);}
 public function assignedFocals(){return $this->belongsToMany(User::class,'focal_lga_assignments');}
}
