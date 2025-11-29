<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class AdminCancelDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    #[Test]
    public function cancel_dispatch_endpoint_is_disabled()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->postJson('/admin/borrow-requests/1/cancel-dispatch');
        $response->assertStatus(404);
    }
}
