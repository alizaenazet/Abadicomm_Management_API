<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('workers')->insert([
            [
                'name'      => 'Admin User',
                'email'     => 'admin@example.com',
                'password'  => Hash::make('password123'),
                'role_id'   => 3,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]
        ]);
    }
}
