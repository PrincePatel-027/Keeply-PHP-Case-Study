<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDataControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_export_contains_portable_records_without_credentials_or_storage_paths(): void
    {
        [$user, , $item, $document] = $this->accountWithPrivateData();

        $response = $this->actingAs($user)->get(route('account.export'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Disposition');
        $payload = $response->json();
        $this->assertSame($user->email, $payload['account']['email']);
        $this->assertSame($item->name, $payload['workspaces'][0]['products'][0]['name']);
        $this->assertSame($document->sha256, $payload['workspaces'][0]['documents'][0]['sha256']);
        $this->assertFalse($payload['retention']['binary_files_included']);
        $this->assertStringNotContainsString('storage_path', $response->getContent());
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_wrong_password_leaves_account_records_and_files_intact(): void
    {
        [$user, $workspace, , $document] = $this->accountWithPrivateData();

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => 'incorrect-password',
            'confirmation' => 'DELETE MY ACCOUNT',
        ])->assertSessionHasErrors('password');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
        Storage::disk($document->storage_disk)->assertExists($document->storage_path);
    }

    public function test_deletion_request_disables_access_but_retains_data_for_thirty_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        [$user, $workspace, , $document] = $this->accountWithPrivateData();

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => 'password',
            'confirmation' => 'DELETE MY ACCOUNT',
        ])->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
        Storage::disk($document->storage_disk)->assertExists($document->storage_path);

        $this->artisan('accounts:purge-deleted')
            ->expectsOutput('Purged 0 account(s).')
            ->assertSuccessful();
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
    }

    public function test_expired_deletion_request_permanently_removes_records_and_private_files(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        [$user, $workspace, , $document] = $this->accountWithPrivateData();
        $path = $document->storage_path;
        $disk = $document->storage_disk;

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => 'password',
            'confirmation' => 'DELETE MY ACCOUNT',
        ])->assertSessionHasNoErrors();

        $this->travel(31)->days();
        $this->artisan('accounts:purge-deleted')
            ->expectsOutput('Purged 1 account(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        Storage::disk($disk)->assertMissing($path);
    }

    public function test_shared_workspace_blocks_account_deletion(): void
    {
        [$user, $workspace, , $document] = $this->accountWithPrivateData();
        $otherUser = User::factory()->create();
        $workspace->members()->attach($otherUser, ['role' => 'member']);

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => 'password',
            'confirmation' => 'DELETE MY ACCOUNT',
        ])->assertSessionHasErrors('account');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id]);
        Storage::disk($document->storage_disk)->assertExists($document->storage_path);
    }

    private function accountWithPrivateData(): array
    {
        Storage::fake('local');
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => $user->name."'s workspace",
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);
        $category = Category::where('key', 'generic')->firstOrFail();
        $item = $workspace->items()->create([
            'category_id' => $category->id,
            'name' => 'Work laptop',
            'serial_number' => 'KEEP-1234',
            'currency' => 'INR',
        ]);
        $path = "documents/{$workspace->id}/receipt.pdf";
        Storage::disk('local')->put($path, '%PDF-private-receipt');
        $document = $workspace->documents()->create([
            'uploaded_by' => $user->id,
            'type' => 'receipt',
            'original_name' => 'receipt.pdf',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 20,
            'sha256' => hash('sha256', '%PDF-private-receipt'),
        ]);
        $item->documents()->attach($document, ['purpose' => 'receipt']);

        return [$user, $workspace, $item, $document];
    }
}
