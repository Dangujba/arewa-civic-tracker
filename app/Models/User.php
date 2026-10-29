<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use Notifiable, HasApiTokens;
    protected $fillable=['name','email','password','role','is_active','must_change_password'];
    protected $hidden=['password','remember_token','two_factor_secret','two_factor_recovery_codes'];
    protected function casts(): array {return ['password'=>'hashed','is_active'=>'boolean','must_change_password'=>'boolean','email_verified_at'=>'datetime','two_factor_secret'=>'encrypted','two_factor_recovery_codes'=>'encrypted:array','two_factor_confirmed_at'=>'datetime'];}
    public function assignedLgas(){return $this->belongsToMany(Lga::class,'focal_lga_assignments')->withTimestamps();}
    public function reports(){return $this->hasMany(CivicCase::class,'reporter_id');}
    public function assignedReviews(){return $this->hasMany(CivicCase::class,'reviewer_id');}
    public function isAdmin():bool{return $this->is_active && $this->role==='admin';}
    public function canReview():bool{return $this->is_active && in_array($this->role,['admin','meal'],true);}
}
