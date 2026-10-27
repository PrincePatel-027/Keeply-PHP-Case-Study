<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AssessmentSnapshotService;
use App\Services\EvidenceChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_saved_assessment_is_required_before_building_the_checklist(): void
    {
        [, , , $case] = $this->ownerContext();

        $checklist = app(EvidenceChecklistService::class)->build($case);

        $this->assertNull($checklist['assessment']);
        $this->assertSame([], $checklist['items']);
    }

    public function test_missing_and_optional_evidence_have_factual_explanations(): void
    {
        [$user, , , $case] = $this->ownerContext();
        app(AssessmentSnapshotService::class)->create($case, $user->id);

        $checklist = app(EvidenceChecklistService::class)->build($case->fresh());
        $items = collect($checklist['items'])->keyBy('key');

        $this->assertSame('missing', $items['proof_of_purchase']['state']);
        $this->assertSame('missing', $items['product_identifier']['state']);
        $this->assertSame('missing', $items['warranty_document']['state']);
        $this->assertSame('optional', $items['issue_photo']['state']);
        $this->assertSame('optional', $items['repair_history']['state']);
        $this->assertSame('optional', $items['communication_reference']['state']);

        foreach ($items as $item) {
            $this->assertNotEmpty($item['explanation']);
            $this->assertNotEmpty($item['source']);
        }

        $this->actingAs($user)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Evidence checklist')
            ->assertSee('Proof of purchase')
            ->assertSee('Attached document types do not include receipt');
    }

    public function test_attached_and_recorded_evidence_is_marked_present(): void
    {
        [$user, $workspace, $item, $case] = $this->ownerContext();
        $item->update(['model' => 'ThinkPad T14']);
        $this->attachDocument($workspace, $item, $user, 'receipt');
        $this->attachDocument($workspace, $item, $user, 'warranty');
        $this->attachDocument($workspace, $item, $user, 'product_photo');
        $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'repair',
            'occurred_at' => now()->subDays(2),
            'summary' => 'Battery connector was reseated.',
        ]);
        $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'contact',
            'occurred_at' => now()->subDay(),
            'summary' => 'Contacted support.',
            'reference' => 'SUP-2048',
        ]);
        app(AssessmentSnapshotService::class)->create($case, $user->id);

        $items = collect(app(EvidenceChecklistService::class)->build($case->fresh())['items']);

        $this->assertCount(6, $items);
        $this->assertTrue($items->every(fn (array $item): bool => $item['state'] === 'present'));
    }

    public function test_expired_warranty_is_not_applicable_when_no_document_is_attached(): void
    {
        [$user, , $item, $case] = $this->ownerContext();
        $item->update([
            'purchase_date' => '2024-01-01',
            'warranty_months' => 12,
        ]);
        app(AssessmentSnapshotService::class)->create($case, $user->id);

        $items = collect(app(EvidenceChecklistService::class)->build($case->fresh())['items'])->keyBy('key');

        $this->assertSame('not_applicable', $items['warranty_document']['state']);
        $this->assertStringContainsString('expired', $items['warranty_document']['explanation']);
    }

    private function attachDocument(Workspace $workspace, $item, User $user, string $purpose): void
    {
        $document = $workspace->documents()->create([
            'uploaded_by' => $user->id,
            'type' => $purpose,
            'original_name' => $purpose.'.pdf',
            'storage_disk' => 'local',
            'storage_path' => "documents/test/{$purpose}.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 128,
            'sha256' => str_repeat(substr($purpose, 0, 1), 64),
        ]);
        $item->documents()->attach($document, ['purpose' => $purpose]);
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
            'purchase_date' => '2026-01-01',
            'return_days' => 30,
            'warranty_months' => 12,
            'currency' => 'INR',
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
