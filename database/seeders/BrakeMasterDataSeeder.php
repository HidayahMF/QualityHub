<?php

namespace Database\Seeders;

use App\Models\BrakePartNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrakeMasterDataSeeder extends Seeder
{
    private const PREFIX = 'A 4304200';

    public function run(): void
    {
        DB::transaction(function (): void {
            $variants = [
                ['variant' => '402 / 502', 'sort_order' => 10],
                ['variant' => '201 / 301', 'sort_order' => 20],
                ['variant' => '202 / 302', 'sort_order' => 30],
            ];

            foreach ($variants as $variant) {
                BrakePartNumber::query()->updateOrCreate(
                    [
                        'prefix' => self::PREFIX,
                        'variant' => $variant['variant'],
                    ],
                    [
                        'display_name' => self::PREFIX . ' ' . $variant['variant'],
                        'is_active' => true,
                        'sort_order' => $variant['sort_order'],
                    ]
                );
            }
        });
    }
}
