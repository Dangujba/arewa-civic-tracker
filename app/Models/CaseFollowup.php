<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaseFollowup extends Model {
    protected $fillable=['case_id','author_id','note'];
}
