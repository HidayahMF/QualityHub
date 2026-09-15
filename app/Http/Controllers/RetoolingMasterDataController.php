<?php

namespace App\Http\Controllers;

use App\Models\AxlePosition;
use App\Models\AxleType;
use App\Models\BrakePartNumber;
use App\Models\LineProduct;
use App\Models\ProductionLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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


        $query = ProductionLine::query()
            ->where('is_active', true)
            ->orderBy('code');


        if ($search !== null) {

            $escapedSearch =
                $this->escapeLike(
                    $search
                );

            $query->where(
                function (Builder $builder) use ($escapedSearch) {

                    $builder
                        ->where(
                            'code',
                            'like',
                            '%' . $escapedSearch . '%'
                        )
                        ->orWhere(
                            'name',
                            'like',
                            '%' . $escapedSearch . '%'
                        );
                }
            );
        }


        $results =
            $query
                ->limit(50)
                ->get([
                    'id',
                    'code',
                    'name',
                ])
                ->map(
                    fn (ProductionLine $line): array => [
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
     * Daftar Type / Casting aktif yang tersedia pada satu Line.
     *
     * Cycle Time berasal dari line_products, bukan dari tabel castings,
     * karena produk yang sama dapat memiliki Cycle Time berbeda di Line lain.
     */
    public function products(
        Request $request,
        ProductionLine $productionLine
    ): JsonResponse {

        $this->ensureAdmin();


        abort_unless(
            $productionLine->is_active,
            404
        );


        $search = $this->normalizeSearch(
            $request->query('q')
        );


        $query = LineProduct::query()
            ->with([
                'casting:id,code,name,is_active',
            ])
            ->where(
                'production_line_id',
                $productionLine->id
            )
            ->where(
                'is_active',
                true
            )
            ->whereHas(
                'casting',
                fn (Builder $builder) =>
                    $builder->where(
                        'is_active',
                        true
                    )
            )
            ->orderBy('casting_id');


        if ($search !== null) {

            $escapedSearch =
                $this->escapeLike(
                    $search
                );

            $query->whereHas(
                'casting',
                function (Builder $builder) use ($escapedSearch) {

                    $builder
                        ->where(
                            'code',
                            'like',
                            '%' . $escapedSearch . '%'
                        )
                        ->orWhere(
                            'name',
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
                    'production_line_id',
                    'casting_id',
                    'cycle_time',
                ])
                ->map(
                    function (LineProduct $lineProduct): array {

                        $casting =
                            $lineProduct->casting;

                        $cycleTime =
                            $this->formatCycleTime(
                                $lineProduct->cycle_time
                            );


                        return [
                            /*
                             * Select2 memakai casting_id sebagai value.
                             */
                            'id' =>
                                $casting->id,

                            'text' =>
                                $casting->code
                                . ' — CT '
                                . $cycleTime,

                            'casting_id' =>
                                $casting->id,

                            'code' =>
                                $casting->code,

                            'name' =>
                                $casting->name,

                            'cycle_time' =>
                                $cycleTime,

                            /*
                             * Berguna untuk debugging / integrasi frontend.
                             */
                            'line_product_id' =>
                                $lineProduct->id,

                            'production_line_id' =>
                                $lineProduct->production_line_id,
                        ];
                    }
                )
                ->values();


        return response()->json([
            'line' => [
                'id' =>
                    $productionLine->id,

                'code' =>
                    $productionLine->code,

                'name' =>
                    $productionLine->name,
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
