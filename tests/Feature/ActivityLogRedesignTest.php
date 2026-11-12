<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
            'must_change_password' => false,
        ]);
    }

    public function test_admin_sees_compact_activity_feed_and_full_audit_metadata(): void
    {
        $admin = $this->actor();

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'view_document',
            'description' => 'Dokumen dicek sebelum retooling.',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'QualityHub QA Browser',
            'metadata' => ['source_type' => 'scan', 'revision_number' => 3],
        ]);

        $response = $this->actingAs($admin)->get(route('activity-logs.index'))->assertOk();

        $response->assertSee('data-qh-audit-page', false)
            ->assertSee('data-qh-audit-entry', false)
            ->assertSee('data-qh-audit-detail', false)
            ->assertSee('data-qh-audit-detail-content', false)
            ->assertSee('Lihat Detail')
            ->assertSee('view_document', false) // value in the filter, not renamed
            ->assertSee('Dokumen dicek sebelum retooling.')
            ->assertSee('QualityHub QA Browser')
            ->assertSee('Source Type')
            ->assertSee('Revision Number')
            ->assertSee('127.0.0.1');

        $this->assertStringNotContainsString('x-data="{ expanded: false }"', $response->getContent());
    }

    public function test_existing_get_filter_fields_are_preserved(): void
    {
        $admin = $this->actor();
        $response = $this->actingAs($admin)->get(route('activity-logs.index'))->assertOk();

        foreach (['search', 'user_id', 'action', 'document_id', 'ip_address', 'date_from', 'date_to'] as $name) {
            $response->assertSee('name="'.$name.'"', false);
        }

        foreach (['user_id', 'action', 'document_id'] as $name) {
            $response->assertSee('id="'.$name.'"', false);
        }

        $response->assertSee('data-qh-searchable-select', false)
            ->assertSee('Filter Lanjutan');
    }

    public function test_filters_and_pagination_queries_keep_existing_behavior(): void
    {
        $admin = $this->actor();

        ActivityLog::create(['user_id' => $admin->id, 'action' => 'view_document', 'description' => 'QC test A']);
        ActivityLog::create(['user_id' => $admin->id, 'action' => 'download_document', 'description' => 'QC test B']);

        $response = $this->actingAs($admin)->get(route('activity-logs.index', [
            'action' => 'view_document',
            'user_id' => $admin->id,
        ]))->assertOk();

        $response->assertSee('QC test A')
            ->assertDontSee('QC test B')
            ->assertSee('Filter Lanjutan');
    }

    public function test_regular_user_cannot_access_activity_log(): void
    {
        $user = $this->actor('user');
        $this->actingAs($user)->get(route('activity-logs.index'))->assertForbidden();
    }

    public function test_system_activity_stays_readable_without_account(): void
    {
        $admin = $this->actor();

        ActivityLog::create([
            'user_id' => null,
            'action' => 'view_document',
            'description' => 'Proses otomatis.',
            'metadata' => ['success' => true],
        ]);

        $this->actingAs($admin)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Sistem')
            ->assertSee('Aktivitas sistem')
            ->assertSee('Proses otomatis.')
            ->assertSee('ya');
    }
}
