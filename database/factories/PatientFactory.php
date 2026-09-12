<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'dob' => $this->faker->dateTimeBetween('-80 years', '-1 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['Male', 'Female', 'Other']),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'address' => $this->faker->address(),
            'phone' => $this->faker->numerify('01#########'),
            'guardian_name' => $this->faker->optional()->name(),
            'emergency_contact' => $this->faker->numerify('01#########'),
            'created_by' => User::inRandomOrder()->value('id') ?? $this->fallbackUserId(),
        ];
    }

    /**
     * Create a fallback user when the users table is empty.
     * UserFactory assigns role_id between 1-6, so we make sure
     * at least 6 roles exist before creating the user, otherwise
     * the FK constraint on users.role_id will fail.
     */
    protected function fallbackUserId(): int
    {
        $existingRoles = Role::count();

        if ($existingRoles < 6) {
            Role::factory()->count(6 - $existingRoles)->create();
        }

        return User::factory()->create()->id;
    }
}