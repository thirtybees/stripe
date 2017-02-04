<?php

namespace Stripe;

class BankAccountTest extends TestCase
{
    public function testVerify()
    {
        self::authorizeFromEnv();

        $customer = self::createTestCustomer();

        $bankAccount = $customer->sources->create(
            [
            'source' => [
                'object' => 'bank_account',
                'account_holder_type' => 'individual',
                'account_number' => '000123456789',
                'account_holder_name' => 'John Doe',
                'routing_number' => '110000000',
                'country' => 'US'
            ]
            ]
        );

        $this->assertSame($bankAccount->status, 'new');

        $bankAccount = $bankAccount->verify(
            [
            'amounts' => [32, 45]
            ]
        );

        $this->assertSame($bankAccount->status, 'verified');
    }
}
