<?php
namespace Database\Seeders;
use App\Models\Lga;
use App\Models\Sector;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run():void {
        foreach(['Bade','Bursari','Damaturu','Fika','Fune','Geidam','Gujba','Gulani','Jakusko','Karasuwa','Machina','Nangere','Nguru','Potiskum','Tarmuwa','Yunusari','Yusufari'] as $name){Lga::firstOrCreate(['name'=>$name],['code'=>strtoupper(substr($name,0,3))]);}
        foreach(['Health','Education','Water and sanitation','Roads and transport','Livelihoods','Agriculture','Governance','Other'] as $name){Sector::firstOrCreate(['name'=>$name]);}
        // No seeded user credentials or invented civic observations.
    }
}
