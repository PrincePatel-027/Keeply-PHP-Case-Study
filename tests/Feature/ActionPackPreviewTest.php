<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AssessmentSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionPackPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_saved_assessment_is_required_to_open_the_builder(): void
    {
        [$user, , , $case] = $this->ownerContext();

        $this->actingAs($user)
            ->get(route('cases.action-packs.create', $case))
            ->assertRedirect(route('cases.show', $case))
            ->assertSessionHasErrors('action_pack');
    }

    public function test_user_can_choose_evidence_edit_the_letter_and_preview_the_case(): void
    {
        [$user, $workspace, $item, $case] = $this->ownerContext();
        $assessment = app(AssessmentSnapshotService::class)->create($case, $user->id);
        $receipt = $this->attachDocument($workspace, $item, $user, 'receipt', 'purchase-receipt.pdf');
        $warranty = $this->attachDocument($workspace, $item, $user, 'warranty', 'warranty-terms.pdf');
        $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'contact',
            'occurred_at' => '2026-02-16 10:00:00',
            'summary' => 'Called the seller and described the battery failure.',
            'reference' => 'SUP-2048',
        ]);

        $this->actingAs($user)
            ->get(route('cases.action-packs.create', $case))
            ->assertOk()
            ->assertSee('Build a factual Action Pack')
            ->assertSee('purchase-receipt.pdf')
            ->assertSee('warranty-terms.pdf')
            ->assertSee('Subject: Request for repair');

        $this->post(route('cases.action-packs.preview', $case), [
            'assessment_id' => $assessment->id,
            'document_ids' => [$receipt->id],
            'letter_body' => "Hello Support,\n\nPlease review my attached receipt and repair request.",
        ])
            ->assertOk()
            ->assertSee('Preview only')
            ->assertSee('Please review my attached receipt and repair request.')
            ->assertSee('purchase-receipt.pdf')
            ->assertDontSee('warranty-terms.pdf')
            ->assertSee('Called the seller and described the battery failure.')
            ->assertSee('SUP-2048')
            ->assertSee('Not stored · No PDF created');

        $this->assertNotSame($receipt->id, $warranty->id);
    }

    public function test_selected_document_must_belong_to_the_case_product(): void
    {
        [$user, $workspace, , $case] = $this->ownerContext();
        [, , $otherItem] = $this->ownerContext($user, $workspace);
        $assessment = app(AssessmentSnapshotService::class)->create($case, $user->id);
        $otherDocument = $this->attachDocument(
            $workspace,
            $otherItem,
            $user,
            'receipt',
            'other-product.pdf',
        );

        $this->actingAs($user)
            ->post(route('cases.action-packs.preview', $case), [
                'assessment_id' => $assessment->id,
                'document_ids' => [$otherDocument->id],
                'letter_body' => 'A factual request.',
            ])
            ->assertSessionHasErrors('document_ids');
    }

    public function test_another_workspace_cannot_open_or_preview_the_pack(): void
    {
        [$owner, , , $case] = $this->ownerContext();
        [$otherUser] = $this->ownerContext();
        $assessment = app(AssessmentSnapshotService::class)->create($case, $owner->id);

        $this->actingAs($otherUser)
            ->get(route('cases.action-packs.create', $case))
            ->assertForbidden();

        $this->post(route('cases.action-packs.preview', $case), [
            'assessment_id' => $assessment->id,
            'letter_body' => 'Must not render.',
        ])->assertForbidden();
    }

    private function attachDocument(
        Workspace $workspace,
        Item $item,
        User $user,
        string $purpose,
        string $name,
    ): Document {
        $document = $workspace->documents()->create([
            'uploaded_by' => $user->id,
            'type' => $purpose,
            'original_name' => $name,
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/'.str()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 128,
            'sha256' => hash('sha256', $name),
        ]);
        $item->documents()->attach($document, ['purpose' => $purpose]);

        return $document;
    }

    private function ownerContext(?User $user = null, ?Workspace $workspace = null): array
    {
        $user ??= User::factory()->create();
        $workspace ??= Workspace::create([
            'name' => $user->name."'s workspace",
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);

        if (! $workspace->members()->whereKey($user->id)->exists()) {
            $workspace->members()->attach($user, ['role' => 'owner']);
        }

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
