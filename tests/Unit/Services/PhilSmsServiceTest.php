<?php

namespace Tests\Unit\Services;

use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use App\Models\User;
use App\Services\PhilSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhilSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.philsms.base_url' => 'https://sms.test',
            'services.philsms.sender_id' => 'PRIMARY',
            'services.philsms.fallback_sender_id' => 'BACKUP',
            'services.philsms.admin_numbers' => null,
        ]);
    }

    public function test_notify_admins_borrow_request_uses_fallback_sender_when_primary_not_authorized(): void
    {
        config(['services.philsms.api_key' => 'test-key']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09123456789',
        ]);

        $borrower = User::factory()->create([
            'role' => 'user',
            'first_name' => 'Carla',
            'last_name' => 'Santos',
        ]);

        $borrowRequest = BorrowRequest::factory()->create([
            'user_id' => $borrower->id,
            'borrow_date' => now()->addDay(),
            'return_date' => now()->addDays(2),
        ]);

        $item = Item::factory()->create();

        BorrowRequestItem::factory()->for($borrowRequest, 'request')->for($item)->create([
            'quantity' => 2,
        ]);

        $calls = [];

        Http::fake(function ($request) use (&$calls) {
            $calls[] = $request;

            if (count($calls) === 1) {
                return Http::response(['message' => 'Sender ID not authorized'], 404);
            }

            return Http::response(['status' => 'ok'], 200);
        });

        app(PhilSmsService::class)->notifyAdminsBorrowRequest($borrowRequest);

        Http::assertSentCount(2);

        $this->assertCount(2, $calls);

        $firstPayload = $calls[0]->data();
        $secondPayload = $calls[1]->data();

        $this->assertSame('https://sms.test/sms/send', $calls[0]->url());
        $this->assertSame('PRIMARY', $firstPayload['sender_id'] ?? null);
        $this->assertSame('+639123456789', $firstPayload['recipient'] ?? null);
        $this->assertStringContainsString('GSO IBIMS', $firstPayload['message'] ?? '');

        $this->assertSame('BACKUP', $secondPayload['sender_id'] ?? null);
        $this->assertSame('+639123456789', $secondPayload['recipient'] ?? null);
    }

    public function test_notify_admins_borrow_request_does_nothing_when_service_disabled(): void
    {
        config(['services.philsms.api_key' => null]);

        Http::fake();

        $borrowRequest = BorrowRequest::factory()->create();

        app(PhilSmsService::class)->notifyAdminsBorrowRequest($borrowRequest);

        Http::assertNothingSent();
    }
}
