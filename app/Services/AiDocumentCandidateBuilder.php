<?php

namespace App\Services;

use App\Exceptions\InvalidLocalAiResponseException;
use App\Models\AxlePosition;
use App\Models\AxleType;
use App\Models\BrakePartNumber;
use App\Models\Casting;
use App\Models\Document;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AiDocumentCandidateBuilder
{
    private const RETOOLING = 'Retooling';

    private const AXLE = 'Laporan Hasil Produksi - Axle Assy';

    private const BRAKE = 'Laporan Hasil Produksi - Brake Assy';

    /**
     * Dynamic business vocabulary sent to the deterministic local parser.
     *
     * @return array<string, list<array{value: string, aliases: list<string>}>>
     */
    public function knownValues(): array
    {
        $lines = ProductionLine::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn (ProductionLine $line): array => $this->knownValue(
                $line->code,
                [
                    $line->name,
                    Str::replace('_', ' ', $line->code),
                    preg_replace('/^LINE[_ -]?(\d+)$/i', 'line $1', $line->code),
                ]
            ))
            ->values()
            ->all();

        $products = Casting::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn (Casting $casting): array => $this->knownValue(
                $casting->code,
                [
                    $casting->name,
                    Str::replace('_', ' ', $casting->code),
                ]
            ))
            ->values()
            ->all();

        $axleTypes = AxleType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn (AxleType $type): array => $this->knownValue(
                $type->name,
                [
                    $type->code,
                    Str::replace('_', ' ', $type->code),
                ]
            ))
            ->values()
            ->all();

        $positions = AxlePosition::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn (AxlePosition $position): array => $this->knownValue(
                $position->code,
                [$position->name]
            ))
            ->values()
            ->all();

        $brakePartNumbers = BrakePartNumber::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get(['prefix', 'variant', 'display_name'])
            ->map(fn (BrakePartNumber $part): array => $this->knownValue(
                $part->display_name,
                [
                    $part->variant,
                    preg_replace('/\s*\/\s*/', ' ', $part->variant),
                    preg_replace('/\s*\/\s*/', ' ', $part->display_name),
                ]
            ))
            ->values()
            ->all();

        // Augment vocabulary with historical values observed in document_scan_metadata.
        // This ensures searches over archived documents still recognise legacy master values.
        try {
            $historical = collect([]);

            $historicalFrom = DB::table('document_scan_metadata')
                ->whereNotNull('from_type')
                ->where('from_type', '<>', '')
                ->distinct()
                ->pluck('from_type');

            $historicalTo = DB::table('document_scan_metadata')
                ->whereNotNull('to_type')
                ->where('to_type', '<>', '')
                ->distinct()
                ->pluck('to_type');

            $historicalProducts = $historicalFrom->merge($historicalTo)->map(fn ($v) => trim((string) $v))->filter()->unique()->values()->all();

            foreach ($historicalProducts as $hp) {
                $historical->push($this->knownValue($hp, []));
            }

            $historicalAxles = DB::table('document_scan_metadata')
                ->whereNotNull('axle_assy_type')
                ->where('axle_assy_type', '<>', '')
                ->distinct()
                ->pluck('axle_assy_type')
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($historicalAxles as $h) {
                $historical->push($this->knownValue($h, []));
            }

            $historicalPositions = DB::table('document_scan_metadata')
                ->whereNotNull('axle_side')
                ->where('axle_side', '<>', '')
                ->distinct()
                ->pluck('axle_side')
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($historicalPositions as $p) {
                $historical->push($this->knownValue($p, []));
            }

            $historicalBrake = DB::table('document_scan_metadata')
                ->whereNotNull('brake_part_number')
                ->where('brake_part_number', '<>', '')
                ->distinct()
                ->pluck('brake_part_number')
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($historicalBrake as $b) {
                $historical->push($this->knownValue($b, []));
            }

            $historicalLines = DB::table('document_scan_metadata')
                ->whereNotNull('line')
                ->where('line', '<>', '')
                ->distinct()
                ->pluck('line')
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($historicalLines as $l) {
                $historical->push($this->knownValue($l, []));
            }

            // Merge deduplicating case-insensitively while preserving master order first.
            $mergeGroup = function (array $target, array $extra): array {
                $seen = [];
                foreach ($target as $item) {
                    $seen[mb_strtolower((string) ($item['value'] ?? ''))] = true;
                }

                foreach ($extra as $item) {
                    $key = mb_strtolower((string) ($item['value'] ?? ''));

                    if ($key === '') {
                        continue;
                    }

                    if (! isset($seen[$key])) {
                        $target[] = $item;
                        $seen[$key] = true;
                    }
                }

                return $target;
            };

            $products = $mergeGroup($products, $historical->whereNotNull()->values()->all());
        } catch (\Throwable $e) {
            // If DB read fails for any reason, fall back to master-only vocabulary.
        }

        return [
            'lines' => $lines,
            'products' => $products,
            'axle_types' => $axleTypes,
            'positions' => $positions,
            'brake_part_numbers' => $brakePartNumbers,
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @param  array<string, list<array{value: string, aliases: list<string>}>>  $knownValues
     * @return Collection<int, Document>
     */
    public function candidates(
        User $user,
        array $entities,
        array $knownValues
    ): Collection {
        $this->ensureKnownEntities($entities, $knownValues);

        $query = $this->authorizedQuery($user);

        $documentType = match ($entities['document_type'] ?? null) {
            'retooling' => self::RETOOLING,
            'axle_assy' => self::AXLE,
            'brake_assy' => self::BRAKE,
            default => null,
        };

        if ($documentType !== null) {
            $query->whereHas(
                'scanMetadata',
                fn (Builder $metadataQuery) => $metadataQuery->where(
                    'scan_type',
                    $documentType
                )
            );
        }

        $metadataFields = [
            'line' => 'line',
            'shift' => 'shift',
            'from_type' => 'from_type',
            'to_type' => 'to_type',
            'axle_type' => 'axle_assy_type',
            'position' => 'axle_side',
            'brake_part_number' => 'brake_part_number',
        ];

        foreach ($metadataFields as $entity => $column) {
            $value = $entities[$entity] ?? null;

            if ($value === null) {
                continue;
            }

            $query->whereHas(
                'scanMetadata',
                fn (Builder $metadataQuery) => $metadataQuery->where(
                    $column,
                    $value
                )
            );
        }

        if (($entities['date_from'] ?? null) !== null) {
            $query->whereDate('document_date', '>=', $entities['date_from']);
        }

        if (($entities['date_to'] ?? null) !== null) {
            $query->whereDate('document_date', '<=', $entities['date_to']);
        }

        if (
            ($entities['date_from'] ?? null) === null
            && ($entities['date_to'] ?? null) === null
        ) {
            if (($entities['month'] ?? null) !== null) {
                $query->whereMonth('document_date', (int) $entities['month']);
            }

            if (($entities['year'] ?? null) !== null) {
                $query->whereYear('document_date', (int) $entities['year']);
            }
        }

        if (($entities['sort'] ?? null) === 'latest') {
            $query
                ->orderByDesc('document_date')
                ->orderByDesc('created_at');
        } else {
            $query->latest('created_at');
        }

        $limit = ($entities['sort'] ?? null) === 'latest'
            ? min(
                $this->candidateLimit(),
                max(1, min(50, (int) config('ai.smart_search.top_k', 20)))
            )
            : $this->candidateLimit();

        return $query
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return list<array<string, mixed>>
     */
    public function payload(Collection $documents): array
    {
        return $documents
            ->map(fn (Document $document): array => $this->candidatePayload($document))
            ->values()
            ->all();
    }

    /**
     * Re-query ranked IDs through the same active-document scope. The local AI
     * response therefore cannot introduce an archived or otherwise unknown ID.
     *
     * @param  list<array{document_id: int, semantic_score: float}>  $rankedResults
     * @param  list<int>  $candidateIds
     * @return list<array{document: Document, semantic_score: float}>
     */
    public function hydrateRankedResults(
        User $user,
        array $rankedResults,
        array $candidateIds
    ): array {
        $allowedIds = array_fill_keys($candidateIds, true);
        $scoresById = [];
        $orderedIds = [];

        foreach ($rankedResults as $result) {
            $documentId = $result['document_id'];

            if (
                ! isset($allowedIds[$documentId])
                || isset($scoresById[$documentId])
            ) {
                continue;
            }

            $orderedIds[] = $documentId;
            $scoresById[$documentId] = $result['semantic_score'];
        }

        if ($orderedIds === []) {
            return [];
        }

        $documents = $this->authorizedQuery($user)
            ->whereIn('documents.id', $orderedIds)
            ->get()
            ->keyBy('id');

        $hydrated = [];

        foreach ($orderedIds as $documentId) {
            $document = $documents->get($documentId);

            if ($document === null) {
                continue;
            }

            $hydrated[] = [
                'document' => $document,
                'semantic_score' => $scoresById[$documentId],
            ];
        }

        return $hydrated;
    }

    /**
     * Existing local LIKE behavior used only when the Python service is not
     * available. The normal Documents search endpoint remains unchanged.
     *
     * @return Collection<int, Document>
     */
    public function keywordFallback(User $user, string $search): Collection
    {
        $likeSearch = app(InputSanitizationService::class)
            ->escapeLike($search);

        return $this->authorizedQuery($user)
            ->where(function (Builder $searchQuery) use ($likeSearch): void {
                $searchQuery
                    ->where('title', 'like', "%{$likeSearch}%")
                    ->orWhere('file_name', 'like', "%{$likeSearch}%")
                    ->orWhere('document_code', 'like', "%{$likeSearch}%")
                    ->orWhere('part_number', 'like', "%{$likeSearch}%")
                    ->orWhere('keywords', 'like', "%{$likeSearch}%")
                    ->orWhere('description', 'like', "%{$likeSearch}%")
                    ->orWhereHas(
                        'category',
                        fn (Builder $categoryQuery) => $categoryQuery
                            ->where('name', 'like', "%{$likeSearch}%")
                            ->orWhere('slug', 'like', "%{$likeSearch}%")
                    )
                    ->orWhereHas(
                        'category.department',
                        fn (Builder $departmentQuery) => $departmentQuery
                            ->where('name', 'like', "%{$likeSearch}%")
                            ->orWhere('code', 'like', "%{$likeSearch}%")
                    )
                    ->orWhereHas(
                        'scanMetadata',
                        function (Builder $metadataQuery) use ($likeSearch): void {
                            $metadataQuery->where(function (Builder $fieldQuery) use ($likeSearch): void {
                                $fields = [
                                    'scan_type',
                                    'shift',
                                    'line',
                                    'from_type',
                                    'to_type',
                                    'axle_assy_type',
                                    'axle_side',
                                    'brake_part_prefix',
                                    'brake_part_variant',
                                    'brake_part_number',
                                ];

                                foreach ($fields as $index => $field) {
                                    $method = $index === 0 ? 'where' : 'orWhere';
                                    $fieldQuery->{$method}(
                                        $field,
                                        'like',
                                        "%{$likeSearch}%"
                                    );
                                }
                            });
                        }
                    );
            })
            ->latest('created_at')
            ->limit(
                max(
                    1,
                    min(50, (int) config('ai.smart_search.top_k', 20))
                )
            )
            ->get();
    }

    /**
     * @return Builder<Document>
     */
    private function authorizedQuery(User $user): Builder
    {
        /*
         * Existing QualityHub authorization exposes every active document to
         * every authenticated active account. Keep that exact boundary here.
         */
        return Document::query()
            ->with([
                'category.department',
                'scanMetadata',
                'retooling.productionLine',
                'retooling.fromCasting',
                'retooling.toCasting',
                'axleProduction',
                'brakeProduction',
            ])
            ->withExists([
                'favorites as is_favorited' => fn ($favoriteQuery) => $favoriteQuery->where(
                    'user_id',
                    $user->id
                ),
            ])
            ->active();
    }

    /**
     * @return array<string, mixed>
     */
    private function candidatePayload(Document $document): array
    {
        $metadata = $document->scanMetadata;
        $scanType = $metadata?->scan_type;

        $line = $metadata?->line;
        $shift = $metadata?->shift !== null
            ? (int) $metadata->shift
            : null;
        $fromType = $metadata?->from_type;
        $toType = $metadata?->to_type;
        $fromCycleTime = null;
        $toCycleTime = null;
        $axleType = $metadata?->axle_assy_type;
        $position = $metadata?->axle_side;
        $brakePartNumber = $metadata?->brake_part_number;

        if ($scanType === self::RETOOLING && $document->retooling !== null) {
            $retooling = $document->retooling;
            $line ??= $retooling->productionLine?->code;
            $shift = (int) $retooling->shift;
            $fromType ??= $retooling->fromCasting?->code
                ?? $retooling->fromCasting?->name;
            $toType ??= $retooling->toCasting?->code
                ?? $retooling->toCasting?->name;
            $fromCycleTime = $retooling->from_cycle_time !== null
                ? (float) $retooling->from_cycle_time
                : null;
            $toCycleTime = $retooling->to_cycle_time !== null
                ? (float) $retooling->to_cycle_time
                : null;
        }

        if ($scanType === self::AXLE && $document->axleProduction !== null) {
            $axle = $document->axleProduction;
            $line = $axle->snapshot_line_code
                ?? $axle->snapshot_line_name;
            $shift = $axle->shift;
            $axleType = $axle->snapshot_axle_type_name
                ?? $axle->snapshot_axle_type_code;
            $position = $axle->snapshot_position_name
                ?? $axle->snapshot_position_code;
        }

        if ($scanType === self::BRAKE && $document->brakeProduction !== null) {
            $brake = $document->brakeProduction;
            $line = $brake->snapshot_line_code
                ?? $brake->snapshot_line_name;
            $shift = $brake->shift;
            $brakePartNumber = $brake->snapshot_display_name;
        }

        if (! in_array($shift, [1, 2, 3], true)) {
            $shift = null;
        }

        return [
            'document_id' => (int) $document->id,
            'title' => mb_substr($document->title, 0, 255),
            'file_name' => mb_substr($document->file_name, 0, 255),
            'document_code' => $this->nullableText($document->document_code),
            'part_number' => $this->nullableText($document->part_number),
            'keywords' => $this->nullableText($document->keywords, 2000),
            'document_date' => $document->document_date?->format('Y-m-d'),
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
            'department' => $this->nullableText(
                $document->category?->department?->name
            ),
            'category' => $this->nullableText($document->category?->name),
            'scan_type' => $this->nullableText($scanType),
            'line' => $this->nullableText($line),
            'shift' => $shift,
            'from_type' => $this->nullableText($fromType),
            'to_type' => $this->nullableText($toType),
            'from_cycle_time' => $this->nullableCycleTime($fromCycleTime),
            'to_cycle_time' => $this->nullableCycleTime($toCycleTime),
            'axle_type' => $this->nullableText($axleType),
            'position' => $this->nullableText($position),
            'brake_part_number' => $this->nullableText($brakePartNumber),
        ];
    }

    /**
     * @param  list<string|null>  $aliases
     * @return array{value: string, aliases: list<string>}
     */
    private function knownValue(string $value, array $aliases): array
    {
        $seen = [mb_strtolower($value) => true];
        $cleanAliases = [];

        foreach ($aliases as $alias) {
            if (! is_string($alias)) {
                continue;
            }

            $alias = trim(preg_replace('/\s+/u', ' ', $alias) ?? '');
            $key = mb_strtolower($alias);

            if ($alias === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $cleanAliases[] = $alias;
        }

        return [
            'value' => $value,
            'aliases' => $cleanAliases,
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @param  array<string, list<array{value: string, aliases: list<string>}>>  $knownValues
     */
    private function ensureKnownEntities(array $entities, array $knownValues): void
    {
        $entityGroups = [
            'line' => 'lines',
            'from_type' => 'products',
            'to_type' => 'products',
            'axle_type' => 'axle_types',
            'position' => 'positions',
            'brake_part_number' => 'brake_part_numbers',
        ];

        foreach ($entityGroups as $entity => $group) {
            $value = $entities[$entity] ?? null;

            if ($value === null) {
                continue;
            }

            $known = collect($knownValues[$group] ?? [])
                ->contains(
                    fn (array $item): bool => mb_strtolower($item['value'])
                        === mb_strtolower((string) $value)
                );

            if (! $known) {
                throw new InvalidLocalAiResponseException(
                    "Local AI returned an unknown {$entity} entity."
                );
            }
        }
    }

    private function candidateLimit(): int
    {
        return max(
            1,
            min(
                250,
                (int) config('ai.smart_search.candidate_limit', 250)
            )
        );
    }

    private function nullableText(mixed $value, int $maxLength = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : mb_substr($value, 0, $maxLength);
    }

    private function nullableCycleTime(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (float) $value;

        return is_finite($value) && $value >= 0
            ? $value
            : null;
    }
}
