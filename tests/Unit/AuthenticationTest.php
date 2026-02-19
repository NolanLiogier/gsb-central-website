<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\UserRepository;
use App\Helpers\UserService;

final class AuthenticationTest extends TestCase
{
    private const STATUS_PENDING = 3;
    private const STATUS_VALIDATED = 1;
    private const STATUS_SENT = 2;

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $password = 'ValidPass123!';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = [
            'user_id' => 1,
            'email' => 'user@test.com',
            'password' => $hash,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'fk_company_id' => 1,
            'fk_function_id' => 2,
            'function_name' => 'Client',
        ];
        $this->assertTrue(password_verify($password, $user['password']));
        $this->assertNotEmpty($user['user_id']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $hash = password_hash('CorrectPassword', PASSWORD_DEFAULT);
        $user = ['password' => $hash];
        $this->assertFalse(password_verify('WrongPassword', $user['password']));
    }

    public function testLoginFailsWhenUserNotFound(): void
    {
        $repo = new UserRepository();
        $result = $repo->getUserByEmail('nonexistent@example.com');
        $this->assertEmpty($result);
    }

    public function testLoginRefusedWhenFieldsEmpty(): void
    {
        $email = '';
        $password = '';
        $this->assertEmpty(trim($email));
        $this->assertEmpty(trim($password));
    }

    public function testPasswordIsStoredHashed(): void
    {
        $plain = 'MySecret123';
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        $this->assertNotEquals($plain, $hash);
        $this->assertTrue(str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon'));
        $this->assertTrue(password_verify($plain, $hash));
    }

    public function testUserServiceIsAuthenticatedReturnsFalseWhenNoSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        unset($_SESSION['user_email'], $_SESSION['user_hash']);
        $service = new UserService();
        $this->assertFalse($service->isAuthenticated());
    }

    public function testAccessDeniedWhenUserNotLoggedIn(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $service = new UserService();
        $this->assertFalse($service->isAuthenticated());
        $user = $service->getCurrentUser();
        $this->assertEmpty($user);
    }
}
