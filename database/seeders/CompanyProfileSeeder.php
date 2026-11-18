<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (! CompanyProfile::query()->exists()) {
            CompanyProfile::create(CompanyProfile::defaults());
        }
    }
}
