<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class SectorController extends Controller {
    private function gate(Request $r):void {abort_unless($r->user()?->isAdmin(),403);}
    public function index(Request $r){$this->gate($r);return view('admin.sectors.index',['sectors'=>Sector::orderBy('name')->get()]);}
    public function store(Request $r){$this->gate($r);$data=$r->validate(['name'=>['required','string','max:120','unique:sectors,name']]);Sector::create([...$data,'is_active'=>true]);return back()->with('message','Sector added.');}
    public function update(Request $r,Sector $sector){$this->gate($r);$data=$r->validate(['name'=>['required','string','max:120',Rule::unique('sectors','name')->ignore($sector->id)],'is_active'=>['required','boolean']]);$sector->update($data);return back()->with('message','Sector updated.');}
}
