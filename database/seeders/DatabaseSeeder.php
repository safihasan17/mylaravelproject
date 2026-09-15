<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionMedicine;
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

        // Patient::factory(30)->create();

        // Department::factory()->createMany([
        //     ['name' => 'Cardiology', 'description' => 'Diagnosis and treatment of heart-related conditions.'],
        //     ['name' => 'Orthopedics', 'description' => 'Care for bones, joints, and the musculoskeletal system.'],
        //     ['name' => 'Pediatrics', 'description' => 'Medical care for infants, children, and adolescents.'],
        //     ['name' => 'Neurology', 'description' => 'Diagnosis and treatment of disorders of the nervous system.'],
        //     ['name' => 'Gynecology', 'description' => "Women's reproductive health and related care."],
        //     ['name' => 'Dermatology', 'description' => 'Diagnosis and treatment of skin, hair, and nail conditions.'],
        //     ['name' => 'ENT', 'description' => 'Ear, nose, and throat related treatment and surgery.'],
        //     ['name' => 'General Medicine', 'description' => 'General diagnosis and treatment of common illnesses.'],
        // ]);

        // Doctor::factory(30)->create();

        // Appointment::factory(30)->create();

        // Prescription::factory(30)->create();
        Medicine::factory(30)->create();
        PrescriptionMedicine::factory(30)->create();


    }
}
