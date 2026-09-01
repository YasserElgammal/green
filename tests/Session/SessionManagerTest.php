<?php

namespace Tests\Session;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use YasserElgammal\Green\Session\SessionManager;

class SessionManagerTest extends TestCase
{
    private SessionManager $session;

    protected function setUp(): void
    {
        // Use MockArraySessionStorage to avoid "headers already sent" error in CLI
        $symfonySession = new Session(new MockArraySessionStorage());
        $this->session = new SessionManager($symfonySession);
    }

    public function testGetAndPut()
    {
        $this->session->put('user_id', 1);
        
        $this->assertTrue($this->session->has('user_id'));
        $this->assertEquals(1, $this->session->get('user_id'));
        $this->assertNull($this->session->get('non_existent'));
        $this->assertEquals('default', $this->session->get('non_existent', 'default'));
    }

    public function testForget()
    {
        $this->session->put('name', 'John');
        $this->assertTrue($this->session->has('name'));

        $this->session->forget('name');
        $this->assertFalse($this->session->has('name'));
    }

    public function testFlush()
    {
        $this->session->put('a', 1);
        $this->session->put('b', 2);
        
        $this->session->flush();

        $this->assertFalse($this->session->has('a'));
        $this->assertFalse($this->session->has('b'));
    }

    public function testFlash()
    {
        $this->session->flash('success', 'Profile updated');
        
        // Ensure flash returns the data
        $flash = $this->session->getFlash('success');
        $this->assertIsArray($flash);
        $this->assertEquals(['Profile updated'], $flash);

        // Flash message should be cleared after reading once
        $this->assertEmpty($this->session->getFlash('success'));
    }

    public function testSessionStartsLazilyAndRegeneratesId()
    {
        $this->assertFalse($this->session->isStarted());

        $this->session->put('user_id', 1);
        $id = $this->session->getId();
        $this->assertNotEmpty($id);

        $this->session->regenerateId(true);
        $newId = $this->session->getId();

        $this->assertNotEquals($id, $newId);
    }
}
