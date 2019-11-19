<?php

namespace soutorafaelbr\LaravelCircuitBreaker\Tests\Manager;

use soutorafaelbr\LaravelCircuitBreaker\Service\ServiceOptions;
use soutorafaelbr\LaravelCircuitBreaker\Service\ServiceOptionsResolver;
use soutorafaelbr\LaravelCircuitBreaker\Events\AttemptFailed;
use soutorafaelbr\LaravelCircuitBreaker\Events\ServiceFailed;
use soutorafaelbr\LaravelCircuitBreaker\Events\ServiceRestored;
use soutorafaelbr\LaravelCircuitBreaker\Manager\CircuitBreakerManager;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use soutorafaelbr\LaravelCircuitBreaker\Store\CircuitBreakerStoreInterface;

class CircuitBreakerManagerTest extends TestCase
{
    /** @var CircuitBreakerStoreInterface | MockObject */
    private $storeMock;

    /** @var Dispatcher | MockObject */
    private $dispatcherMock;

    /** @var ServiceOptionsResolver | MockObject */
    private $serviceOptionsResolver;

    /** @var CircuitBreakerManager */
    private $manager;

    public function setUp()
    {
        parent::setUp();

        $this->storeMock = $this->getMockBuilder(CircuitBreakerStoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcherMock = $this->getMockBuilder(Dispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceOptionsResolver = $this->getMockBuilder(ServiceOptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceOptionsResolver->expects($this->any())
            ->method('getOptionsFor')
            ->with('service')
            ->willReturn(ServiceOptions::createFromOptions(3, 1000, 5000));

        $this->manager = new CircuitBreakerManager($this->storeMock, $this->dispatcherMock, $this->serviceOptionsResolver);
    }

    public function testItShouldGetServiceAvailability()
    {
        $this->storeMock->expects($this->at(0))
            ->method('isAvailable')
            ->with('service')
            ->willReturn(false);

        $this->storeMock->expects($this->at(1))
            ->method('isAvailable')
            ->with('service')
            ->willReturn(true);

        $this->assertFalse($this->manager->isAvailable('service'));
        $this->assertTrue($this->manager->isAvailable('service'));
    }

    public function testItShouldReportFailedAttempt()
    {
        $this->storeMock->expects($this->any())
            ->method('isAvailable')
            ->with('service')
            ->willReturn(false);

        $this->storeMock->expects($this->once())
            ->method('reportFailure')
            ->with('service', 3, 1000, 5000);

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof AttemptFailed;
            }));

        $this->manager->reportFailure('service');
    }

    public function testItShouldReportSuccessfulAttempt()
    {
        $this->storeMock->expects($this->once())
            ->method('reportSuccess')
            ->with('service');

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof ServiceRestored;
            }));

        $this->manager->reportSuccess('service');
    }

    public function testItShouldReportFailedService()
    {
        $this->storeMock->expects($this->any())
            ->method('isAvailable')
            ->with('service')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->storeMock->expects($this->once())
            ->method('reportFailure')
            ->with('service', 3, 1000, 5000);

        $this->dispatcherMock->expects($this->at(0))
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof AttemptFailed;
            }));

        $this->dispatcherMock->expects($this->at(1))
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof ServiceFailed;
            }));

        $this->manager->reportFailure('service');
    }
}
