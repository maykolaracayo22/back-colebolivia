<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LevelsSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Inicial', 'slug' => Str::slug('Inicial')],
            ['name' => 'Primaria', 'slug' => Str::slug('Primaria')],
            ['name' => 'Secundaria', 'slug' => Str::slug('Secundaria')],
        ];

        foreach ($levels as $lvl) {
            DB::table('levels')->updateOrInsert(['slug' => $lvl['slug']], $lvl + ['description' => null]);
        }
    }
}
