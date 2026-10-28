<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CivicCase extends Model {
    protected $table='civic_cases';
    protected $fillable=['uuid','client_submission_id','payload_hash','reference','reporter_id','type','title','description','lga_id','community','sector_id','observation_date','observations','duty_bearers','progress_status','verification_status','latitude','longitude'];
    protected function casts():array{return ['observation_date'=>'date','latitude'=>'decimal:7','longitude'=>'decimal:7'];}
    public function reporter(){return $this->belongsTo(User::class,'reporter_id');}
    public function lga(){return $this->belongsTo(Lga::class);}
    public function sector(){return $this->belongsTo(Sector::class);}
    public function evidence(){return $this->hasMany(CaseEvidence::class,'case_id');}
    public function events(){return $this->hasMany(CaseEvent::class,'case_id')->orderBy('created_at');}
    public function followups(){return $this->hasMany(CaseFollowup::class,'case_id')->orderBy('created_at');}
    public function scopeVisibleTo($query,User $user){return $user->canReview()?$query:$query->where('reporter_id',$user->id);}
}
