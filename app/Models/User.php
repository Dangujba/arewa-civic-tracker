<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
    use Notifiable;
    protected $fillable=['name','email','password','role','is_active','must_change_password'];
    protected $hidden=['password','remember_token'];
    protected function casts(): array {return ['password'=>'hashed','is_active'=>'boolean','must_change_password'=>'boolean','email_verified_at'=>'datetime'];}
    public function isAdmin():bool{return $this->is_active && $this->role==='admin';}
    public function canReview():bool{return $this->is_active && in_array($this->role,['admin','meal'],true);}
}
