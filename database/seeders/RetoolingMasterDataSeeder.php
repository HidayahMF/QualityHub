<?php

namespace Database\Seeders;

use App\Models\Casting;
use App\Models\LineProduct;
use App\Models\ProductionLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetoolingMasterDataSeeder extends Seeder
{
    /**
     * Dummy master data Retooling untuk development.
     *
     * Data ini mengikuti contoh dari kebutuhan IT BMC.
     * Saat master resmi tersedia, isi dummy dapat diganti tanpa mengubah
     * struktur tabel dan relasi aplikasi.
     */
    public function run(): void
    {
        DB::transaction(function (): void {

            /*
            |--------------------------------------------------------------------------
            | Production Lines
            |--------------------------------------------------------------------------
            */

            $lineData = [
                'LINE_1' => 'Line 1',
                'LINE_2' => 'Line 2',
                'LINE_3' => 'Line 3',
            ];


            $lines = [];

            foreach ($lineData as $code => $name) {

                $lines[$code] =
                    ProductionLine::query()
                        ->updateOrCreate(
                            [
                                'code' => $code,
                            ],
                            [
                                'name' => $name,
                                'is_active' => true,
                            ]
                        );
            }


            /*
            |--------------------------------------------------------------------------
            | Castings / Products
            |--------------------------------------------------------------------------
            */

            $castingCodes = [
                'product_A',
                'product_B',
                'product_C',
                'product_D',
                'product_E',
                'product_F',
                'product_Z',
            ];


            $castings = [];

            foreach ($castingCodes as $code) {

                $castings[$code] =
                    Casting::query()
                        ->updateOrCreate(
                            [
                                'code' => $code,
                            ],
                            [
                                'name' =>
                                    str_replace(
                                        '_',
                                        ' ',
                                        $code
                                    ),

                                'is_active' =>
                                    true,
                            ]
                        );
            }


            /*
            |--------------------------------------------------------------------------
            | Line Product + Cycle Time
            |--------------------------------------------------------------------------
            */

            $lineProducts = [
                ['LINE_1', 'product_A', 20],
                ['LINE_1', 'product_B', 24],
                ['LINE_1', 'product_C', 30],

                ['LINE_2', 'product_A', 25],
                ['LINE_2', 'product_B', 40],
                ['LINE_2', 'product_E', 22],
                ['LINE_2', 'product_F', 25],

                ['LINE_3', 'product_B', 30],
                ['LINE_3', 'product_A', 25],
                ['LINE_3', 'product_D', 40],
                ['LINE_3', 'product_Z', 40],
            ];


            foreach ($lineProducts as [
                $lineCode,
                $castingCode,
                $cycleTime,
            ]) {

                LineProduct::query()
                    ->updateOrCreate(
                        [
                            'production_line_id' =>
                                $lines[$lineCode]->id,

                            'casting_id' =>
                                $castings[$castingCode]->id,
                        ],
                        [
                            'cycle_time' =>
                                $cycleTime,

                            'is_active' =>
                                true,
                        ]
                    );
            }
        });
    }
}
