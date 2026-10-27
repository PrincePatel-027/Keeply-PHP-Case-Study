<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_member_can_attach_and_download_a_private_document(): void
    {
        Storage::fake('local');
        [$user, $workspace, $item] = $this->productOwner();
        $file = UploadedFile::fake()->createWithContent(
            'laptop-receipt.pdf',
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF",
        );

        $this->actingAs($user)
            ->post(route('items.documents.store', $item), [
                'purpose' => 'receipt',
                'document' => $file,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('items.show', $item));

        $document = Document::firstOrFail();

        $this->assertSame($workspace->id, $document->workspace_id);
        $this->assertSame('receipt', $document->type);
        $this->assertSame('laptop-receipt.pdf', $document->original_name);
        $this->assertSame(64, strlen($document->sha256));
        $this->assertDatabaseHas('item_documents', [
            'item_id' => $item->id,
            'document_id' => $document->id,
            'purpose' => 'receipt',
        ]);
        Storage::disk('local')->assertExists($document->storage_path);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('laptop-receipt.pdf')
            ->assertSee('Receipt');

        $this->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_file_content_must_match_an_allowed_document_type(): void
    {
        Storage::fake('local');
        [$user, , $item] = $this->productOwner();
        $disguisedFile = UploadedFile::fake()->createWithContent('not-a-document.pdf', '<?php echo "unsafe";');

        $this->actingAs($user)
            ->post(route('items.documents.store', $item), [
                'purpose' => 'receipt',
                'document' => $disguisedFile,
            ])
            ->assertSessionHasErrors('document');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_user_from_another_workspace_cannot_download_the_document(): void
    {
        Storage::fake('local');
        [$owner, , $item] = $this->productOwner();
        [$otherUser] = $this->productOwner();

        $this->actingAs($owner)->post(route('items.documents.store', $item), [
            'purpose' => 'warranty',
            'document' => UploadedFile::fake()->createWithContent(
                'warranty.pdf',
                "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF",
            ),
        ])->assertSessionHasNoErrors();

        $document = Document::firstOrFail();

        $this->actingAs($otherUser)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    private function productOwner(): array
    {
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
            'name' => 'Protected product',
            'currency' => 'INR',
        ]);

        return [$user, $workspace, $item];
    }
}
