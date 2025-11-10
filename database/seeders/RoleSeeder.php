<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['nama' => 'Supervisor']);
        Role::create(['nama' => 'Karyawan']);
        Role::create(['nama' => 'Admin']);
    }
}
