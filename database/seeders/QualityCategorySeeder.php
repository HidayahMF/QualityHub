<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use Illuminate\Database\Seeder;

class QualityCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Quality Control Department
        |--------------------------------------------------------------------------
        |
        | Mengambil department QC. Kalau belum ada, otomatis dibuat.
        |
        */

        $department = Department::firstOrCreate(
            [
                'code' => 'QC',
            ],
            [
                'name' => 'Quality Control',
                'description' => 'Quality Control Department',
                'is_active' => true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Quality Control Document Categories
        |--------------------------------------------------------------------------
        */

        $categories = [
            [
                'name' => 'Inspection',
                'slug' => 'inspection',
                'description' => 'Dokumen dan data proses inspection Quality Control.',
            ],
            [
                'name' => 'Inspection Result',
                'slug' => 'inspection-result',
                'description' => 'Dokumen hasil pemeriksaan atau inspection result.',
            ],
            [
                'name' => 'Final Inspection',
                'slug' => 'final-inspection',
                'description' => 'Dokumen pemeriksaan final sebelum produk diproses lebih lanjut atau dikirim.',
            ],
            [
                'name' => 'Checksheet',
                'slug' => 'checksheet',
                'description' => 'Dokumen checksheet pemeriksaan Quality Control.',
            ],
            [
                'name' => 'Report',
                'slug' => 'report',
                'description' => 'Dokumen laporan dan rekap Quality Control.',
            ],
        ];


        foreach ($categories as $category) {

            Category::updateOrCreate(

                [
                    'department_id' => $department->id,
                    'slug' => $category['slug'],
                ],

                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ]

            );

        }
    }
}