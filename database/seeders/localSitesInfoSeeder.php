<?php

namespace Database\Seeders;
use App\Models\LocalSitesInfo;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class localSitesInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $result = LocalSitesInfo::create([
            'site_name' => Str::random(10), 
            'username' => Str::random(10),
            'password' => '12345'
        ]);
    }
}
