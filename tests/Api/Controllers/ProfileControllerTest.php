<?php

namespace Tests\Api\Controllers;

use App\Controllers\Api\ProfileController;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\UpdateProfilePayload;
use App\Payloads\DeleteAccountPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Drive\DriveManager;
use YasserElgammal\Green\Drive\Drive;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    private ProfileController $controller;
    private UserTable $userTable;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize Drive helper for testing (so upload/drive calls don't crash)
        $config = require dirname(__DIR__, 3) . '/config/drive.php';
        $manager = new DriveManager($config);
        $drive = new Drive($manager);
        $this->app->instance(Drive::class, $drive);

        $this->controller = new ProfileController();
        $this->userTable = new UserTable();
    }

    public function testShowReturnsAuthenticatedUserJson(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request();
        $request->setAttribute('user', $user);

        $response = $this->controller->show($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals($user->name, $data['data']['item']['name']);
        $this->assertEquals($user->email, $data['data']['item']['email']);
    }

    public function testUpdateProfileSuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'name'  => 'Updated API User',
            'email' => 'updatedapi@example.com'
        ]);
        $request->setAttribute('user', $user);

        $payload = new UpdateProfilePayload($request);
        $_FILES = [];

        $response = $this->controller->update($payload);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Updated API User', $data['data']['item']['name']);
        $this->assertEquals('updatedapi@example.com', $data['data']['item']['email']);

        // Assert DB state
        $updatedUser = $this->userTable->fetchByIdOrFail(1);
        $this->assertEquals('Updated API User', $updatedUser->name);
        $this->assertEquals('updatedapi@example.com', $updatedUser->email);
    }

    public function testUpdateProfileWithDuplicateEmailFails(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'name'  => 'Updated API User',
            'email' => 'admin@example.com' // taken
        ]);
        $request->setAttribute('user', $user);

        $payload = new UpdateProfilePayload($request);

        $response = $this->controller->update($payload);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Email already in use.', $data['message']);
        $this->assertEquals('Email already in use.', $data['errors']['email'][0]);
    }

    public function testUpdateWithPostSuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'name'  => 'Updated API User 2',
            'email' => 'updatedapi2@example.com'
        ]);
        $request->setAttribute('user', $user);

        $payload = new UpdateProfilePayload($request);
        $_FILES = [];

        $response = $this->controller->updateWithPost($payload);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Updated API User 2', $data['data']['item']['name']);
        $this->assertEquals('updatedapi2@example.com', $data['data']['item']['email']);
    }

    public function testChangePasswordSuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'current_password' => 'password',
            'password'         => 'newsecurepassword123',
            'confirm_password' => 'newsecurepassword123'
        ]);
        $request->setAttribute('user', $user);

        $payload = new ChangePasswordPayload($request);

        $response = $this->controller->changePassword($payload);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Password changed successfully.', $data['message']);

        // Assert DB state
        $updatedUser = $this->userTable->fetchByIdOrFail(1);
        $this->assertTrue(password_verify('newsecurepassword123', $updatedUser->password));
    }

    public function testChangePasswordWithInvalidCurrentPasswordFails(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'current_password' => 'wrongpassword',
            'password'         => 'newsecurepassword123',
            'confirm_password' => 'newsecurepassword123'
        ]);
        $request->setAttribute('user', $user);

        $payload = new ChangePasswordPayload($request);

        $response = $this->controller->changePassword($payload);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('The provided current password does not match our records.', $data['message']);
        $this->assertEquals('The provided current password does not match our records.', $data['errors']['current_password'][0]);
    }

    public function testDestroySuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'password' => 'password'
        ]);
        $request->setAttribute('user', $user);

        $payload = new DeleteAccountPayload($request);

        $response = $this->controller->destroy($payload);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Account deleted successfully.', $data['message']);

        // Assert DB state
        $deleted = $this->connection->fetchAssociative("SELECT * FROM users WHERE id = 1");
        $this->assertFalse($deleted);
    }

    public function testDestroyWithInvalidPasswordFails(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'password' => 'wrongpassword'
        ]);
        $request->setAttribute('user', $user);

        $payload = new DeleteAccountPayload($request);

        $response = $this->controller->destroy($payload);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('The provided current password does not match our records.', $data['message']);
        $this->assertEquals('The provided current password does not match our records.', $data['errors']['password'][0]);
    }
}
