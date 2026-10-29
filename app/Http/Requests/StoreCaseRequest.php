<?php
namespace App\Http\Requests;
use App\Models\CivicCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreCaseRequest extends FormRequest {
    public function authorize():bool{return $this->user()?->can('create',CivicCase::class)??false;}
    public function rules():array{return [
        'client_submission_id'=>['required','uuid'],
        'type'=>['required',Rule::in(['public_project','governance_issue'])],
        'title'=>['required','string','min:5','max:200'],
        'description'=>['required','string','min:15','max:10000'],
        'lga_id'=>['required','integer','exists:lgas,id',function($attribute,$value,$fail){
            $user=$this->user();
            if(!$user || $user->role!=='focal')return;
            $assigned=$user->assignedLgas()->pluck('lgas.id')->all();
            if(!count($assigned))$fail('Your account has no assigned LGA. Contact your RAWYOD Administrator.');
            elseif(!in_array((int)$value,array_map('intval',$assigned),true))
                $fail('This local government is not assigned to your field account.');
        }],
        'community'=>['required','string','max:150'],
        'sector_id'=>['required','integer',Rule::exists('sectors','id')->where('is_active',true)],
        'report_category_id'=>['nullable','integer',
          Rule::exists('report_categories','id')->where('is_active',true)->where('type',$this->input('type'))],
        'observation_date'=>['required','date','before_or_equal:today'],
        'observations'=>['nullable','string','max:10000'],
        'duty_bearers'=>['nullable','string','max:5000'],
        'progress_status'=>['required',Rule::in(['observed','not_started','ongoing','stalled','completed','resolved','other'])],
        'latitude'=>['nullable','required_with:longitude','numeric','between:-90,90'],
        'longitude'=>['nullable','required_with:latitude','numeric','between:-180,180'],
        'evidence'=>['sometimes','array','max:5'],
        'evidence.*'=>['file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
    ];}
}
