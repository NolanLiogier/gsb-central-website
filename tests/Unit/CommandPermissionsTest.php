<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\CommandRepository;

final class CommandRepositoryStub extends CommandRepository
{
    private array $command;

    public function __construct(array $command)
    {
        $this->command = $command;
    }

    public function getCommandById(int $commandId): array
    {
        return $this->command;
    }
}

final class CommandPermissionsTest extends TestCase
{
    private const STATUS_PENDING = 3;
    private const STATUS_VALIDATED = 1;
    private const STATUS_SENT = 2;

    public function testClientCannotModifyValidatedCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_VALIDATED, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 2];
        $this->assertFalse($repo->canUserPerformAction($user, 1, 'modify'));
    }

    public function testClientCanModifyPendingCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_PENDING, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 2];
        $this->assertTrue($repo->canUserPerformAction($user, 1, 'modify'));
    }

    public function testClientCannotDeleteValidatedCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_VALIDATED, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 2];
        $this->assertFalse($repo->canUserPerformAction($user, 1, 'delete'));
    }

    public function testClientCanDeletePendingCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_PENDING, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 2];
        $this->assertTrue($repo->canUserPerformAction($user, 1, 'delete'));
    }

    public function testDeleteForbiddenWhenCommandAlreadySent(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_SENT, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => 1, 'fk_function_id' => 2];
        $this->assertFalse($repo->canUserPerformAction($user, 1, 'delete'));
    }

    public function testLogisticianCanSendValidatedCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_VALIDATED, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => null, 'fk_function_id' => 3];
        $this->assertTrue($repo->canUserPerformAction($user, 1, 'send'));
    }

    public function testLogisticianCannotValidateCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_PENDING, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => null, 'fk_function_id' => 3];
        $this->assertFalse($repo->canUserPerformAction($user, 1, 'validate'));
    }

    public function testLogisticianCannotSendPendingCommand(): void
    {
        $command = ['command_id' => 1, 'fk_status_id' => self::STATUS_PENDING, 'fk_user_id' => 1];
        $repo = new CommandRepositoryStub($command);
        $user = ['user_id' => 1, 'fk_company_id' => null, 'fk_function_id' => 3];
        $this->assertFalse($repo->canUserPerformAction($user, 1, 'send'));
    }

    public function testUpdateCommandStatusValidateReturnsBoolean(): void
    {
        $repo = new CommandRepository();
        $result = $repo->updateCommandStatus(0, 'validate');
        $this->assertIsBool($result);
    }

    public function testUpdateCommandStatusSendReturnsBoolean(): void
    {
        $repo = new CommandRepository();
        $result = $repo->updateCommandStatus(0, 'send');
        $this->assertIsBool($result);
    }
}
