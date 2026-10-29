<?php
namespace App\Services;

use App\Models\CivicCase;
use App\Models\User;
use Illuminate\Support\Str;

class SimilarReportService
{
    /** A suggestion, not a block: reports may cover different visits to one project. */
    public function find(User $viewer,array $input,int $limit=5):array
    {
        $title=trim((string)($input['title']??''));
        $lga=(int)($input['lga_id']??0);
        if($lga<1 || mb_strlen($title)<5)return [];
        $community=$this->normalize((string)($input['community']??''));
        $tokens=$this->tokens($title);
        if(!$tokens)return [];

        // Only inspect a fixed subset in the same LGA, protecting query latency.
        $query=CivicCase::query()->where('lga_id',$lga)
            ->where('verification_status','!=','rejected')
            ->where('created_at','>=',now()->subYear())
            ->when($input['exclude_id']??null,fn($q,$id)=>$q->whereKeyNot($id))
            ->latest('id')->limit(250);
        $sector=(int)($input['sector_id']??0);
        $results=[];
        foreach($query->get(['id','reference','title','type','community','sector_id','reporter_id',
            'observation_date','verification_status','created_at']) as $case){
            $other=$this->tokens($case->title);
            $intersection=count(array_intersect($tokens,$other));
            $union=count(array_unique(array_merge($tokens,$other)));
            $overlap=$union ? $intersection/$union : 0;
            $sameTitle=$this->normalize($title)===$this->normalize($case->title);
            $score=$sameTitle?85:(int)round($overlap*75);
            if($community!==''&&$community===$this->normalize((string)$case->community))$score+=10;
            if($sector>0&&(int)$case->sector_id===$sector)$score+=5;
            if(($input['type']??null)===$case->type)$score+=4;
            $date=$input['observation_date']??null;
            if($date&&abs(\Carbon\Carbon::parse($date)->diffInDays($case->observation_date))<=30)$score+=3;
            if($score<53||($intersection<2&&!$sameTitle))continue;
            $canView=$viewer->can('view',$case);
            $results[]=[
                'score'=>min($score,100),
                'title'=>$canView?$case->title:'Related report in this LGA',
                'community'=>$canView?$case->community:null,
                'reference'=>$canView?$case->reference:null,
                'url'=>$canView?route('cases.show',$case):null,
                'observation_date'=>$case->observation_date->format('Y-m-d')
            ];
        }
        usort($results,fn($a,$b)=>$b['score']<=>$a['score']);
        return array_slice($results,0,$limit);
    }

    private function normalize(string $value):string
    {
        return trim(preg_replace('/\s+/u',' ',mb_strtolower(
            preg_replace('/[^\p{L}\p{N} ]/u',' ',$value)??''
        ))??'');
    }
    private function tokens(string $value):array
    {
        $ignored=['the','with','and','for','from','are','this','that','was','were',
            'project','issue','government','local','area','at','in','to','of','on','a','an'];
        return array_values(array_unique(array_filter(explode(' ',$this->normalize($value)),
            fn($word)=>mb_strlen($word)>=3&&!in_array($word,$ignored,true))));
    }
}
