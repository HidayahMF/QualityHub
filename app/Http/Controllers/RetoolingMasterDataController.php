<?php

namespace App\Http\Controllers;

use App\Models\AxlePosition;
use App\Models\AxleType;
use App\Models\BrakePartNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RetoolingMasterDataController extends Controller
{
    /**
     * Daftar Line aktif untuk Select2 Retooling.
     *
     * Response:
     * {
     *   "results": [
     *     {
     *       "id": 1,
     *       "text": "LINE_1 — Line 1",
     *       "code": "LINE_1",
     *       "name": "Line 1"
     *     }
     *   ]
     * }
     */
    public function lines(
        Request $request
    ): JsonResponse {

        $this->ensureAdmin();

        $search = $this->normalizeSearch(
            $request->query('q')
        );


        // Line produksi berasal dari database MTC, bukan master lokal QualityHub.
        $query = DB::table('mtc.LINE')
            ->select([
                'KODE_LN as id',
                DB::raw('COALESCE(NULLIF(kd_ln, \'\'), NAMA_LN) as code'),
                'NAMA_LN as name',
            ])
            ->where(function ($builder) {
                $builder
                    ->whereNotNull('kd_ln')
                    ->orWhereNotNull('NAMA_LN');
            })
            ->orderBy('KODE_LN');


        if ($search !== null) {

            $escapedSearch =
                $this->escapeLike(
                    $search
                );

            $query->where(
                function (Builder $builder) use ($escapedSearch) {

                    $builder
                        ->where('kd_ln', 'like', '%' . $escapedSearch . '%')
                        ->orWhere('NAMA_LN', 'like', '%' . $escapedSearch . '%');
                }
            );
        }


        $results =
            $query
                ->limit(50)
                ->get()
                ->map(
                    fn (object $line): array => [
                        'id' =>
                            $line->id,

                        'text' =>
                            $line->code
                            . (
                                trim((string) $line->name) !== ''
                                && $line->name !== $line->code
                                    ? ' — ' . $line->name
                                    : ''
                            ),

                        'code' =>
                            $line->code,

                        'name' =>
                            $line->name,
                    ]
                )
                ->values();


        return response()->json([
            'results' => $results,
        ]);
    }


    /**
     * Daftar produk aktif pada satu Line dari master PPIC.
     */
    public function products(
        Request $request,
        int $productionLine
    ): JsonResponse {

        $this->ensureAdmin();


        $lineExists = DB::table('mtc.LINE')
            ->where('KODE_LN', $productionLine)
            ->exists();

        abort_unless($lineExists, 404);

        $line = DB::table('mtc.LINE')
            ->where('KODE_LN', $productionLine)
            ->first([
                'KODE_LN as id',
                DB::raw("COALESCE(NULLIF(kd_ln, ''), NAMA_LN) as code"),
                'NAMA_LN as name',
            ]);


        $search = $this->normalizeSearch(
            $request->query('q')
        );


        $query = DB::table('ppic.LINEprd as product')
            ->join('mtc.LINE as line', 'line.KODE_LN', '=', 'product.KODE_LN')
            ->where('product.KODE_LN', $productionLine)
            ->where('product.AKTIF', 1)
            ->whereNotNull('product.KODE_CST')
            ->where('product.KODE_CST', '<>', '')
            ->select([
                'product.KODE_CST as id',
                'product.KODE_CST as code',
                'product.NAMA_CST as name',
                'product.CT as cycle_time',
                'product.KODE_LN as production_line_id',
            ])
            ->orderBy('product.KODE_CST')
            ->distinct();


        if ($search !== null) {

            $escapedSearch =
                $this->escapeLike(
                    $search
                );

            $query->where(function (Builder $builder) use ($escapedSearch) {
                $builder
                    ->where('product.KODE_CST', 'like', '%' . $escapedSearch . '%')
                    ->orWhere('product.NAMA_CST', 'like', '%' . $escapedSearch . '%');
            });
        }


        $results =
            $query
                ->limit(100)
                ->get()
                ->map(
                    function (object $product): array {
                        $cycleTime =
                            $this->formatCycleTime(
                                $product->cycle_time
                            );


                        return [
                            /*
                             * Frontend memakai KODE_CST sebagai value produk.
                             */
                            'id' => $product->id,

                            'text' =>
                                $product->code
                                . ' — '
                                . $product->name
                                . ' — CT '
                                . $cycleTime,

                            'casting_id' => $product->id,

                            'code' => $product->code,

                            'name' => $product->name,

                            'cycle_time' =>
                                $cycleTime,

                            /*
                             * Berguna untuk debugging / integrasi frontend.
                             */
                            'line_product_id' => $product->id,

                            'production_line_id' =>
                                $product->production_line_id,
                        ];
                    }
                )
                ->values();


        return response()->json([
            'line' => [
                    'id' => $line->id,
                    'code' => $line->code,
                    'name' => $line->name,
                ],

            'results' =>
                $results,
        ]);
    }


    /**
     * Daftar Type Axle aktif untuk workflow Axle Assy.
     */
    public function axleTypes(): JsonResponse
    {
        $this->ensureAdmin();


        $results =
            AxleType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                ])
                ->map(
                    fn (AxleType $axleType): array => [
                        'id' =>
                            $axleType->id,

                        'text' =>
                            $axleType->name,

                        'code' =>
                            $axleType->code,

                        'name' =>
                            $axleType->name,
                    ]
                )
                ->values();


        return response()->json([
            'results' => $results,
        ]);
    }


    /**
     * Daftar Posisi Axle aktif untuk workflow Axle Assy.
     */
    public function axlePositions(): JsonResponse
    {
        $this->ensureAdmin();


        $results =
            AxlePosition::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get([
                    'id',
                    'code',
                    'name',
                ])
                ->map(
                    fn (AxlePosition $axlePosition): array => [
                        'id' =>
                            $axlePosition->id,

                        'text' =>
                            $axlePosition->name,

                        'code' =>
                            $axlePosition->code,

                        'name' =>
                            $axlePosition->name,
                    ]
                )
                ->values();


        return response()->json([
            'results' => $results,
        ]);
    }


    /**
     * Daftar Part Number Brake aktif untuk workflow Brake Assy.
     */
    public function brakePartNumbers(
        Request $request
    ): JsonResponse {
        $this->ensureAdmin();


        $search = $this->normalizeSearch(
            $request->query('q')
        );


        $query = BrakePartNumber::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('prefix')
            ->orderBy('variant');


        if ($search !== null) {

            $escapedSearch =
                $this->escapeLike(
                    $search
                );

            $query->where(
                function (Builder $builder) use ($escapedSearch) {

                    $builder
                        ->where(
                            'prefix',
                            'like',
                            '%' . $escapedSearch . '%'
                        )
                        ->orWhere(
                            'variant',
                            'like',
                            '%' . $escapedSearch . '%'
                        )
                        ->orWhere(
                            'display_name',
                            'like',
                            '%' . $escapedSearch . '%'
                        );
                }
            );
        }


        $results =
            $query
                ->limit(100)
                ->get([
                    'id',
                    'prefix',
                    'variant',
                    'display_name',
                ])
                ->map(
                    fn (BrakePartNumber $partNumber): array => [
                        'id' =>
                            $partNumber->id,

                        'text' =>
                            $partNumber->display_name,

                        'prefix' =>
                            $partNumber->prefix,

                        'variant' =>
                            $partNumber->variant,

                        'display_name' =>
                            $partNumber->display_name,
                    ]
                )
                ->values();


        return response()->json([
            'results' => $results,
        ]);
    }


    /**
     * Search Select2 dinormalisasi server-side.
     */
    private function normalizeSearch(
        mixed $value
    ): ?string {

        if ($value === null) {
            return null;
        }


        $value =
            preg_replace(
                '/[\x00-\x1F\x7F]/u',
                '',
                (string) $value
            ) ?? '';


        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            ) ?? '';


        $value =
            trim(
                $value
            );


        if ($value === '') {
            return null;
        }


        /*
         * Batasi panjang pencarian supaya endpoint ringan.
         */
        return mb_substr(
            $value,
            0,
            100
        );
    }


    /**
     * Escape wildcard LIKE supaya input "%" atau "_" tidak berubah menjadi
     * wildcard bebas.
     */
    private function escapeLike(
        string $value
    ): string {

        return str_replace(
            [
                '\\',
                '%',
                '_',
            ],
            [
                '\\\\',
                '\\%',
                '\\_',
            ],
            $value
        );
    }


    private function formatCycleTime(
        mixed $value
    ): string {

        $number =
            number_format(
                (float) $value,
                2,
                '.',
                ''
            );


        return rtrim(
            rtrim(
                $number,
                '0'
            ),
            '.'
        );
    }


    /**
     * Scan / Capture saat ini memang hanya Admin / Super Admin.
     * Endpoint master Retooling mengikuti otorisasi yang sama.
     */
    private function ensureAdmin(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAdmin(),
            403
        );
    }
}
