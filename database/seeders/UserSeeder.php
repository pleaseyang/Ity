<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new \App\Models\User;
        $user->name = 'user';
        $user->email = 'user@ity.com';
        $user->password = \Illuminate\Support\Facades\Hash::make('123456');
        $user->save();
    }
}
