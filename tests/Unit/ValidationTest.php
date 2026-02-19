<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testEmailFormatValid(): void
    {
        $valid = ['user@example.com', 'test.user@domain.co.uk', 'a@b.co'];
        foreach ($valid as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
    }

    public function testEmailFormatInvalid(): void
    {
        $invalid = ['notanemail', '@nodomain.com', 'nodomain@', ''];
        foreach ($invalid as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
    }

    public function testPasswordLengthMinimum(): void
    {
        $minLength = 8;
        $short = 'Ab1!';
        $long = 'ValidPass123!';
        $this->assertLessThan($minLength, strlen($short));
        $this->assertGreaterThanOrEqual($minLength, strlen($long));
    }

    public function testForbiddenCharactersRejected(): void
    {
        $forbidden = ['<', '>', '"', "'", ';', '--', '/*'];
        $input = 'normal';
        $hasForbidden = false;
        foreach ($forbidden as $char) {
            if (str_contains($input, $char)) {
                $hasForbidden = true;
                break;
            }
        }
        $this->assertFalse($hasForbidden);
        $inputWithForbidden = "test' OR '1'='1";
        $hasForbidden = false;
        foreach (str_split($inputWithForbidden) as $c) {
            if (in_array($c, ["'", '"', ';', '<', '>'], true)) {
                $hasForbidden = true;
                break;
            }
        }
        $this->assertTrue($hasForbidden);
    }
}
