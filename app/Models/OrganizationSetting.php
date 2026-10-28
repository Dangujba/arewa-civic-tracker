<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrganizationSetting extends Model {
 protected $fillable=['review_sla_days','require_mfa_admin','require_mfa_meal'];
 protected function casts(): array{return ['require_mfa_admin'=>'boolean','require_mfa_meal'=>'boolean'];}
 public static function requiresMfa(string $role):bool {
  if(!in_array($role,['admin','meal'],true))return false;
  return (bool)(static::query()->value('require_mfa_'.$role)??false);
 }
 public static function reviewDays(): int {return (int)(static::query()->value('review_sla_days')??7);}
}
