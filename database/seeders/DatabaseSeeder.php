<?php

namespace Database\Seeders;

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
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(OrgSeeder::class);
        $this->call(EvaluationTemplateSeeder::class);

        $user = User::updateOrCreate(['nik' => 'D260065'], [
            'name' => 'Kicky Maulana',
            'email' => 'kickymaulana@gmail.com',
            'role' => 'admin',
            'is_approved' => true,
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles(['admin']);
    }
}
