<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccommodationTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accommodation_types')->upsert(
            [
                ['id' => 1, 'fa_name' => 'اقامتگاه سنتی', 'en_name' => 'traditional residence', 'created_at' => '2026-02-21 12:02:09', 'updated_at' => '2026-02-22 10:42:22'],
                ['id' => 2, 'fa_name' => 'خانه مسافر', 'en_name' => 'traveler house', 'created_at' => '2026-02-21 12:02:09', 'updated_at' => '2026-02-22 10:42:38'],
                ['id' => 3, 'fa_name' => 'اقامتگاه بوم گردی', 'en_name' => 'ecotourism resorts', 'created_at' => '2026-02-21 12:02:09', 'updated_at' => '2026-02-22 10:42:53'],
                ['id' => 4, 'fa_name' => 'هتل', 'en_name' => 'hotel', 'created_at' => '2026-02-21 12:02:09', 'updated_at' => '2026-02-22 10:43:03'],
                ['id' => 5, 'fa_name' => 'هتل آپارتمان', 'en_name' => 'apartment hotel', 'created_at' => '2026-02-21 12:02:10', 'updated_at' => '2026-02-22 10:41:30'],
                ['id' => 6, 'fa_name' => 'مجتمع اقامتی', 'en_name' => 'residential complex', 'created_at' => '2026-02-21 12:02:10', 'updated_at' => '2026-02-22 10:41:47'],
                ['id' => 7, 'fa_name' => 'مجتمع گردشگری', 'en_name' => 'tourism complex', 'created_at' => '2026-02-21 12:02:10', 'updated_at' => '2026-02-22 10:42:08'],
                ['id' => 8, 'fa_name' => 'مهمانسرای ممتاز', 'en_name' => 'privilege inn', 'created_at' => '2026-02-21 12:02:11', 'updated_at' => '2026-02-22 10:41:05'],
                ['id' => 9, 'fa_name' => 'مهمانسرا', 'en_name' => 'inn', 'created_at' => '2026-02-21 12:02:11', 'updated_at' => '2026-02-22 10:41:17'],
                ['id' => 10, 'fa_name' => 'هتل بوتیک', 'en_name' => 'boutique', 'created_at' => '2026-02-21 12:02:12', 'updated_at' => '2026-02-22 10:40:24'],
                ['id' => 11, 'fa_name' => 'متل', 'en_name' => 'motel', 'created_at' => '2026-02-21 12:02:12', 'updated_at' => '2026-02-22 10:40:42'],
                ['id' => 12, 'fa_name' => 'سوئیت آپارتمانی', 'en_name' => 'apartment suite', 'created_at' => '2026-02-21 12:42:17', 'updated_at' => '2026-02-22 10:40:05'],
                ['id' => 13, 'fa_name' => 'هاستل', 'en_name' => 'hostel', 'created_at' => '2026-02-21 13:31:20', 'updated_at' => '2026-02-22 10:39:51'],
                ['id' => 14, 'fa_name' => 'مجتمع اقامتی ساحلی', 'en_name' => 'beach residential complex', 'created_at' => '2026-02-21 13:31:21', 'updated_at' => '2026-02-22 10:39:33'],
                ['id' => 15, 'fa_name' => 'واحد اقامتی', 'en_name' => 'residential unit', 'created_at' => '2026-02-21 13:31:25', 'updated_at' => '2026-02-22 10:39:04'],
                ['id' => 16, 'fa_name' => 'پانسیون', 'en_name' => 'pension', 'created_at' => '2026-02-21 13:31:38', 'updated_at' => '2026-02-22 10:38:28'],
            ],
            ['id'],
            ['fa_name', 'en_name', 'updated_at']
        );
    }
}
