<?php

namespace Tests\Feature;

use App\Models\Casting;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRetooling;
use App\Models\DocumentScanMetadata;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSmartSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    private ProductionLine $line1;

    private ProductionLine $line2;

    private Casting $productA;

    private Casting $productE;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.smart_search.enabled' => true,
            'ai.smart_search.url' => 'http://127.0.0.1:8765',
            'ai.smart_search.connect_timeout' => 0.1,
            'ai.smart_search.timeout' => 1,
            'ai.smart_search.top_k' => 20,
            'ai.smart_search.candidate_limit' => 250,
        ]);

        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'department_id' => $department->id,
            'name' => 'QC Scans',
            'slug' => 'qc-scans',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'viewer',
            'account_status' => 'active',
        ]);

        $this->line1 = ProductionLine::create([
            'code' => 'LINE_1',
            'name' => 'Line 1',
            'is_active' => true,
        ]);

        $this->line2 = ProductionLine::create([
            'code' => 'LINE_2',
            'name' => 'Line 2',
            'is_active' => true,
        ]);

        $this->productA = Casting::create([
            'code' => 'product_A',
            'name' => 'Product A',
            'is_active' => true,
        ]);

        $this->productE = Casting::create([
            'code' => 'product_E',
            'name' => 'Product E',
            'is_active' => true,
        ]);
    }

    public function test_ai_search_route_requires_authentication(): void
    {
        $this->postJson(
            route('documents.search.ai'),
            ['query' => 'retooling line 1']
        )->assertUnauthorized();
    }

    public function test_ai_query_validation_rejects_empty_and_oversized_input(): void
    {
        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => '   ']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => str_repeat('a', 501)]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');

        Http::assertNothingSent();
    }

    public function test_ai_search_uses_parse_filter_search_and_metadata_only_payload(): void
    {
        $matching = $this->createRetoolingDocument(
            'Retooling LINE_1 September',
            $this->line1,
            '2026-09-15'
        );
        $this->createRetoolingDocument(
            'Retooling LINE_2 September',
            $this->line2,
            '2026-09-15'
        );
        $this->createRetoolingDocument(
            'Retooling LINE_1 October',
            $this->line1,
            '2026-10-02'
        );

        $entities = $this->entities([
            'document_type' => 'retooling',
            'line' => 'LINE_1',
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            'month' => 9,
            'year' => 2026,
        ]);

        Http::fake(function (ClientRequest $request) use ($entities, $matching) {
            if (str_ends_with($request->url(), '/parse')) {
                $this->assertSame(
                    'cari dokumen retooling line 1 bulan september 2026',
                    $request['query']
                );
                $this->assertSame('2026-09-17', $request['reference_date']);
                $this->assertSame(
                    'LINE_1',
                    $request['known_values']['lines'][0]['value']
                );

                return Http::response([
                    'query' => $request['query'],
                    'intent' => 'search_document',
                    'entities' => $entities,
                ]);
            }

            $candidates = $request['candidates'];

            $this->assertCount(1, $candidates);
            $this->assertSame($matching->id, $candidates[0]['document_id']);
            $this->assertSame('LINE_1', $candidates[0]['line']);
            $this->assertSame('product_A', $candidates[0]['from_type']);
            $this->assertSame('product_E', $candidates[0]['to_type']);
            $this->assertSame(25.0, $candidates[0]['from_cycle_time']);
            $this->assertSame(22.0, $candidates[0]['to_cycle_time']);
            $this->assertSame('DOC-001', $candidates[0]['document_code']);
            $this->assertSame('retooling, LINE_1', $candidates[0]['keywords']);
            $this->assertArrayNotHasKey('file_path', $candidates[0]);
            $this->assertArrayNotHasKey('description', $candidates[0]);

            return Http::response([
                'query' => $request['query'],
                'intent' => 'search_document',
                'entities' => $entities,
                'results' => [
                    [
                        'document_id' => $matching->id,
                        'semantic_score' => 0.87,
                    ],
                ],
                'model' => 'multilingual-e5-small',
                'mode' => 'local',
            ]);
        });

        $this->travelTo('2026-09-17 10:00:00');

        $response = $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                [
                    'query' => 'cari dokumen retooling line 1 bulan september 2026',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('mode', 'local_ai')
            ->assertJsonPath('fallback', false)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.id', $matching->id)
            ->assertJsonPath('results.0.semantic_score', 0.87)
            ->assertJsonPath('results.0.matched_by.0', 'Retooling')
            ->assertJsonPath('model', 'multilingual-e5-small');

        Http::assertSentCount(2);
    }

    public function test_ai_result_ids_are_intersected_with_active_authorized_candidates(): void
    {
        $active = $this->createRetoolingDocument(
            'Active Retooling',
            $this->line1,
            '2026-09-15'
        );
        $archived = $this->createRetoolingDocument(
            'Archived Retooling',
            $this->line1,
            '2026-09-15',
            false
        );
        $filteredOut = $this->createRetoolingDocument(
            'Active But Different Line',
            $this->line2,
            '2026-09-15'
        );
        $entities = $this->entities([
            'document_type' => 'retooling',
            'line' => 'LINE_1',
        ]);

        Http::fake(function (ClientRequest $request) use (
            $entities,
            $active,
            $archived,
            $filteredOut
        ) {
            if (str_ends_with($request->url(), '/parse')) {
                return Http::response([
                    'query' => $request['query'],
                    'intent' => 'search_document',
                    'entities' => $entities,
                ]);
            }

            $candidateIds = collect($request['candidates'])
                ->pluck('document_id')
                ->all();

            $this->assertSame([$active->id], $candidateIds);
            $this->assertNotContains($archived->id, $candidateIds);
            $this->assertNotContains($filteredOut->id, $candidateIds);

            return Http::response([
                'query' => $request['query'],
                'intent' => 'search_document',
                'entities' => $entities,
                'results' => [
                    ['document_id' => $archived->id, 'semantic_score' => 0.99],
                    ['document_id' => $filteredOut->id, 'semantic_score' => 0.90],
                    ['document_id' => $active->id, 'semantic_score' => 0.75],
                ],
                'model' => 'fake-local-model',
                'mode' => 'local',
            ]);
        });

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => 'dokumen produksi']
            )
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $active->id)
            ->assertJsonMissing(['id' => $archived->id])
            ->assertJsonMissing(['id' => $filteredOut->id]);
    }

    public function test_latest_entity_overrides_semantic_order_with_document_date(): void
    {
        $older = $this->createRetoolingDocument(
            'Older Retooling',
            $this->line1,
            '2026-08-01'
        );
        $newer = $this->createRetoolingDocument(
            'Newer Retooling',
            $this->line1,
            '2026-09-16'
        );
        $entities = $this->entities(['sort' => 'latest']);

        Http::fake(function (ClientRequest $request) use ($entities, $older, $newer) {
            if (str_ends_with($request->url(), '/parse')) {
                return Http::response([
                    'query' => $request['query'],
                    'intent' => 'search_document',
                    'entities' => $entities,
                ]);
            }

            return Http::response([
                'query' => $request['query'],
                'intent' => 'search_document',
                'entities' => $entities,
                'results' => [
                    ['document_id' => $older->id, 'semantic_score' => 0.98],
                    ['document_id' => $newer->id, 'semantic_score' => 0.70],
                ],
                'model' => 'fake-local-model',
                'mode' => 'local',
            ]);
        });

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => 'retooling terbaru']
            )
            ->assertOk()
            ->assertJsonPath('results.0.id', $newer->id)
            ->assertJsonPath('results.1.id', $older->id);
    }

    public function test_unavailable_or_malformed_local_ai_falls_back_without_page_failure(): void
    {
        $document = $this->createRetoolingDocument(
            'Retooling Fallback',
            $this->line1,
            '2026-09-15'
        );

        Http::fake([
            'http://127.0.0.1:8765/parse' => Http::response(
                ['detail' => ['code' => 'model_unavailable']],
                503
            ),
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => 'Retooling Fallback']
            )
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('mode', 'keyword_fallback')
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('results.0.id', $document->id)
            ->assertJsonPath(
                'message',
                'AI Smart Search lokal sedang tidak tersedia. Pencarian kata kunci lokal tetap dapat digunakan.'
            );

        Http::fake([
            'http://127.0.0.1:8765/parse' => Http::response([
                'intent' => 'search_document',
                'entities' => ['shift' => 99],
            ]),
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => 'Retooling Fallback']
            )
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('mode', 'keyword_fallback');

        Http::fake([
            'http://127.0.0.1:8765/parse' => Http::response([
                'intent' => 'search_document',
                'entities' => $this->entities(),
            ]),
            'http://127.0.0.1:8765/search' => Http::response([
                'intent' => 'search_document',
                'results' => 'invalid',
                'model' => 'fake-local-model',
                'mode' => 'local',
            ]),
        ]);

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => 'Retooling Fallback']
            )
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('mode', 'keyword_fallback');
    }

    public function test_local_ai_connection_timeout_is_handled_safely(): void
    {
        $document = $this->createRetoolingDocument(
            'Timeout Fallback',
            $this->line1,
            '2026-09-15'
        );

        Http::fake(Http::failedConnection('Local AI timed out.'));

        $this->actingAs($this->user)
            ->postJson(
                route('documents.search.ai'),
                ['query' => 'Timeout Fallback']
            )
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('mode', 'keyword_fallback')
            ->assertJsonPath('results.0.id', $document->id);
    }

    public function test_non_loopback_or_path_based_ai_url_is_rejected_before_http(): void
    {
        $document = $this->createRetoolingDocument(
            'Safe Local Fallback',
            $this->line1,
            '2026-09-15'
        );

        foreach ([
            'https://api.example.com',
            'http://127.0.0.1:8765/hidden',
            'http://user:password@127.0.0.1:8765',
        ] as $url) {
            config(['ai.smart_search.url' => $url]);
            Http::fake();

            $this->actingAs($this->user)
                ->postJson(
                    route('documents.search.ai'),
                    ['query' => 'Safe Local Fallback']
                )
                ->assertOk()
                ->assertJsonPath('available', false)
                ->assertJsonPath('results.0.id', $document->id);

            Http::assertNothingSent();
        }
    }

    public function test_existing_normal_search_behavior_remains_available(): void
    {
        $document = $this->createRetoolingDocument(
            'Normal Search Still Works',
            $this->line1,
            '2026-09-15'
        );

        $this->actingAs($this->user)
            ->get(route('documents.index', [
                'search' => 'Normal Search Still Works',
            ]))
            ->assertOk()
            ->assertSee($document->title)
            ->assertSee('AI Smart Search')
            ->assertSee('Pencarian QualityHub')
            ->assertSee('documents\\/search\\/ai', false);

        $this->actingAs($this->user)
            ->getJson(route('documents.search.live', [
                'search' => 'Normal Search Still Works',
            ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $document->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function entities(array $overrides = []): array
    {
        return array_merge([
            'document_type' => null,
            'line' => null,
            'shift' => null,
            'from_type' => null,
            'to_type' => null,
            'axle_type' => null,
            'position' => null,
            'brake_part_number' => null,
            'date_from' => null,
            'date_to' => null,
            'month' => null,
            'year' => null,
            'relative_date' => null,
            'sort' => null,
        ], $overrides);
    }

    private function createRetoolingDocument(
        string $title,
        ProductionLine $line,
        string $documentDate,
        bool $active = true
    ): Document {
        $document = Document::create([
            'category_id' => $this->category->id,
            'uploaded_by' => $this->user->id,
            'title' => $title,
            'document_code' => 'DOC-001',
            'part_number' => null,
            'file_name' => str($title)->slug() . '.pdf',
            'file_path' => 'private/test/' . str($title)->slug() . '.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'document_date' => $documentDate,
            'description' => 'Confidential document body is not an AI candidate field.',
            'keywords' => 'retooling, ' . $line->code,
            'is_active' => $active,
            'archived_at' => $active ? null : now(),
            'archived_by' => $active ? null : $this->user->id,
            'archive_reason' => $active ? null : 'Archived for test',
            'current_revision' => 0,
        ]);

        DocumentScanMetadata::create([
            'document_id' => $document->id,
            'source_type' => 'scan',
            'scan_type' => 'Retooling',
            'scan_date' => $documentDate,
            'production_date' => $documentDate,
            'shift' => '1',
            'line' => $line->code,
            'from_type' => $this->productA->code,
            'to_type' => $this->productE->code,
            'scan_time' => '08:30',
            'image_format' => 'pdf',
            'capture_source' => 'upload',
        ]);

        DocumentRetooling::create([
            'document_id' => $document->id,
            'shift' => 1,
            'production_line_id' => $line->id,
            'from_casting_id' => $this->productA->id,
            'to_casting_id' => $this->productE->id,
            'from_cycle_time' => 25,
            'to_cycle_time' => 22,
        ]);

        return $document;
    }
}
