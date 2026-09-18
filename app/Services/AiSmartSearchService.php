<?php

namespace App\Services;

use App\Exceptions\InvalidLocalAiResponseException;
use App\Exceptions\LocalAiUnavailableException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AiSmartSearchService
{
    /**
     * @param  array<string, list<array{value: string, aliases: list<string>}>>  $knownValues
     * @return array<string, mixed>
     */
    public function parse(
        string $query,
        string $referenceDate,
        array $knownValues
    ): array {
        $response = $this->request()->post('/parse', [
            'query' => $query,
            'reference_date' => $referenceDate,
            'known_values' => $knownValues,
        ]);

        if (! $response->successful()) {
            throw new LocalAiUnavailableException(
                'Local AI parse request failed with status ' . $response->status() . '.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new InvalidLocalAiResponseException(
                'Local AI parse response is not a JSON object.'
            );
        }

        return $this->validateEntitiesPayload($payload);
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @param  array<string, list<array{value: string, aliases: list<string>}>>  $knownValues
     * @return array{model: string, mode: string, results: list<array{document_id: int, semantic_score: float}>}
     */
    public function search(
        string $query,
        array $candidates,
        string $referenceDate,
        array $knownValues
    ): array {
        $topK = max(
            1,
            min(
                50,
                (int) config('ai.smart_search.top_k', 20)
            )
        );

        $response = $this->request()->post('/search', [
            'query' => $query,
            'candidates' => $candidates,
            'top_k' => $topK,
            'reference_date' => $referenceDate,
            'known_values' => $knownValues,
        ]);

        if (! $response->successful()) {
            throw new LocalAiUnavailableException(
                'Local AI search request failed with status ' . $response->status() . '.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new InvalidLocalAiResponseException(
                'Local AI search response is not a JSON object.'
            );
        }

        if (($payload['intent'] ?? null) !== 'search_document') {
            throw new InvalidLocalAiResponseException(
                'Local AI search response has an invalid intent.'
            );
        }

        if (($payload['mode'] ?? null) !== 'local') {
            throw new InvalidLocalAiResponseException(
                'Local AI search response does not declare local mode.'
            );
        }

        $model = $payload['model'] ?? null;
        $results = $payload['results'] ?? null;

        if (
            ! is_string($model)
            || $model === ''
            || mb_strlen($model) > 160
            || str_starts_with($model, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $model)
            || preg_match('/[\x00-\x1F\x7F]/', $model)
            || ! is_array($results)
            || count($results) > $topK
        ) {
            throw new InvalidLocalAiResponseException(
                'Local AI search response is incomplete.'
            );
        }

        $validatedResults = [];

        foreach ($results as $result) {
            if (! is_array($result)) {
                throw new InvalidLocalAiResponseException(
                    'Local AI result item is invalid.'
                );
            }

            $documentId = $result['document_id'] ?? null;
            $semanticScore = $result['semantic_score'] ?? null;

            if (
                ! is_int($documentId)
                || $documentId < 1
                || ! is_numeric($semanticScore)
            ) {
                throw new InvalidLocalAiResponseException(
                    'Local AI result fields are invalid.'
                );
            }

            $semanticScore = (float) $semanticScore;

            if (
                ! is_finite($semanticScore)
                || $semanticScore < -1.0
                || $semanticScore > 1.0
            ) {
                throw new InvalidLocalAiResponseException(
                    'Local AI semantic score is outside the cosine range.'
                );
            }

            $validatedResults[] = [
                'document_id' => $documentId,
                'semantic_score' => $semanticScore,
            ];
        }

        return [
            'model' => $model,
            'mode' => 'local',
            'results' => $validatedResults,
        ];
    }

    private function request(): PendingRequest
    {
        if (! config('ai.smart_search.enabled', false)) {
            throw new LocalAiUnavailableException(
                'Local AI Smart Search is disabled.'
            );
        }

        $baseUrl = rtrim(
            (string) config('ai.smart_search.url', ''),
            '/'
        );

        if (! $this->isLoopbackUrl($baseUrl)) {
            throw new LocalAiUnavailableException(
                'Local AI URL must use an HTTP loopback address.'
            );
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(
                max(
                    0.1,
                    (float) config('ai.smart_search.connect_timeout', 1)
                )
            )
            ->timeout(
                max(
                    0.5,
                    (float) config('ai.smart_search.timeout', 5)
                )
            );
    }

    private function isLoopbackUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));

        $path = (string) ($parts['path'] ?? '');
        $hasDisallowedComponent = isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment']);

        return $scheme === 'http'
            && in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
            && ($path === '' || $path === '/')
            && ! $hasDisallowedComponent;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateEntitiesPayload(array $payload): array
    {
        if (($payload['intent'] ?? null) !== 'search_document') {
            throw new InvalidLocalAiResponseException(
                'Local AI parse response has an invalid intent.'
            );
        }

        $entities = $payload['entities'] ?? null;

        if (! is_array($entities)) {
            throw new InvalidLocalAiResponseException(
                'Local AI parse response has no entities object.'
            );
        }

        $stringFields = [
            'line',
            'from_type',
            'to_type',
            'axle_type',
            'position',
            'brake_part_number',
        ];

        foreach ($stringFields as $field) {
            $value = $entities[$field] ?? null;

            if (
                $value !== null
                && (! is_string($value) || $value === '' || mb_strlen($value) > 150)
            ) {
                throw new InvalidLocalAiResponseException(
                    "Local AI entity {$field} is invalid."
                );
            }
        }

        $documentType = $entities['document_type'] ?? null;

        if (
            $documentType !== null
            && ! in_array(
                $documentType,
                ['retooling', 'axle_assy', 'brake_assy'],
                true
            )
        ) {
            throw new InvalidLocalAiResponseException(
                'Local AI document type entity is invalid.'
            );
        }

        $shift = $entities['shift'] ?? null;

        if ($shift !== null && (! is_int($shift) || ! in_array($shift, [1, 2, 3], true))) {
            throw new InvalidLocalAiResponseException(
                'Local AI shift entity is invalid.'
            );
        }

        foreach (['date_from', 'date_to'] as $field) {
            $value = $entities[$field] ?? null;

            if (
                $value !== null
                && (
                    ! is_string($value)
                    || ! $this->isIsoDate($value)
                )
            ) {
                throw new InvalidLocalAiResponseException(
                    "Local AI entity {$field} is invalid."
                );
            }
        }

        $month = $entities['month'] ?? null;
        $year = $entities['year'] ?? null;

        if ($month !== null && (! is_int($month) || $month < 1 || $month > 12)) {
            throw new InvalidLocalAiResponseException(
                'Local AI month entity is invalid.'
            );
        }

        if ($year !== null && (! is_int($year) || $year < 1900 || $year > 2200)) {
            throw new InvalidLocalAiResponseException(
                'Local AI year entity is invalid.'
            );
        }

        $relativeDate = $entities['relative_date'] ?? null;

        if (
            ($entities['date_from'] ?? null) !== null
            && ($entities['date_to'] ?? null) !== null
            && $entities['date_to'] < $entities['date_from']
        ) {
            throw new InvalidLocalAiResponseException(
                'Local AI date range entity is invalid.'
            );
        }

        if (
            $relativeDate !== null
            && ! in_array($relativeDate, ['today', 'yesterday', 'previous_week'], true)
        ) {
            throw new InvalidLocalAiResponseException(
                'Local AI relative date entity is invalid.'
            );
        }

        $sort = $entities['sort'] ?? null;

        if ($sort !== null && $sort !== 'latest') {
            throw new InvalidLocalAiResponseException(
                'Local AI sort entity is invalid.'
            );
        }

        return [
            'document_type' => $documentType,
            'line' => $entities['line'] ?? null,
            'shift' => $shift,
            'from_type' => $entities['from_type'] ?? null,
            'to_type' => $entities['to_type'] ?? null,
            'axle_type' => $entities['axle_type'] ?? null,
            'position' => $entities['position'] ?? null,
            'brake_part_number' => $entities['brake_part_number'] ?? null,
            'date_from' => $entities['date_from'] ?? null,
            'date_to' => $entities['date_to'] ?? null,
            'month' => $month,
            'year' => $year,
            'relative_date' => $relativeDate,
            'sort' => $sort,
        ];
    }

    private function isIsoDate(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        return checkdate(
            (int) substr($value, 5, 2),
            (int) substr($value, 8, 2),
            (int) substr($value, 0, 4)
        );
    }
}
