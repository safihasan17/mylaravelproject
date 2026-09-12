<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Role::factory()->createMany([
        //     ['name' => 'Super Admin'],
        //     ['name' => 'Doctor'],
        //     ['name' => 'Receptionist'],
        //     ['name' => 'Pharmacist'],
        //     ['name' => 'Lab Technician'],
        //     ['name' => 'Accountant'],
        // ]);


        // User::factory(30)->create();

        Patient::factory(30)->create();


    }
}
