<?php

namespace Tests\Feature;

use App\Models\ActionPack;
use App\Models\Category;
use App\Models\Document;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AssessmentSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class ActionPackPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_pack_is_snapshotted_as_a_private_valid_pdf(): void
    {
        Storage::fake('local');
        [$user, $workspace, $item, $case] = $this->ownerContext();
        $assessment = app(AssessmentSnapshotService::class)->create($case, $user->id);
        $receipt = $this->attachDocument($workspace, $item, $user);
        $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'contact',
            'occurred_at' => '2026-02-16 10:00:00',
            'summary' => 'Called the seller about the battery.',
            'reference' => 'SUP-2048',
        ]);

        $this->actingAs($user)
            ->post(route('cases.action-packs.store', $case), [
                'assessment_id' => $assessment->id,
                'document_ids' => [$receipt->id],
                'letter_body' => 'Please review my repair request.',
            ])
            ->assertRedirect(route('cases.show', $case));

        $actionPack = ActionPack::with('pdfDocument')->firstOrFail();
        $this->assertSame(1, $actionPack->version);
        $this->assertSame('Work laptop', $actionPack->facts_snapshot['item']['name']);
        $this->assertSame('Called the seller about the battery.', $actionPack->events_snapshot[0]['summary']);
        $this->assertSame($receipt->sha256, $actionPack->evidence_manifest[0]['sha256']);
        $this->assertSame('Please review my repair request.', $actionPack->letter_body);
        $this->assertSame('action_pack', $actionPack->pdfDocument->type);
        $this->assertSame('application/pdf', $actionPack->pdfDocument->mime_type);
        Storage::disk('local')->assertExists($actionPack->pdfDocument->storage_path);
        $this->assertStringStartsWith(
            '%PDF-',
            Storage::disk('local')->get($actionPack->pdfDocument->storage_path),
        );
    }

    public function test_each_generation_creates_a_new_version_without_rewriting_the_first(): void
    {
        Storage::fake('local');
        [$user, , $item, $case] = $this->ownerContext();
        $assessment = app(AssessmentSnapshotService::class)->create($case, $user->id);

        $this->actingAs($user)->post(route('cases.action-packs.store', $case), [
            'assessment_id' => $assessment->id,
            'letter_body' => 'First factual letter.',
        ]);
        $first = ActionPack::firstOrFail();

        $item->update(['name' => 'Renamed laptop']);
        $this->post(route('cases.action-packs.store', $case), [
            'assessment_id' => $assessment->id,
            'letter_body' => 'Second factual letter.',
        ]);

        $this->assertSame('Work laptop', $first->fresh()->facts_snapshot['item']['name']);
        $this->assertSame('Renamed laptop', ActionPack::where('version', 2)->firstOrFail()->facts_snapshot['item']['name']);
        $this->assertDatabaseCount('action_packs', 2);

        $this->expectException(LogicException::class);
        $first->update(['letter_body' => 'Changed later']);
    }

    public function test_only_workspace_members_can_download_the_generated_pdf(): void
    {
        Storage::fake('local');
        [$owner, , , $case] = $this->ownerContext();
        [$otherUser] = $this->ownerContext();
        $assessment = app(AssessmentSnapshotService::class)->create($case, $owner->id);

        $this->actingAs($owner)->post(route('cases.action-packs.store', $case), [
            'assessment_id' => $assessment->id,
            'letter_body' => 'Private letter.',
        ]);
        $actionPack = ActionPack::firstOrFail();

        $this->get(route('action-packs.download', $actionPack))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($otherUser)
            ->get(route('action-packs.download', $actionPack))
            ->assertForbidden();
    }

    private function attachDocument(Workspace $workspace, Item $item, User $user): Document
    {
        $document = $workspace->documents()->create([
            'uploaded_by' => $user->id,
            'type' => 'receipt',
            'original_name' => 'purchase-receipt.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/purchase-receipt.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 128,
            'sha256' => hash('sha256', 'receipt'),
        ]);
        $item->documents()->attach($document, ['purpose' => 'receipt']);

        return $document;
    }

    private function ownerContext(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => $user->name."'s workspace",
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);
        $category = Category::create([
            'key' => 'test-'.str()->random(8),
            'name' => 'Test product',
            'is_active' => true,
        ]);
        $item = $workspace->items()->create([
            'category_id' => $category->id,
            'name' => 'Work laptop',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad T14',
            'purchase_date' => '2026-01-01',
            'return_days' => 30,
            'warranty_months' => 12,
            'currency' => 'INR',
            'seller_name' => 'Example Electronics',
        ]);
        $case = $workspace->resolutionCases()->create([
            'item_id' => $item->id,
            'opened_by' => $user->id,
            'title' => 'Battery problem',
            'problem_summary' => 'Battery no longer holds charge.',
            'failure_date' => '2026-02-15',
            'desired_outcome' => 'repair',
            'status' => 'active',
            'priority' => 'normal',
            'opened_at' => now(),
        ]);

        return [$user, $workspace, $item, $case];
    }
}
