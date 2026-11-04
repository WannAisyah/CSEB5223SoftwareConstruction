<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('module-products')]
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_product_catalog(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/products');
    }
}
