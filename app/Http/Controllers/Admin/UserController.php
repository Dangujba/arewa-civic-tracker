<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class UserController extends Controller {
    private function gate(Request $r):void {abort_unless($r->user()?->isAdmin(),403);}
    public function index(Request $r){$this->gate($r);return view('admin.users.index',['users'=>User::orderBy('name')->paginate(25)]);}
    public function create(Request $r){$this->gate($r);return view('admin.users.create');}
    public function store(Request $r){$this->gate($r);
        $data=$r->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:255','unique:users,email'],'role'=>['required',Rule::in(['admin','meal','focal'])],'password'=>['required','string','min:12','confirmed']]);
        User::create([...$data,'is_active'=>true,'must_change_password'=>true]);
        return redirect()->route('admin.users.index')->with('message','User account created.');
    }
    public function update(Request $r,User $user){$this->gate($r);
        $data=$r->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],'role'=>['required',Rule::in(['admin','meal','focal'])],'is_active'=>['required','boolean']]);
        if($user->id===$r->user()->id && (!$data['is_active'] || $data['role']!=='admin')){
            return back()->withErrors(['role'=>'You cannot deactivate or demote your own administrator account.']);
        }
        $user->update($data);
        return redirect()->route('admin.users.index')->with('message','User details updated.');
    }
}
