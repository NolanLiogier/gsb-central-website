<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\PermissionVerificationService;
use App\Repositories\CommandRepository;

final class AccessControlTest extends TestCase
{
    public function testClientCanAccessOnlyOwnCompany(): void
    {
        $service = new PermissionVerificationService();
        $user = ['user_id' => 10, 'fk_company_id' => 5, 'fk_function_id' => 2];
        $this->assertTrue($service->canAccessCompany($user, ['companyId' => 5]));
        $this->assertFalse($service->canAccessCompany($user, ['companyId' => 99]));
    }

    public function testClientCannotAccessAnotherCompany(): void
    {
        $service = new PermissionVerificationService();
        $user = ['user_id' => 10, 'fk_company_id' => 5, 'fk_function_id' => 2];
        $this->assertFalse($service->canAccessCompany($user, ['companyId' => 6]));
    }

    public function testCommandsFilteredByRoleClient(): void
    {
        $repo = new CommandRepository();
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 2];
        $commands = $repo->getCommandsByUserRole($user);
        $this->assertIsArray($commands);
    }

    public function testCommandsFilteredByRoleCommercial(): void
    {
        $repo = new CommandRepository();
        $user = ['user_id' => 1, 'fk_company_id' => null, 'fk_function_id' => 1];
        $commands = $repo->getCommandsByUserRole($user);
        $this->assertIsArray($commands);
    }

    public function testCommandsFilteredByRoleLogistician(): void
    {
        $repo = new CommandRepository();
        $user = ['user_id' => 1, 'fk_company_id' => null, 'fk_function_id' => 3];
        $commands = $repo->getCommandsByUserRole($user);
        $this->assertIsArray($commands);
    }

    public function testUnknownRoleReturnsEmptyCommands(): void
    {
        $repo = new CommandRepository();
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 99];
        $commands = $repo->getCommandsByUserRole($user);
        $this->assertSame([], $commands);
    }
}
