<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_and_laptop_definitions_load_in_field_order(): void
    {
        $this->seed(CategorySeeder::class);

        $categories = Category::query()
            ->where('is_active', true)
            ->with('fields')
            ->orderBy('sort_order')
            ->get();

        $this->assertSame(['laptop', 'generic'], $categories->pluck('key')->all());

        $laptop = $categories->firstWhere('key', 'laptop');
        $generic = $categories->firstWhere('key', 'generic');

        $this->assertSame(
            ['processor', 'memory_gb', 'storage_gb', 'storage_type', 'screen_size_inches', 'operating_system', 'charger_included'],
            $laptop->fields->pluck('key')->all()
        );
        $this->assertSame(['variant', 'condition_at_purchase'], $generic->fields->pluck('key')->all());

        $memory = $laptop->fields->firstWhere('key', 'memory_gb');
        $storageType = $laptop->fields->firstWhere('key', 'storage_type');

        $this->assertTrue($memory->is_required);
        $this->assertSame(['minimum' => 1, 'maximum' => 512], $memory->validation_rules);
        $this->assertSame('SSD', $storageType->options['ssd']);
    }

    public function test_category_seeding_is_idempotent(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseCount('category_fields', 9);
    }
}
