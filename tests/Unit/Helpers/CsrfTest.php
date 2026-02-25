<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Clinica\Helpers\Csrf;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Initialize an empty session array to simulate a PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testGenerateTokenCreatesCorrectLengthToken()
    {
        $token = Csrf::generateToken();
        // bin2hex(random_bytes(32)) generates a 64 character hex string
        $this->assertEquals(64, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function testGenerateTokenReusesExistingToken()
    {
        $token1 = Csrf::generateToken();
        $token2 = Csrf::generateToken();

        $this->assertEquals($token1, $token2);
    }

    public function testValidateTokenReturnsTrueForValidToken()
    {
        $token = Csrf::generateToken();
        $this->assertTrue(Csrf::validateToken($token));
    }

    public function testValidateTokenReturnsFalseForInvalidToken()
    {
        Csrf::generateToken(); // Ensure token is set in session
        $this->assertFalse(Csrf::validateToken('invalid-token-here'));
        $this->assertFalse(Csrf::validateToken(null));
        $this->assertFalse(Csrf::validateToken(''));
    }
}
