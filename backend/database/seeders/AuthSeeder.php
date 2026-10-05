<?php
namespace Database\Seeders;
use App\Models\User;
class AuthSeeder extends \Illuminate\Database\Seeder {
    public function run(): void {
        $username=env('ADMIN_USERNAME'); $password=env('ADMIN_PASSWORD');
        if (!$username || !$password || strlen($password)<8) throw new \RuntimeException('Set ADMIN_USERNAME and ADMIN_PASSWORD (at least 8 characters) before running AuthSeeder.');
        $user=User::firstOrCreate(['username'=>$username],['name'=>'Administrator','password'=>password_hash($password,PASSWORD_BCRYPT),'role'=>'admin']);
        if ($user->role!=='admin') throw new \RuntimeException('This username already belongs to a non-admin account. Choose another ADMIN_USERNAME.');
    }
}
