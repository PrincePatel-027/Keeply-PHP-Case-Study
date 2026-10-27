<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $generic = Category::updateOrCreate(
            ['key' => 'generic'],
            ['name' => 'Other product', 'is_active' => true, 'sort_order' => 100]
        );

        $this->syncFields($generic, [
            [
                'key' => 'variant',
                'label' => 'Variant or specification',
                'data_type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max_length' => 120],
                'help_text' => 'Size, edition, colour, or another detail that identifies this product.',
                'sort_order' => 10,
            ],
            [
                'key' => 'condition_at_purchase',
                'label' => 'Condition when purchased',
                'data_type' => 'select',
                'is_required' => true,
                'options' => [
                    'new' => 'New',
                    'refurbished' => 'Refurbished',
                    'used' => 'Used',
                ],
                'sort_order' => 20,
            ],
        ]);

        $laptop = Category::updateOrCreate(
            ['key' => 'laptop'],
            ['name' => 'Laptop', 'is_active' => true, 'sort_order' => 10]
        );

        $this->syncFields($laptop, [
            [
                'key' => 'processor',
                'label' => 'Processor',
                'data_type' => 'text',
                'is_required' => true,
                'validation_rules' => ['max_length' => 120],
                'sort_order' => 10,
            ],
            [
                'key' => 'memory_gb',
                'label' => 'Memory (GB)',
                'data_type' => 'integer',
                'is_required' => true,
                'validation_rules' => ['minimum' => 1, 'maximum' => 512],
                'sort_order' => 20,
            ],
            [
                'key' => 'storage_gb',
                'label' => 'Storage capacity (GB)',
                'data_type' => 'integer',
                'is_required' => true,
                'validation_rules' => ['minimum' => 16, 'maximum' => 16384],
                'sort_order' => 30,
            ],
            [
                'key' => 'storage_type',
                'label' => 'Storage type',
                'data_type' => 'select',
                'is_required' => true,
                'options' => [
                    'ssd' => 'SSD',
                    'hdd' => 'HDD',
                    'hybrid' => 'Hybrid',
                ],
                'sort_order' => 40,
            ],
            [
                'key' => 'screen_size_inches',
                'label' => 'Screen size (inches)',
                'data_type' => 'decimal',
                'is_required' => false,
                'validation_rules' => ['minimum' => 7, 'maximum' => 24, 'decimal_places' => 1],
                'sort_order' => 50,
            ],
            [
                'key' => 'operating_system',
                'label' => 'Operating system',
                'data_type' => 'text',
                'is_required' => false,
                'validation_rules' => ['max_length' => 80],
                'sort_order' => 60,
            ],
            [
                'key' => 'charger_included',
                'label' => 'Original charger included',
                'data_type' => 'boolean',
                'is_required' => false,
                'sort_order' => 70,
            ],
        ]);
    }

    private function syncFields(Category $category, array $fields): void
    {
        foreach ($fields as $field) {
            $category->fields()->updateOrCreate(
                ['key' => $field['key']],
                array_merge([
                    'validation_rules' => null,
                    'options' => null,
                    'help_text' => null,
                    'is_sensitive' => false,
                ], $field)
            );
        }
    }
}
