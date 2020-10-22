<?php

declare(strict_types=1);

namespace Jasny\Persist\Tests;

use Jasny\Persist\Gateway\GatewayInterface;
use Jasny\Persist\Option\Functions as opt;
use PHPUnit\Framework\TestCase;

/**
 * Test against the Northwind database.
 */
abstract class AbstractNorthwindTest extends TestCase
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

    public function testFetchOrderWithProducts()
    {
        $gateway = $this->getGateway('orders');

        $order = $gateway
            ->fetch(
                ["id" => 44],
                opt\limit(1),
                opt\fields('id', 'order_date', 'details.product', 'details.unit_price'),
                opt\hydrate('product_id')->for('details')
                    ->fields('id', 'product_code', 'product_name'),
            )
            ->first(true);

        $this->assertIsArray($order);

        $expected = [
            'id' => 44,
            'order_date' => new \DateTime('2006-03-24T00:00:00+0000'),
            'details' => [
                [
                    'product' => [
                        'id' => 1,
                        'product_code' => 'NWTB-1',
                        'product_name' => 'Northwind Traders Chai',
                    ],
                    'unit_price' => 18.0,
                ],
                [
                    'product' => [
                        'id' => 43,
                        'product_code' => 'NWTB-43',
                        'product_name' => 'Northwind Traders Coffee',
                    ],
                    'unit_price' => 46.0,
                ],
                [
                    'product' => [
                        'id' => 81,
                        'product_code' => 'NWTB-81',
                        'product_name' => 'Northwind Traders Green Tea',
                    ],
                    'unit_price' => 2.99
                ],
            ],
        ];

        $this->assertEquals($expected, $order);
    }


    public function testFetchCustomerWithOrderCount()
    {
        $gateway = $this->getGateway('customers');

        $customer = $gateway
            ->fetch(
                ["id" => 1],
                opt\limit(1),
                opt\lookup('orders')->count(),
                opt\fields('id', 'company', 'orders')
            )
            ->first(true);

        $this->assertIsArray($customer);
        $this->assertArrayHasKey('orders', $customer);

        $expected = [
            'id' => 1,
            'company' => 'Company A',
            'orders' => 2
        ];

        $this->assertEquals($expected, $customer);
    }

    public function testFetchCustomerWithOrders()
    {
        $gateway = $this->getGateway('customers');

        $customer = $gateway
            ->fetch(
                ["id" => 1],
                opt\limit(1),
                opt\lookup('orders')
                    ->fields('id', 'order_date')
                    ->sort('order_date'),
                opt\lookup('products')->for('orders.details')->as('product')
                    ->fields('id', 'product_code', 'product_name')
                    ->sort('product_code'),
                opt\fields('id', 'company', 'orders')
            )
            ->first(true);

        $this->assertIsArray($customer);
        $this->assertArrayHasKey('orders', $customer);

        $expected = [
            'id' => 1,
            'company' => 'Company A',
            'orders' => [
                [
                    'id' => 44,
                    'order_date' => new \DateTime('2006-03-24T00:00:00+0000'),
                    'details' => [
                        [
                            'product' => [
                                'id' => 1,
                                'product_code' => 'NWTB-1',
                                'product_name' => 'Northwind Traders Chai',
                            ],
                            'unit_price' => 18.0,
                        ],
                        [
                            'product' => [
                                'id' => 43,
                                'product_code' => 'NWTB-43',
                                'product_name' => 'Northwind Traders Coffee',
                            ],
                            'unit_price' => 46.0,
                        ],
                        [
                            'product' => [
                                'id' => 81,
                                'product_code' => 'NWTB-81',
                                'product_name' => 'Northwind Traders Green Tea',
                            ],
                            'unit_price' => 2.99
                        ],
                    ],
                ],
                [
                    'id' => 71,
                    'order_date' => new \DateTime('2006-05-24T00:00:00+0000'),
                    'product' => []
                ]
            ]
        ];

        $this->assertEquals($expected, $customer);
    }
}
