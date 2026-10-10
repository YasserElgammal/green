<?php

namespace Tests\Api\Controllers;

use App\Controllers\Api\ProfileController;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\DeleteAccountPayload;
use App\Payloads\UpdateProfilePayload;
use App\Tables\UserTable;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;

class ProfileControllerTest extends TestCase
{
    private ProfileController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProfileController();
    }

    public function testShowReturnsAuthenticatedUser(): void
    {
        $user = $this->createUser();
        $request = new Request();
        $request->setAttribute('user', $user);

        $response = $this->controller->show($request);
        $data = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user->email, $data['data']['item']['email']);
    }

    public function testUpdateChangesProfile(): void
    {
        $user = $this->createUser();
        $request = new Request([], [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
        ]);
        $request->setAttribute('user', $user);

        $response = $this->controller->update(new UpdateProfilePayload($request));
        $updated = (new UserTable())->fetchByIdOrFail($user->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Updated User', $updated->name);
        $this->assertSame('updated@example.com', $updated->email);
    }

    public function testUpdateRejectsAnotherUsersEmail(): void
    {
        $user = $this->createUser();
        $this->createUser('other@example.com');
        $request = new Request([], [
            'name' => 'Updated User',
            'email' => 'other@example.com',
        ]);
        $request->setAttribute('user', $user);

        $response = $this->controller->update(new UpdateProfilePayload($request));
        $data = $this->json($response->getContent());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('That email address is already in use.', $data['errors']['email'][0]);
    }

    public function testChangePasswordSucceeds(): void
    {
        $user = $this->createUser();
        $request = new Request([], [
            'current_password' => 'password',
            'password' => 'new-password',
            'confirm_password' => 'new-password',
        ]);
        $request->setAttribute('user', $user);

        $response = $this->controller->changePassword(new ChangePasswordPayload($request));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(password_verify(
            'new-password',
            (new UserTable())->fetchByIdOrFail($user->id)->password,
        ));
    }

    public function testChangePasswordRejectsIncorrectCurrentPassword(): void
    {
        $user = $this->createUser();
        $request = new Request([], [
            'current_password' => 'incorrect',
            'password' => 'new-password',
            'confirm_password' => 'new-password',
        ]);
        $request->setAttribute('user', $user);

        $response = $this->controller->changePassword(new ChangePasswordPayload($request));

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testDeleteRemovesAccount(): void
    {
        $user = $this->createUser();
        $request = new Request([], ['password' => 'password']);
        $request->setAttribute('user', $user);

        $response = $this->controller->delete(new DeleteAccountPayload($request));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull((new UserTable())->fetchById($user->id));
    }

    public function testDeleteRejectsIncorrectPassword(): void
    {
        $user = $this->createUser();
        $request = new Request([], ['password' => 'incorrect']);
        $request->setAttribute('user', $user);

        $response = $this->controller->delete(new DeleteAccountPayload($request));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertNotNull((new UserTable())->fetchById($user->id));
    }

    private function json(string $content): array
    {
        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
