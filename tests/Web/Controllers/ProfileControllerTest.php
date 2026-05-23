<?php

namespace Tests\Web\Controllers;

use App\Controllers\Web\ProfileController;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\UpdateProfilePayload;
use App\Payloads\DeleteAccountPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\View\View;
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
        
        // Initialize Twig Views
        View::init(dirname(__DIR__, 3) . '/views');

        // Initialize Drive helper for testing (so upload/drive calls don't crash)
        $config = require dirname(__DIR__, 3) . '/config/drive.php';
        $manager = new DriveManager($config);
        $drive = new Drive($manager);
        drive_set_instance($drive);

        $this->controller = new ProfileController();
        $this->userTable = new UserTable();
    }

    public function testIndexReturnsViewWithAuthenticatedUser(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request();
        $request->setAttribute('user', $user);

        // Ensure session has user_id
        session()->put('user_id', $user->id);

        $response = $this->controller->index($request);

        $this->assertIsString($response);
        $this->assertStringContainsString($user->name, $response);
        $this->assertStringContainsString($user->email, $response);
    }

    public function testUpdateProfileInfoSuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'name'  => 'Updated Test User',
            'email' => 'updated@example.com'
        ]);
        $request->setAttribute('user', $user);

        $payload = new UpdateProfilePayload($request);

        // Clear files array for safety
        $_FILES = [];

        $response = $this->controller->update($payload);

        // Assert it returned a redirect response
        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);

        // Assert user info updated in DB
        $updatedUser = $this->userTable->fetchByIdOrFail(1);
        $this->assertEquals('Updated Test User', $updatedUser->name);
        $this->assertEquals('updated@example.com', $updatedUser->email);

        // Assert session updated
        $this->assertEquals('Updated Test User', session()->get('user_name'));

        // Assert success message is flashed
        $this->assertNotEmpty(session()->getFlash('success'));
    }

    public function testUpdateProfileInfoWithExistingEmailFails(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1); // test@example.com
        // Another user exists in seed: admin@example.com

        $request = new Request([], [
            'name'  => 'Updated Test User',
            'email' => 'admin@example.com' // Already taken by admin user
        ]);
        $request->setAttribute('user', $user);

        $payload = new UpdateProfilePayload($request);

        $response = $this->controller->update($payload);

        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);

        // Assert user info remains unchanged in DB
        $updatedUser = $this->userTable->fetchByIdOrFail(1);
        $this->assertEquals('Test User', $updatedUser->name);
        $this->assertEquals('test@example.com', $updatedUser->email);

        // Assert error message is flashed
        $this->assertNotEmpty(session()->getFlash('error'));
    }

    public function testChangePasswordSuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'current_password' => 'password',
            'password'         => 'newsecurepassword',
            'confirm_password' => 'newsecurepassword'
        ]);
        $request->setAttribute('user', $user);

        $payload = new ChangePasswordPayload($request);

        $response = $this->controller->changePassword($payload);

        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);

        // Fetch user from DB and check password
        $updatedUser = $this->userTable->fetchByIdOrFail(1);
        $this->assertTrue(password_verify('newsecurepassword', $updatedUser->password));

        // Assert success message is flashed
        $this->assertNotEmpty(session()->getFlash('success'));
    }

    public function testChangePasswordFailsWhenCurrentPasswordIsInvalid(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'current_password' => 'wrongpassword',
            'password'         => 'newsecurepassword',
            'confirm_password' => 'newsecurepassword'
        ]);
        $request->setAttribute('user', $user);

        $payload = new ChangePasswordPayload($request);

        $response = $this->controller->changePassword($payload);

        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);

        // Password should remain 'password'
        $updatedUser = $this->userTable->fetchByIdOrFail(1);
        $this->assertTrue(password_verify('password', $updatedUser->password));

        // Assert error message is flashed
        $this->assertNotEmpty(session()->getFlash('error'));
    }

    public function testDeleteAccountSuccessfully(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'password' => 'password'
        ]);
        $request->setAttribute('user', $user);

        // Put dummy session details
        session()->put('user_id', 1);
        session()->put('user_name', 'Test User');

        $payload = new DeleteAccountPayload($request);

        $response = $this->controller->deleteAccount($payload);

        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);

        // Assert user deleted in DB
        $deletedUser = $this->connection->fetchAssociative("SELECT * FROM users WHERE id = 1");
        $this->assertFalse($deletedUser);

        // Assert session is flushed/cleared
        $this->assertFalse(session()->has('user_id'));

        // Assert success deleted message is flashed
        $this->assertNotEmpty(session()->getFlash('success'));
    }

    public function testDeleteAccountFailsWhenPasswordIsInvalid(): void
    {
        $this->seed();

        $user = $this->userTable->fetchByIdOrFail(1);

        $request = new Request([], [
            'password' => 'wrongpassword'
        ]);
        $request->setAttribute('user', $user);

        $payload = new DeleteAccountPayload($request);

        $response = $this->controller->deleteAccount($payload);

        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);

        // User should still exist in DB
        $stillExists = $this->connection->fetchAssociative("SELECT * FROM users WHERE id = 1");
        $this->assertNotEmpty($stillExists);

        // Assert error message is flashed
        $this->assertNotEmpty(session()->getFlash('error'));
    }
}
