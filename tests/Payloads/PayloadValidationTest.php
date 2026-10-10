<?php

namespace Tests\Payloads;

use App\Payloads\ChangePasswordPayload;
use App\Payloads\DeleteAccountPayload;
use App\Payloads\LoginPayload;
use App\Payloads\RegisterPayload;
use App\Payloads\UpdateProfilePayload;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\ValidationException;

class PayloadValidationTest extends TestCase
{
    public static function invalidPayloads(): array
    {
        return [
            'login email' => [LoginPayload::class, ['email' => 'bad', 'password' => 'password'], 'email'],
            'login password' => [LoginPayload::class, ['email' => 'user@example.com', 'password' => ''], 'password'],
            'register name' => [RegisterPayload::class, ['name' => 'A', 'email' => 'new@example.com', 'password' => 'password'], 'name'],
            'register password' => [RegisterPayload::class, ['name' => 'User', 'email' => 'new@example.com', 'password' => 'short'], 'password'],
            'profile email' => [UpdateProfilePayload::class, ['name' => 'User', 'email' => 'bad'], 'email'],
            'change confirmation' => [ChangePasswordPayload::class, ['current_password' => 'old-password', 'password' => 'new-password', 'confirm_password' => 'different'], 'confirm_password'],
            'delete password' => [DeleteAccountPayload::class, ['password' => ''], 'password'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function testInvalidPayloadsReportTheExpectedField(string $payloadClass, array $data, string $field): void
    {
        try {
            new $payloadClass(new Request([], $data));
            $this->fail('Expected payload validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->getErrors());
        }
    }

    public function testPayloadExposesOnlyValidatedFields(): void
    {
        $payload = new LoginPayload(new Request([], ['email' => 'user@example.com', 'password' => 'password', 'admin' => true]));
        $this->assertSame(['email' => 'user@example.com', 'password' => 'password'], $payload->validated());
    }
}
