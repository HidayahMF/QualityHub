<?php

namespace Database\Seeders;

use App\Models\AxlePosition;
use App\Models\AxleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AxleMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $types = [
                [
                    'code' => 'MODULE_ASSY',
                    'name' => 'Module Assy',
                    'sort_order' => 10,
                ],
                [
                    'code' => 'AXLE_ASSY',
                    'name' => 'Axle Assy',
                    'sort_order' => 20,
                ],
            ];

            foreach ($types as $type) {
                AxleType::query()->updateOrCreate(
                    ['code' => $type['code']],
                    [
                        'name' => $type['name'],
                        'is_active' => true,
                        'sort_order' => $type['sort_order'],
                    ]
                );
            }

            $positions = [
                [
                    'code' => 'LH',
                    'name' => 'LH',
                    'sort_order' => 10,
                ],
                [
                    'code' => 'RH',
                    'name' => 'RH',
                    'sort_order' => 20,
                ],
            ];

            foreach ($positions as $position) {
                AxlePosition::query()->updateOrCreate(
                    ['code' => $position['code']],
                    [
                        'name' => $position['name'],
                        'is_active' => true,
                        'sort_order' => $position['sort_order'],
                    ]
                );
            }
        });
    }
}
