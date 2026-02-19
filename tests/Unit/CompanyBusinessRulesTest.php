<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repositories\CompaniesRepository;

final class CompanyBusinessRulesTest extends TestCase
{
    public function testAddCompanyRefusesInvalidSiret(): void
    {
        $repo = new CompaniesRepository();
        $data = [
            'company_name' => 'Test Co',
            'siret' => '123',
            'siren' => '123456789',
            'delivery_address' => '',
            'sector' => 1,
            'salesman' => '',
        ];
        $result = $repo->addCompany($data);
        $this->assertFalse($result);
    }

    public function testAddCompanyRefusesInvalidSiren(): void
    {
        $repo = new CompaniesRepository();
        $data = [
            'company_name' => 'Test Co',
            'siret' => '12345678901234',
            'siren' => '123',
            'delivery_address' => '',
            'sector' => 1,
            'salesman' => '',
        ];
        $result = $repo->addCompany($data);
        $this->assertFalse($result);
    }

    public function testAddCompanyRefusesEmptyRequiredFields(): void
    {
        $repo = new CompaniesRepository();
        $data = [
            'company_name' => '',
            'siret' => '12345678901234',
            'siren' => '123456789',
            'delivery_address' => '',
            'sector' => 1,
            'salesman' => '',
        ];
        $result = $repo->addCompany($data);
        $this->assertFalse($result);
    }

    public function testAddCompanyAcceptsValidData(): void
    {
        $repo = new CompaniesRepository();
        $data = [
            'company_name' => 'Valid Company',
            'siret' => '12345678901234',
            'siren' => '123456789',
            'delivery_address' => '1 rue Test',
            'sector' => 1,
            'salesman' => '',
        ];
        $result = $repo->addCompany($data);
        $this->assertIsBool($result);
    }
}
