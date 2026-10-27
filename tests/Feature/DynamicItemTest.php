<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DynamicItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_laptop_form_is_generated_from_metadata_and_rejects_invalid_values(): void
    {
        $this->seed(CategorySeeder::class);
        [$user] = $this->userWithWorkspace();
        $laptop = Category::where('key', 'laptop')->firstOrFail();

        $this->actingAs($user)
            ->get(route('items.create', ['category' => $laptop->id]))
            ->assertOk()
            ->assertSee('Processor')
            ->assertSee('Memory (GB)')
            ->assertSee('Original charger included');

        $this->post(route('items.store'), [
            'category_id' => $laptop->id,
            'name' => 'Work laptop',
            'dynamic' => [
                'processor' => 'Intel Core i7',
                'memory_gb' => 0,
                'storage_gb' => 512,
                'storage_type' => 'invalid-drive',
            ],
        ])->assertSessionHasErrors([
            'dynamic.memory_gb',
            'dynamic.storage_type',
        ]);
    }

    public function test_product_and_typed_laptop_values_round_trip(): void
    {
        $this->seed(CategorySeeder::class);
        [$user, $workspace] = $this->userWithWorkspace();
        $laptop = Category::where('key', 'laptop')->firstOrFail();

        $this->actingAs($user)->post(route('items.store'), [
            'category_id' => $laptop->id,
            'name' => 'Studio laptop',
            'brand' => 'Framework',
            'model' => 'Laptop 13',
            'serial_number' => 'KEEP-12345',
            'purchase_date' => '2026-01-15',
            'purchase_price' => '125000.50',
            'seller_name' => 'Framework Store',
            'return_days' => 30,
            'warranty_months' => 24,
            'dynamic' => [
                'processor' => 'AMD Ryzen 7',
                'memory_gb' => 32,
                'storage_gb' => 1024,
                'storage_type' => 'ssd',
                'screen_size_inches' => '13.5',
                'operating_system' => 'Windows 11',
                'charger_included' => '1',
            ],
        ])->assertSessionHasNoErrors();

        $item = Item::where('workspace_id', $workspace->id)->firstOrFail();
        $values = $item->load('values.categoryField')->values->keyBy('categoryField.key');

        $this->assertSame(12500050, $item->purchase_price_minor);
        $this->assertSame('KEEP-12345', $item->serial_number);
        $this->assertNotSame('KEEP-12345', DB::table('items')->where('id', $item->id)->value('serial_number'));
        $this->assertSame(32, $values['memory_gb']->value_integer);
        $this->assertSame('13.5000', $values['screen_size_inches']->value_decimal);
        $this->assertTrue($values['charger_included']->value_boolean);
        $this->assertSame('ssd', $values['storage_type']->value_text);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Studio laptop')
            ->assertSee('AMD Ryzen 7')
            ->assertSee('SSD')
            ->assertSee('INR 125,000.50');
    }

    public function test_date_metadata_value_round_trips(): void
    {
        $this->seed(CategorySeeder::class);
        [$user] = $this->userWithWorkspace();
        $generic = Category::where('key', 'generic')->firstOrFail();
        $generic->fields()->create([
            'key' => 'installed_on',
            'label' => 'Installed on',
            'data_type' => 'date',
            'is_required' => true,
            'sort_order' => 30,
        ]);

        $this->actingAs($user)->post(route('items.store'), [
            'category_id' => $generic->id,
            'name' => 'Water purifier',
            'dynamic' => [
                'condition_at_purchase' => 'new',
                'installed_on' => '2026-02-03',
            ],
        ])->assertSessionHasNoErrors();

        $item = Item::firstOrFail()->load('values.categoryField');
        $installed = $item->values->firstWhere('categoryField.key', 'installed_on');

        $this->assertSame('2026-02-03', $installed->value_date->format('Y-m-d'));
    }

    public function test_product_from_another_workspace_is_forbidden(): void
    {
        $this->seed(CategorySeeder::class);
        [$user] = $this->userWithWorkspace();
        [, $otherWorkspace] = $this->userWithWorkspace();
        $laptop = Category::where('key', 'laptop')->firstOrFail();
        $item = $otherWorkspace->items()->create([
            'category_id' => $laptop->id,
            'name' => 'Private laptop',
            'currency' => 'INR',
        ]);

        $this->actingAs($user)
            ->get(route('items.show', $item))
            ->assertForbidden();
    }

    private function userWithWorkspace(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => $user->name."'s workspace",
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);

        return [$user, $workspace];
    }
}
