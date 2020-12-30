<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = new \App\Models\Admin;
        $admin->name = 'admin';
        $admin->email = 'admin@ity.com';
        $admin->password = \Illuminate\Support\Facades\Hash::make('123456');
        $admin->save();

        $admin2 = new \App\Models\Admin;
        $admin2->name = 'test';
        $admin2->email = 'test@ity.com';
        $admin2->password = \Illuminate\Support\Facades\Hash::make('123456');
        $admin2->save();
    }
}
