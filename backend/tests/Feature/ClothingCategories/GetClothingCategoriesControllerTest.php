<?php

namespace Tests\Feature\ClothingCategories;

use App\Models\ClothingCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GetClothingCategoriesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed clothing categories for tests
        $this->seedClothingCategories();
    }

    private function seedClothingCategories(): void
    {
        $categories = [
            ['name' => 'Tシャツ', 'icon_path' => '/icons/tshirt.svg', 'sort_order' => 1],
            ['name' => 'ズボン', 'icon_path' => '/icons/pants.svg', 'sort_order' => 2],
            ['name' => '靴下', 'icon_path' => '/icons/socks.svg', 'sort_order' => 3],
            ['name' => 'ハンカチ', 'icon_path' => '/icons/handkerchief.svg', 'sort_order' => 4],
            ['name' => '肌着', 'icon_path' => '/icons/underwear.svg', 'sort_order' => 5],
            ['name' => 'ぼうし', 'icon_path' => '/icons/hat.svg', 'sort_order' => 6],
            ['name' => '水着セット', 'icon_path' => '/icons/swimwear.svg', 'sort_order' => 7],
            ['name' => 'ビニール袋', 'icon_path' => '/icons/plastic_bag.svg', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            ClothingCategory::create($category);
        }
    }

    public function test_can_get_all_clothing_categories(): void
    {
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '衣類カテゴリ一覧を取得しました',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'icon_path',
                            'sort_order',
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertCount(8, $responseData['categories']);
    }

    public function test_categories_are_ordered_by_sort_order(): void
    {
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $categories = $responseData['categories'];

        // Verify categories are ordered by sort_order
        for ($i = 0; $i < count($categories) - 1; $i++) {
            $currentSortOrder = $categories[$i]['sort_order'];
            $nextSortOrder = $categories[$i + 1]['sort_order'];
            $this->assertLessThan($nextSortOrder, $currentSortOrder);
        }

        // Verify first category is sort_order 1
        $this->assertEquals(1, $categories[0]['sort_order']);
        $this->assertEquals('Tシャツ', $categories[0]['name']);

        // Verify last category is sort_order 8
        $this->assertEquals(8, $categories[7]['sort_order']);
        $this->assertEquals('ビニール袋', $categories[7]['name']);
    }

    public function test_response_contains_all_required_fields(): void
    {
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $categories = $responseData['categories'];

        foreach ($categories as $category) {
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('name', $category);
            $this->assertArrayHasKey('icon_path', $category);
            $this->assertArrayHasKey('sort_order', $category);

            $this->assertIsInt($category['id']);
            $this->assertIsString($category['name']);
            $this->assertIsString($category['icon_path']);
            $this->assertIsInt($category['sort_order']);
        }
    }

    public function test_icon_paths_are_correct(): void
    {
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $categories = $responseData['categories'];

        $expectedIconPaths = [
            '/icons/tshirt.svg',
            '/icons/pants.svg',
            '/icons/socks.svg',
            '/icons/handkerchief.svg',
            '/icons/underwear.svg',
            '/icons/hat.svg',
            '/icons/swimwear.svg',
            '/icons/plastic_bag.svg',
        ];

        foreach ($categories as $index => $category) {
            $this->assertEquals($expectedIconPaths[$index], $category['icon_path']);
        }
    }

    public function test_response_structure_is_correct(): void
    {
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'icon_path',
                            'sort_order',
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertIsString($responseData['message']);
        $this->assertIsArray($responseData['data']['categories']);
    }

    public function test_caching_works_correctly(): void
    {
        // Clear cache before test
        Cache::forget('clothing_categories');

        // First request should hit database and cache result
        $response1 = $this->getJson('/api/clothing-categories');
        $response1->assertStatus(200);

        // Verify cache exists
        $this->assertTrue(Cache::has('clothing_categories'));

        // Second request should hit cache
        $response2 = $this->getJson('/api/clothing-categories');
        $response2->assertStatus(200);

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_cache_invalidation_works(): void
    {
        // Make initial request to populate cache
        $response1 = $this->getJson('/api/clothing-categories');
        $response1->assertStatus(200);

        // Manually clear cache (simulating cache invalidation)
        Cache::forget('clothing_categories');

        // Verify cache is cleared
        $this->assertFalse(Cache::has('clothing_categories'));

        // Next request should repopulate cache
        $response2 = $this->getJson('/api/clothing-categories');
        $response2->assertStatus(200);

        // Verify cache exists again
        $this->assertTrue(Cache::has('clothing_categories'));
    }

    public function test_returns_empty_categories_when_no_data(): void
    {
        // Clear all categories
        ClothingCategory::truncate();
        Cache::forget('clothing_categories');

        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '衣類カテゴリ一覧を取得しました',
                'data' => [
                    'categories' => []
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertCount(0, $responseData['categories']);
    }

    public function test_handles_custom_sort_order(): void
    {
        // Clear existing data and cache
        ClothingCategory::truncate();
        Cache::forget('clothing_categories');

        // Create categories with custom sort order
        ClothingCategory::create(['name' => 'カテゴリC', 'icon_path' => '/icons/c.svg', 'sort_order' => 30]);
        ClothingCategory::create(['name' => 'カテゴリA', 'icon_path' => '/icons/a.svg', 'sort_order' => 10]);
        ClothingCategory::create(['name' => 'カテゴリB', 'icon_path' => '/icons/b.svg', 'sort_order' => 20]);

        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $categories = $responseData['categories'];

        // Verify correct ordering
        $this->assertEquals('カテゴリA', $categories[0]['name']);
        $this->assertEquals(10, $categories[0]['sort_order']);

        $this->assertEquals('カテゴリB', $categories[1]['name']);
        $this->assertEquals(20, $categories[1]['sort_order']);

        $this->assertEquals('カテゴリC', $categories[2]['name']);
        $this->assertEquals(30, $categories[2]['sort_order']);
    }

    public function test_api_is_accessible_without_authentication(): void
    {
        // This endpoint should be publicly accessible
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200);
        
        // Should not require authentication
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_content_type_is_json(): void
    {
        $response = $this->getJson('/api/clothing-categories');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }
}