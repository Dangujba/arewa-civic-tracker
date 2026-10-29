<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CivicCase extends Model {
    protected $table='civic_cases';
    protected $fillable=['uuid','client_submission_id','payload_hash','reference','reporter_id','type','title','description','lga_id','ward','community','sector_id','report_category_id','observation_date','observations','duty_bearers','progress_status','verification_status','reviewer_id','review_due_at','latitude','longitude'];
    protected function casts():array{return ['observation_date'=>'date','review_due_at'=>'datetime','closed_at'=>'datetime','review_last_notified_on'=>'date','legal_hold'=>'boolean','latitude'=>'decimal:7','longitude'=>'decimal:7','published_at'=>'datetime'];}
    protected static function booted():void {
        static::saving(function(self $case) {
            if($case->published_at && $case->isDirty([
                'verification_status','type','title','description','community','ward','lga_id',
                'sector_id','report_category_id','observation_date','progress_status',
                'latitude','longitude','observations','duty_bearers',
            ])) {
                $case->published_at=null;
                $case->published_by=null;
            }
        });
    }
    public function publishedBy(){return $this->belongsTo(User::class,'published_by');}
    public function scopePubliclyVisible($query){return $query->where('verification_status','approved')->whereNotNull('published_at');}
    public function legalHoldBy(){return $this->belongsTo(User::class,'legal_hold_by');}
    public function closedBy(){return $this->belongsTo(User::class,'closed_by');}
    public function reviewer(){return $this->belongsTo(User::class,'reviewer_id');}
    public function reporter(){return $this->belongsTo(User::class,'reporter_id');}
    public function lga(){return $this->belongsTo(Lga::class);}
    public function sector(){return $this->belongsTo(Sector::class);}
    public function category(){return $this->belongsTo(ReportCategory::class,'report_category_id');}
    public function evidence(){return $this->hasMany(CaseEvidence::class,'case_id');}
    public function revisions(){return $this->hasMany(CaseRevision::class,'case_id')->orderByDesc('version');}
    public function events(){return $this->hasMany(CaseEvent::class,'case_id')->orderBy('created_at');}
    public function followups(){return $this->hasMany(CaseFollowup::class,'case_id')->orderBy('created_at');}
    public function scopeVisibleTo($query,User $user){if($user->isAdmin())return $query;
        if($user->role==='meal' && $user->is_active)
            return $query->where(fn($q)=>$q->whereNull('reviewer_id')->orWhere('reviewer_id',$user->id));
        return $query->where('reporter_id',$user->id);}
}
