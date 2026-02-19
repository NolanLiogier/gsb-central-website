<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\UserRepository;

final class SqlInjectionTest extends TestCase
{
    public function testGetUserByEmailWithSqlLikeInputDoesNotThrow(): void
    {
        $repo = new UserRepository();
        $malicious = "admin'--";
        $result = $repo->getUserByEmail($malicious);
        $this->assertIsArray($result);
    }

    public function testGetUserByEmailWithSqlLikeInputReturnsEmptyWhenUserNotFound(): void
    {
        $repo = new UserRepository();
        $malicious = "x' OR '1'='1";
        $result = $repo->getUserByEmail($malicious);
        $this->assertEmpty($result);
    }
}
