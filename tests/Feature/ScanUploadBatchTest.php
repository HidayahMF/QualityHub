<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ScanUploadBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScanUploadBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    public function test_admin_can_stage_photos_in_sequential_chunks_and_restore_pdf_order(): void
    {
        $admin = $this->admin();

        $start = $this->actingAs($admin)->postJson(route('documents.scan.batches.start'), [
            'expected_count' => 13,
        ])->assertCreated()->json();

        $this->assertIsString($start['batch_id']);
        $id = $start['batch_id'];

        $first = array_map(
            fn (int $n) => UploadedFile::fake()->image("page-{$n}.png", 120, 160),
            range(1, 10)
        );

        $this->withHeader('Accept', 'application/json')
            ->post(route('documents.scan.batches.upload'), [
                'batch_id' => $id,
                'offset' => 0,
                'files' => $first,
            ])
            ->assertOk()
            ->assertJsonPath('received', 10);

        $second = array_map(
            fn (int $n) => UploadedFile::fake()->image("page-{$n}.png", 120, 160),
            range(11, 13)
        );

        $this->withHeader('Accept', 'application/json')
            ->post(route('documents.scan.batches.upload'), [
                'batch_id' => $id,
                'offset' => 10,
                'files' => $second,
            ])
            ->assertOk()
            ->assertJsonPath('received', 13);

        $staged = app(ScanUploadBatchService::class)
            ->filesForCompletion($id, $admin->id, 13);

        $this->assertCount(13, $staged);
        $this->assertSame('photo-0001.png', $staged[0]->getClientOriginalName());
        $this->assertSame('photo-0013.png', $staged[12]->getClientOriginalName());
        $this->assertFileExists($staged[0]->getRealPath());

        app(ScanUploadBatchService::class)->discard($id, $admin->id);
        Storage::disk('local')->assertMissing('scan-upload-batches/'.$id.'/manifest.json');
    }

    public function test_incomplete_and_out_of_order_batches_are_rejected(): void
    {
        $admin = $this->admin();
        $id = $this->actingAs($admin)
            ->postJson(route('documents.scan.batches.start'), ['expected_count' => 1000])
            ->assertCreated()
            ->json('batch_id');

        $this->withHeader('Accept', 'application/json')
            ->post(route('documents.scan.batches.upload'), [
                'batch_id' => $id,
                'offset' => 5,
                'files' => [UploadedFile::fake()->image('page.png')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('offset');

        $this->expectException(ValidationException::class);
        app(ScanUploadBatchService::class)->filesForCompletion($id, $admin->id, 1000);
    }

    public function test_batch_is_private_to_its_owner(): void
    {
        $admin = $this->admin();
        $anotherAdmin = $this->admin();

        $id = $this->actingAs($admin)
            ->postJson(route('documents.scan.batches.start'), ['expected_count' => 2])
            ->assertCreated()
            ->json('batch_id');

        $this->actingAs($anotherAdmin)
            ->withHeader('Accept', 'application/json')
            ->post(route('documents.scan.batches.upload'), [
                'batch_id' => $id,
                'offset' => 0,
                'files' => [UploadedFile::fake()->image('page.png')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('batch_id');
    }

    public function test_employee_cannot_start_upload_batch(): void
    {
        $employee = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->actingAs($employee)
            ->postJson(route('documents.scan.batches.start'), ['expected_count' => 10])
            ->assertForbidden();
    }

    public function test_server_rejects_more_than_one_thousand_photo_pages(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('documents.scan.batches.start'), ['expected_count' => 1001])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_count');
    }
}
