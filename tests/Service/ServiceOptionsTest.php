<?php

namespace atlas\LaravelCircuitBreaker\Tests\Service;

use atlas\LaravelCircuitBreaker\Service\ServiceOptions;
use PHPUnit\Framework\TestCase;

class ServiceOptionsTest extends TestCase
{
    public function testServiceOptionsConstruction()
    {
        $options = ServiceOptions::createFromOptions(1, 2, 3);

        $this->assertEquals(1, $options->getAttemptsThreshold());
        $this->assertEquals(2, $options->getAttemptsTtl());
        $this->assertEquals(3, $options->getFailureTtl());
    }
}
