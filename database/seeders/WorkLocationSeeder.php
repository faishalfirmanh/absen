<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkLocation;
class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $work = [
            [
                'location_name' => 'An Namiroh',
                'latitude' => 112.54197029906528,
                'longitude' => -7.5100300225165535,
                'radius_meters' => 45,
                'address' => 'Jl. Gajah Mada No.10/03, Menanggal, Kec. Mojosari, Kabupaten Mojokerto, Jawa Timur 61382',
                'is_active' => true,
            ],
            [
                'location_name' => 'ZamZami',
                'latitude' => -7.511035410671308,
                'longitude' => 112.54461684629327,
                'radius_meters' => 45,
                'address' => 'Krawengan, Menanggal, Kec. Mojosari, Kabupaten Mojokerto, Jawa Timur 61382',
                'is_active' => true,
            ],
            [
                'location_name' => 'Rihlah Saidah',
                'latitude' => -7.511358768208762,
                'longitude' => 112.54557957833812,
                'radius_meters' => 45,
                'address' => 'Jl. R.A Basuni No.43, Jampirogo, Kec. Sooko, Kabupaten Mojokerto, Jawa Timur 61361',
                'is_active' => true,
            ]
        ];

        foreach ($work as $work_Location) {
            WorkLocation::create($work_Location);
        }
    }
}
