<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new \LogicException('Demo users may only be seeded locally or in tests.');
        }
        \App\Models\User::firstOrCreate(['UserName' => 'student'], [
            'Password' => password_hash('123456', PASSWORD_BCRYPT),
        ]);
    }
}
