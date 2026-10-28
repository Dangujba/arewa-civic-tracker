<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
class CreateAdmin extends Command {
    protected $signature='act:create-admin {email}';
    protected $description='Create the initial authorized administrator interactively';
    public function handle():int{
        $email=strtolower(trim($this->argument('email')));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $this->error('Invalid email');return self::FAILURE;}
        if(User::where('email',$email)->exists()){$this->error('Email already registered');return self::FAILURE;}
        $name=$this->ask('Full name');$password=$this->secret('Password (minimum 12 characters)');
        if(strlen((string)$password)<12){$this->error('Password too short');return self::FAILURE;}
        User::create(['name'=>$name,'email'=>$email,'password'=>Hash::make($password),'role'=>'admin','is_active'=>true,'must_change_password'=>true]);
        $this->info('Administrator account created; no credentials written to logs.');return self::SUCCESS;
    }
}
