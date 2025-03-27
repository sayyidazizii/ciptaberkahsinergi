<?php

namespace Database\Seeders;

use App\Models\CoreAnouncement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CoreAnouncement::factory(2)->create();
    }
}
