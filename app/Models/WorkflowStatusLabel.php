<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkflowStatusLabel extends Model {
 protected $fillable=['kind','code','label'];
 public static function label(string $kind,string $code):string {
  return static::query()->where('kind',$kind)->where('code',$code)->value('label')
    ??ucwords(str_replace('_',' ',$code));
 }
}
