<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DataGovernancePolicy extends Model {
 protected $fillable=['case_retention_days','evidence_retention_days','audit_retention_days',
 'state','approval_reference','notes','updated_by','approved_by','approved_at'];
 protected function casts():array {return ['approved_at'=>'datetime'];}
 public function approver(){return $this->belongsTo(User::class,'approved_by');}
}
