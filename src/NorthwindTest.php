<?php

declare(strict_types=1);

namespace Persist\Tests;

use Persist\Gateway\GatewayInterface;
use Persist\Option\Functions as opt;
use PHPUnit\Framework\TestCase;

/**
 * Test against the Northwind database.
 */
abstract class NorthwindTest extends TestCase
{
    /**
     * Get the gateway for a table/collections.
     *
     * @param string $name  Table or collection name
     * @return GatewayInterface
     */
    abstract protected function getGateway(string $name): GatewayInterface;

    public function testFetchOrderWithCustomer()
    {
        $gateway = $this->getGateway('orders');

        $order = $gateway
            ->fetch(
                ["_id" => 30],
                opt\limit(1),
                opt\hydrate('customer_id'),
                opt\hydrate('employee_id')->fields('id', 'last_name', 'first_name'),
            )
            ->first(true);

        $this->assertIsArray($order);

        $this->assertArrayHasKey('customer', $order);
        $this->assertArrayNotHasKey('customer_id', $order);

        $this->assertArrayHasKey('employee', $order);
        $this->assertArrayNotHasKey('employee_id', $order);

        $expected = [
            'id' => 30,
            'order_date' => new \DateTime('2006-01-15T00:00:00+00:00'),
            'shipped_date' => new \DateTime('2006-01-22T00:00:00+00:00'),
            'shipper_id' => 2,
            'ship_name' => 'Karen Toh',
            'ship_address' => '789 27th Street',
            'ship_city' => 'Las Vegas',
            'ship_state_province' => 'NV',
            'ship_zip_postal_code' => 99999,
            'ship_country_region' => 'USA',
            'shipping_fee' => 200.0,
            'taxes' => 0.0,
            'payment_type' => 'Check',
            'paid_date' => new \DateTime('2006-01-15T00:00:00+00:00'),
            'tax_rate' => 0,
            'status_id' => 3,
            'details' => [
                [
                    'product_id' => 34,
                    'quantity' => 100.0,
                    'unit_price' => 14.0,
                    'discount' => 0,
                    'status_id' => 2,
                    'purchase_order_id' => 96,
                    'inventory_id' => 83,
                ],
                [
                    'product_id' => 80,
                    'quantity' => 30.0,
                    'unit_price' => 3.5,
                    'discount' => 0,
                    'status_id' => 2,
                    'inventory_id' => 63,
                ],
            ],
            'customer' => [
                'id' => 27,
                'company' => 'Company AA',
                'last_name' => 'Toh',
                'first_name' => 'Karen',
                'job_title' => 'Purchasing Manager',
                'business_phone' => '(123)555-0100',
                'fax_number' => '(123)555-0101',
                'address' => '789 27th Street',
                'city' => 'Las Vegas',
                'state_province' => 'NV',
                'zip_postal_code' => 99999,
                'country_region' => 'USA',
            ],
            'employee' => [
                'id' => 9,
                'last_name' => 'Hellung-Larsen',
                'first_name' => 'Anne',
            ],
        ];

        $this->assertEquals($expected, $order);
    }
}
