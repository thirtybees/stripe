<?php

namespace Stripe;

class DiscountTest extends TestCase
{
    public function testDeletion()
    {
        self::authorizeFromEnv();
        $id = 'test-coupon-' . self::generateRandomString(20);
        $coupon = Coupon::create(
            [
                'percent_off' => 25,
                'duration' => 'repeating',
                'duration_in_months' => 5,
                'id' => $id,
            ]
        );
        $customer = self::createTestCustomer(['coupon' => $id]);

        $this->assertTrue(isset($customer->discount));
        $this->assertTrue(isset($customer->discount->coupon));
        $this->assertSame($id, $customer->discount->coupon->id);

        $customer->deleteDiscount();
        $this->assertFalse(isset($customer->discount));

        $customer = Customer::retrieve($customer->id);
        $this->assertFalse(isset($customer->discount));
    }
}
