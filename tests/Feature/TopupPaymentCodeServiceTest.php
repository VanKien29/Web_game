<?php

namespace Tests\Feature;

use App\Services\TopupPaymentCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TopupPaymentCodeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('topup_payment_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nro_account_id')->unique();
            $table->char('code', 15)->unique();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('topup_payment_codes');

        parent::tearDown();
    }

    public function test_each_account_receives_a_stable_opaque_payment_code(): void
    {
        $service = app(TopupPaymentCodeService::class);

        $firstCode = $service->issueForAccountId(101);
        $secondCode = $service->issueForAccountId(101);
        $otherAccountCode = $service->issueForAccountId(202);

        $this->assertSame($firstCode, $secondCode);
        $this->assertNotSame($firstCode, $otherAccountCode);
        $this->assertMatchesRegularExpression(
            '/^NRH[23456789ABCDEFGHJKMNPQRSTVWXYZ]{12}$/',
            $firstCode,
        );
        $this->assertStringNotContainsString('101', $firstCode);
    }

    public function test_payment_content_is_resolved_after_bank_formatting_changes(): void
    {
        $service = app(TopupPaymentCodeService::class);
        $code = $service->issueForAccountId(303);
        $formattedCode = strtolower(
            substr($code, 0, 3).' '.implode('-', str_split(substr($code, 3), 4)),
        );

        $this->assertSame(
            303,
            $service->accountIdFromContent("chuyen tien {$formattedCode} cam on"),
        );
    }

    public function test_unknown_payment_code_is_rejected(): void
    {
        $service = app(TopupPaymentCodeService::class);

        $this->assertNull(
            $service->accountIdFromContent('NRH222222222222'),
        );
        $this->assertNull(
            $service->accountIdFromContent('noi dung khong co ma nap'),
        );
    }
}
