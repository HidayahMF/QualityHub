<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidLocalAiResponseException;
use App\Exceptions\LocalAiUnavailableException;
use App\Models\Document;
use App\Models\User;
use App\Services\AiDocumentCandidateBuilder;
use App\Services\AiSmartSearchService;
use App\Services\InputSanitizationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiSmartSearchController extends Controller
{
    public function __invoke(
        Request $request,
        InputSanitizationService $input,
        AiSmartSearchService $ai,
        AiDocumentCandidateBuilder $candidates
    ): JsonResponse {
        $request->merge([
            'query' => $input->search($request->input('query')),
        ]);

        $validated = $request->validate([
            'query' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();
        $query = $validated['query'];
        $fallbackQuery = mb_substr($query, 0, 160);
        $fallbackUrl = route(
            'documents.index',
            ['search' => $fallbackQuery],
            false
        );
        $startedAt = microtime(true);

        try {
            $knownValues = $candidates->knownValues();
            $referenceDate = now()->toDateString();
            $entities = $ai->parse(
                $query,
                $referenceDate,
                $knownValues
            );
            $candidateDocuments = $candidates->candidates(
                $user,
                $entities,
                $knownValues
            );
            $candidateIds = $candidateDocuments
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
            $aiResult = $ai->search(
                $query,
                $candidates->payload($candidateDocuments),
                $referenceDate,
                $knownValues
            );
            $rankedDocuments = $candidates->hydrateRankedResults(
                $user,
                $aiResult['results'],
                $candidateIds
            );

            if (($entities['sort'] ?? null) === 'latest') {
                usort(
                    $rankedDocuments,
                    function (array $left, array $right): int {
                        $leftDocument = $left['document'];
                        $rightDocument = $right['document'];
                        $leftDate = $leftDocument->document_date?->getTimestamp()
                            ?? $leftDocument->created_at?->getTimestamp()
                            ?? 0;
                        $rightDate = $rightDocument->document_date?->getTimestamp()
                            ?? $rightDocument->created_at?->getTimestamp()
                            ?? 0;

                        if ($leftDate !== $rightDate) {
                            return $rightDate <=> $leftDate;
                        }

                        return ($rightDocument->created_at?->getTimestamp() ?? 0)
                            <=> ($leftDocument->created_at?->getTimestamp() ?? 0);
                    }
                );
            }

            $matchedBy = $this->matchedBy($entities);
            $results = array_map(
                fn (array $ranked): array => $this->resultPayload(
                    $ranked['document'],
                    $ranked['semantic_score'],
                    $matchedBy
                ),
                $rankedDocuments
            );

            Log::info('Local AI Smart Search completed.', [
                'duration_ms' => (int) round(
                    (microtime(true) - $startedAt) * 1000
                ),
                'candidate_count' => count($candidateIds),
                'result_count' => count($results),
            ]);

            return response()->json([
                'available' => true,
                'mode' => 'local_ai',
                'fallback' => false,
                'query' => $query,
                'entities' => $entities,
                'count' => count($results),
                'results' => $results,
                'model' => $aiResult['model'],
                'message' => null,
                'fallback_url' => $fallbackUrl,
            ]);
        } catch (
            LocalAiUnavailableException
            | InvalidLocalAiResponseException
            | ConnectionException $exception
        ) {
            $fallbackDocuments = $candidates->keywordFallback(
                $user,
                $fallbackQuery
            );
            $matchedBy = ['Pencarian kata kunci lokal'];
            $results = $fallbackDocuments
                ->map(fn (Document $document): array => $this->resultPayload(
                    $document,
                    null,
                    $matchedBy
                ))
                ->values()
                ->all();

            Log::warning('Local AI Smart Search unavailable.', [
                'reason' => $exception::class,
                'duration_ms' => (int) round(
                    (microtime(true) - $startedAt) * 1000
                ),
                'result_count' => count($results),
            ]);

            return response()->json([
                'available' => false,
                'mode' => 'keyword_fallback',
                'fallback' => true,
                'query' => $query,
                'entities' => [],
                'count' => count($results),
                'results' => $results,
                'model' => null,
                'message' => 'AI Smart Search lokal sedang tidak tersedia. Pencarian kata kunci lokal tetap dapat digunakan.',
                'fallback_url' => $fallbackUrl,
            ]);
        }
    }

    /**
     * @param  list<string>  $matchedBy
     * @return array<string, mixed>
     */
    private function resultPayload(
        Document $document,
        ?float $semanticScore,
        array $matchedBy
    ): array {
        $metadata = $document->scanMetadata;
        $scanType = $metadata?->scan_type;

        $qcTypeLabel = match ($scanType) {
            'Retooling' => 'Instruksi Perubahan',
            'Laporan Hasil Produksi - Axle Assy' => 'Axle Assy',
            'Laporan Hasil Produksi - Brake Assy' => 'Brake Assy',
            default => null,
        };

        $qcDetail = match ($scanType) {
            'Retooling' => implode(
                ' → ',
                array_filter([
                    $metadata?->from_type,
                    $metadata?->to_type,
                ])
            ) ?: null,
            'Laporan Hasil Produksi - Axle Assy' => implode(
                ' • ',
                array_filter([
                    $metadata?->axle_assy_type,
                    $metadata?->axle_side,
                ])
            ) ?: null,
            'Laporan Hasil Produksi - Brake Assy' => $metadata?->brake_part_number
                ?: implode(
                    ' ',
                    array_filter([
                        $metadata?->brake_part_prefix,
                        $metadata?->brake_part_variant,
                    ])
                )
                ?: null,
            default => null,
        };

        return [
            'id' => (int) $document->id,
            'title' => $document->title,
            'document_code' => $document->document_code,
            'part_number' => $document->part_number,
            'file_name' => $document->file_name,
            'extension' => strtoupper($document->file_extension ?? 'FILE'),
            'category' => $document->category?->name,
            'department' => $document->category?->department?->name,
            'date' => $document->document_date
                ?->locale('id')
                ->translatedFormat('d M Y'),
            'scan_type' => $scanType,
            'qc_type_label' => $qcTypeLabel,
            'qc_detail' => $qcDetail,
            'production_date' => $metadata?->production_date
                ?->locale('id')
                ->translatedFormat('d M Y'),
            'current_revision' => (int) $document->current_revision,
            'revision_label' => 'Revisi ' . (int) $document->current_revision,
            'is_favorited' => (bool) $document->is_favorited,
            'url' => route('documents.show', $document),
            'semantic_score' => $semanticScore,
            'matched_by' => $matchedBy,
            'explanation' => $matchedBy === []
                ? null
                : 'Cocok dengan: ' . implode(' • ', $matchedBy),
        ];
    }

    /**
     * @param  array<string, mixed>  $entities
     * @return list<string>
     */
    private function matchedBy(array $entities): array
    {
        $matched = [];

        $documentType = match ($entities['document_type'] ?? null) {
            'retooling' => 'Retooling',
            'axle_assy' => 'Axle Assy',
            'brake_assy' => 'Brake Assy',
            default => null,
        };

        if ($documentType !== null) {
            $matched[] = $documentType;
        }

        foreach ([
            'line',
            'from_type',
            'to_type',
            'axle_type',
            'position',
            'brake_part_number',
        ] as $field) {
            if (($entities[$field] ?? null) !== null) {
                $matched[] = (string) $entities[$field];
            }
        }

        if (($entities['shift'] ?? null) !== null) {
            $matched[] = 'Shift ' . $entities['shift'];
        }

        if (($entities['date_from'] ?? null) !== null) {
            $dateLabel = $entities['date_from'];

            if (
                ($entities['date_to'] ?? null) !== null
                && $entities['date_to'] !== $entities['date_from']
            ) {
                $dateLabel .= ' s.d. ' . $entities['date_to'];
            }

            $matched[] = $dateLabel;
        } elseif (($entities['month'] ?? null) !== null) {
            $matched[] = 'Bulan ' . $entities['month']
                . (($entities['year'] ?? null) !== null
                    ? ' ' . $entities['year']
                    : '');
        } elseif (($entities['year'] ?? null) !== null) {
            $matched[] = 'Tahun ' . $entities['year'];
        }

        if (($entities['sort'] ?? null) === 'latest') {
            $matched[] = 'Terbaru';
        }

        return array_values(array_unique($matched));
    }
}
