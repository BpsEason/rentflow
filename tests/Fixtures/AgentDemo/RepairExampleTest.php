<?php

namespace Tests\Fixtures\AgentDemo;

use PHPUnit\Framework\TestCase;

final class RepairExampleTest extends TestCase
{
    public function test_discount_is_applied_to_the_subtotal(): void
    {
        $subtotal = 100;
        $discountRate = 0.10;
        $total = $subtotal; // Intentional defect: the discount is not applied.

        $this->assertSame(90, $total, 'A 10% discount on 100 should produce a total of 90.');
    }
}
