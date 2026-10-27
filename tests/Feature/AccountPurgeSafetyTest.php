<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AccountPurgeSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_file_deletion_preserves_records_for_a_later_purge_attempt(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Private workspace',
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);
        $document = $workspace->documents()->create([
            'uploaded_by' => $user->id,
            'type' => 'receipt',
            'original_name' => 'receipt.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'documents/private-receipt.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 20,
            'sha256' => hash('sha256', 'private receipt'),
        ]);
        $user->delete();

        Storage::shouldReceive('disk')->once()->with('local')->andReturnSelf();
        Storage::shouldReceive('delete')
            ->once()
            ->with('documents/private-receipt.pdf')
            ->andReturnFalse();

        try {
            app(AccountDeletionService::class)->purge($user);
            $this->fail('The purge should fail when a private file cannot be deleted.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'A private account file could not be removed. The purge will be retried.',
                $exception->getMessage(),
            );
        }

        $this->assertTrue(User::withTrashed()->whereKey($user->id)->exists());
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }
}
