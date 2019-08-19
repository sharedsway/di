<?php namespace Sharedsway\Test\Di;
use Sharedsway\Di\Di;
use Sharedsway\Test\UnitTester;

class ServiceCest
{
    public function _before(UnitTester $I)
    {
    }

    // tests
    public function testResolvingService(UnitTester $I)
    {
        $di = new Di();
        $di->set('resolved', function () {
            return new SomeService();
        });
        $di->set('notResolved', function () {
            return new SomeService();
        });

        $I->assertFalse($di->getService('resolved')->isResolved());
        $I->assertFalse($di->getService('notResolved')->isResolved());
        $di->get('resolved');
        $I->assertTrue($di->getService('resolved')->isResolved());
        $I->assertFalse($di->getService('notResolved')->isResolved());
    }
}

class SomeService
{
}

