<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class YobeWardSeeder extends Seeder {
    public function run():void {
        $source=json_decode(file_get_contents(database_path('data/yobe_wards.json')),true,512,JSON_THROW_ON_ERROR);
        $lgas=DB::table('lgas')->get(['id','name'])->keyBy(fn($lga)=>Str::slug($lga->name));
        $rows=[];
        foreach($source['lgas'] as $entry){
            $lga=$lgas->get($entry['lga']);
            if(!$lga)continue;
            foreach($entry['wards'] as $ward){
                $rows[]=[
                    'lga_id'=>$lga->id,'name'=>$ward['name'],
                    'source_slug'=>$ward['slug'],'is_active'=>true
                ];
            }
        }
        // Safe for repeated deployments and for installations seeding LGAs
        // AFTER all migrations. Never overwrite any manually entered report ward.
        foreach(array_chunk($rows,100) as $batch) {
            DB::table('wards')->upsert($batch,['lga_id','source_slug'],['name','is_active']);
        }
    }
}
