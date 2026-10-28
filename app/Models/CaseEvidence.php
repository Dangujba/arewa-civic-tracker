<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaseEvidence extends Model {
    protected $table='case_evidence';
    protected $fillable=['case_id','uploaded_by','disk','path','original_name','mime_type','size_bytes','sha256'];
}
