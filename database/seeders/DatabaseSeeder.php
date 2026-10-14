<?php
namespace Database\Seeders;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\UserSeeder::class,
            \Database\Seeders\LevelsSeeder::class,
//             StudentUserSeeder::class, // Disabled - needs username field
//             DemoUsersByRoleSeeder::class, // Disabled - needs username field
//             \Database\Seeders\PermisosNotasSeeder::class, // Disabled - needs username field
        ]);
    }
}
