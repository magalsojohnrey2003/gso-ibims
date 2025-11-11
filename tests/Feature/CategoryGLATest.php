<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class CategoryGLATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user with admin role
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function it_only_returns_ppe_categories_not_glas()
    {
        // Create a PPE category
        $ppe = Category::create([
            'name' => 'ICT Equipment',
            'category_code' => '0500',
            'parent_id' => null,
        ]);

        // Create GLAs under the PPE
        Category::create([
            'name' => 'Computer Hardware',
            'category_code' => '030',
            'parent_id' => $ppe->id,
        ]);

        Category::create([
            'name' => 'Computer Software',
            'category_code' => '031',
            'parent_id' => $ppe->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/admin/api/categories');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data'); // Should only return 1 PPE, not the 2 GLAs
        $response->assertJsonFragment(['name' => 'ICT Equipment']);
        $response->assertJsonMissing(['name' => 'Computer Hardware']);
        $response->assertJsonMissing(['name' => 'Computer Software']);
    }

    #[Test]
    public function it_returns_glas_for_specific_ppe()
    {
        // Create a PPE category
        $ppe = Category::create([
            'name' => 'ICT Equipment',
            'category_code' => '0500',
            'parent_id' => null,
        ]);

        // Create GLAs under the PPE
        $gla1 = Category::create([
            'name' => 'Computer Hardware',
            'category_code' => '030',
            'parent_id' => $ppe->id,
        ]);

        $gla2 = Category::create([
            'name' => 'Computer Software',
            'category_code' => '031',
            'parent_id' => $ppe->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/admin/api/categories/{$ppe->id}/glas");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Computer Hardware', 'category_code' => '030']);
        $response->assertJsonFragment(['name' => 'Computer Software', 'category_code' => '031']);
    }

    #[Test]
    public function it_can_create_gla_under_ppe()
    {
        // Create a PPE category
        $ppe = Category::create([
            'name' => 'ICT Equipment',
            'category_code' => '0500',
            'parent_id' => null,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/admin/api/categories/{$ppe->id}/glas", [
                'name' => 'Printers',
                'category_code' => '040',
            ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Printers', 'category_code' => '040']);
        
        $this->assertDatabaseHas('categories', [
            'name' => 'Printers',
            'category_code' => '040',
            'parent_id' => $ppe->id,
        ]);
    }

    #[Test]
    public function it_can_delete_gla()
    {
        // Create a PPE category
        $ppe = Category::create([
            'name' => 'ICT Equipment',
            'category_code' => '0500',
            'parent_id' => null,
        ]);

        // Create a GLA
        $gla = Category::create([
            'name' => 'Computer Hardware',
            'category_code' => '030',
            'parent_id' => $ppe->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/admin/api/categories/{$ppe->id}/glas/{$gla->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', [
            'id' => $gla->id,
        ]);
    }

    #[Test]
    public function it_prevents_deleting_ppe_with_glas()
    {
        // Create a PPE category
        $ppe = Category::create([
            'name' => 'ICT Equipment',
            'category_code' => '0500',
            'parent_id' => null,
        ]);

        // Create a GLA
        Category::create([
            'name' => 'Computer Hardware',
            'category_code' => '030',
            'parent_id' => $ppe->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/admin/api/categories/" . urlencode($ppe->name));

        // Assert
        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'Cannot delete category with GLA sub-categories']);
        
        $this->assertDatabaseHas('categories', [
            'id' => $ppe->id,
        ]);
    }
}
