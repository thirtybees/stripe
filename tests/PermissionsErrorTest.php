<?php

namespace Stripe;

class PermissionErrorTest extends TestCase
{
    private function permissionErrorResponse()
    {
        return [
            'error' => [],
        ];
    }

    /**
     * @expectedException Stripe\Error\Permission
     */
    public function testPermission()
    {
        $this->mockRequest('GET', '/v1/accounts/acct_DEF', [], $this->permissionErrorResponse(), 403);
        Account::retrieve('acct_DEF');
    }
}
