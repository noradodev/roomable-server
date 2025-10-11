<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            RoleSeeder::class
        ]);

        $user = User::factory()->create([
            'id' => Str::uuid(),
            'name' => 'Rado No',
            'email' => 'test@admin.com',
            'password' => Hash::make('111213')
        ]);
        $landLord = User::factory()->create([
            'id' => Str::uuid(),
            'name' => 'Landlord 1',
            'email' => 'test@landord.com',
            'password' => Hash::make('111213')
        ]);

        $user->assignRole('admin');
        $landLord->assignRole('landlord');
    }
}
