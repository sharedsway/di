<?php namespace Sharedsway\Test;

use Sharedsway\Di\Di;
use Sharedsway\Di\DiInterface;
use Sharedsway\Di\Exception;
use Sharedsway\Di\Library\Service;
use Sharedsway\Di\Library\ServiceProviderInterface;
use Sharedsway\Test\UnitTester;

class DiCest
{
    /**
     * @var DiInterface
     */
    protected $phDi;

    public function _before(UnitTester $I)
    {
        $this->phDi = new Di();
    }

    /**
     * Tests registering a service via string
     * @param \Sharedsway\Test\UnitTester $I
     * @throws \Sharedsway\Di\Exception
     */
    public function testSetString(UnitTester $I)
    {


        //系统自带
        $this->phDi->set('std', 'stdClass');
        $I->assertEquals('stdClass', get_class($this->phDi->get('std')));

        //自定义
        $exampleClass = DiCestExampleA::class;
        $this->phDi->set('example', $exampleClass);
        $I->assertEquals($exampleClass, get_class($this->phDi->get('example')));
    }

    public function testSetAnonymousFunction(UnitTester $I)
    {


        //系统自带
        $this->phDi->set('std', function () {
            return new \stdClass();
        });
        $I->assertEquals('stdClass', get_class($this->phDi->get('std')));

        //自定义
        $exampleClass = __CLASS__ . 'ExampleA';
        $this->phDi->set('example', function () {
            return new DiCestExampleA();
        });
        $I->assertEquals($exampleClass, get_class($this->phDi->get('example')));
    }

    public function testSetArray(UnitTester $I)
    {


        $this->phDi->set('std', [
            'className' => 'stdClass'
        ]);
        $I->assertEquals('stdClass', get_class($this->phDi->get('std')));

        $this->phDi->set('example', [
            'className' => DiCestExampleA::class
        ]);
        $I->assertEquals(DiCestExampleA::class, get_class($this->phDi->get('example')));
    }

    public function testAttempt(UnitTester $I)
    {

        $this->phDi->set('std', function () {
            return new \stdClass();
        });

        $this->phDi->attempt('std', function () {
            return new DiCestExampleA();
        });

        $this->phDi->attempt('example', function () {
            return new DiCestExampleA();
        });


        $this->phDi->attempt('example2', function () {
            return new \stdClass();
        });
        $this->phDi->attempt('example2', function () {
            return new DiCestExampleA();
        });

        $I->assertEquals(\stdClass::class, get_class($this->phDi->get('std')));
        $I->assertEquals(DiCestExampleA::class, get_class($this->phDi->get('example')));
        $I->assertEquals(\stdClass::class, get_class($this->phDi->get('example2')));

    }

    public function testHas(UnitTester $I)
    {

        $this->phDi->set('std', function () {
            return new \stdClass();
        });

        $I->assertTrue($this->phDi->has('std'));
        $I->assertFalse($this->phDi->has('std1'));
    }

    public function testGetShared(UnitTester $I)
    {

        $this->phDi->set('dateObject', function () {
            $object       = new \stdClass();
            $object->date = microtime(true);
            return $object;
        });

        $dateObject = $this->phDi->getShared('dateObject');
        usleep(5000);
        $dateObject2 = $this->phDi->getShared('dateObject');

        $I->assertEquals($dateObject2, $dateObject);
        $I->assertEquals($dateObject2->date, $dateObject->date);
    }

    public function testMagicGetCall(UnitTester $I)
    {


        $this->phDi->set('std', \stdClass::class);
        $I->assertEquals(\stdClass::class, get_class($this->phDi->getStd()));

        $this->phDi->set('example', DiCestExampleA::class);
        $I->assertEquals(DiCestExampleA::class, get_class($this->phDi->getExample()));
    }

    public function testMagicSetCall(UnitTester $I)
    {


        $this->phDi->setStd(\stdClass::class);
        $I->assertEquals(\stdClass::class, get_class($this->phDi->get('std')));

        $this->phDi->setExample(DiCestExampleA::class);
        $I->assertEquals(DiCestExampleA::class, get_class($this->phDi->get('example')));

    }

    public function testSetParameters(UnitTester $I)
    {
        $this->phDi->set('example1', function ($v) {
            return new DiCestExampleB($v);
        });

        $this->phDi->set('example2', DiCestExampleB::class);

        $example = $this->phDi->get('example1', ['Jim']);
        $I->assertEquals('Jim', $example->name);


        $example = $this->phDi->get('example2', ['Tony']);
        $I->assertEquals('Tony', $example->name);


    }

    public function testGetServices(UnitTester $I)
    {
        $expectedServices = [
            'std'      => Service::__set_state([
                '_name'           => 'std',
                '_definition'     => 'stdClass',
                '_shared'         => false,
                '_sharedInstance' => null,
            ]),
            'service2' => Service::__set_state([
                '_name'           => 'service2',
                '_definition'     => 'hello-world',
                '_shared'         => false,
                '_sharedInstance' => null,
            ])
        ];

        $this->phDi->set('std', 'stdClass');
        $this->phDi->set('service2', 'hello-world');

        $I->assertEquals($expectedServices, $this->phDi->getServices());
    }

    public function testGetRawService(UnitTester $I)
    {
        $this->phDi->set('service1', 'some-service');
        $I->assertEquals('some-service', $this->phDi->getRaw('service1'));
    }

    public function testRegisteringViaArrayAccess(UnitTester $I)
    {
        $this->phDi['simple'] = DiCestExampleA::class;
        $I->assertEquals(DiCestExampleA::class, get_class($this->phDi->get('simple')));
    }

    public function testResolvingViaArrayAccess(UnitTester $I)
    {
        $this->phDi->set('simple', DiCestExampleA::class);
        $I->assertEquals(DiCestExampleA::class, get_class($this->phDi['simple']));
    }

    public function testGettingNonExistentService(UnitTester $I)
    {
        $I->expectException(Exception::class, function () {
            $this->phDi->get('nonExistentService');
        });
    }

    public function testGettingDiViaGetDefault(UnitTester $I)
    {
        // 暂时不做
    }

    public function testComplexInjection(UnitTester $I)
    {
        $example = new DiCestExampleA();
        $this->phDi->set('example', $example);

        // Injection of parameters in the constructor
        $this->phDi->set(
            'simpleConstructor',
            [
                'className' => InjectableComponent::class,
                'arguments' => [
                    [
                        'type' => 'parameter',
                        'value' => 'example'
                    ],
                ]
            ]
        );

        // Injection of simple setters
        $this->phDi->set(
            'simpleSetters',
            [
                'className' => InjectableComponent::class,
                'calls' => [
                    [
                        'method' => 'setExample',
                        'arguments' => [
                            [
                                'type' => 'parameter',
                                'value' => 'example'
                            ],
                        ]
                    ],
                ]
            ]
        );

        // Injection of properties
        $this->phDi->set(
            'simpleProperties',
            [
                'className' => InjectableComponent::class,
                'properties' => [
                    [
                        'name' => 'example',
                        'value' => [
                            'type' => 'parameter',
                            'value' => 'example'
                        ]
                    ],
                ]
            ]
        );

        // Injection of parameters in the constructor resolving the service parameter
        $this->phDi->set(
            'complexConstructor',
            [
                'className' => InjectableComponent::class,
                'arguments' => [
                    [
                        'type' => 'service',
                        'name' => 'example'
                    ]
                ]
            ]
        );

        // Injection of simple setters resolving the service parameter
        $this->phDi->set(
            'complexSetters',
            [
                'className' => InjectableComponent::class,
                'calls' => [
                    [
                        'method' => 'setExample',
                        'arguments' => [
                            [
                                'type' => 'service',
                                'name' => 'example',
                            ]
                        ]
                    ],
                ]
            ]
        );

        // Injection of properties resolving the service parameter
        $this->phDi->set(
            'complexProperties',
            [
                'className' => InjectableComponent::class,
                'properties' => [
                    [
                        'name' => 'example',
                        'value' => [
                            'type' => 'service',
                            'name' => 'example',
                        ]
                    ],
                ]
            ]
        );


        $component = $this->phDi->get('simpleConstructor');
        $I->assertTrue(is_string($component->getExample()));
        $I->assertEquals('example', $component->getExample());

        $component = $this->phDi->get('simpleSetters');
        $I->assertTrue(is_string($component->getExample()));
        $I->assertEquals('example', $component->getExample());


        $component = $this->phDi->get('simpleProperties');
        $I->assertTrue(is_string($component->getExample()));
        $I->assertEquals('example', $component->getExample());


        $component = $this->phDi->get('complexConstructor');
        $I->assertTrue(is_object($component->getExample()));
        $I->assertEquals($example, $component->getExample());

        $component = $this->phDi->get('complexSetters');
        $I->assertTrue(is_object($component->getExample()));
        $I->assertEquals($example, $component->getExample());

        $component = $this->phDi->get('complexProperties');
        $I->assertTrue(is_object($component->getExample()));
        $I->assertEquals($example, $component->getExample());
    }

    public function testRegistersServiceProvider(UnitTester $I)
    {
        $this->phDi->register(new SomeServiceProvider());
        $I->assertEquals('bar', $this->phDi['foo']);

        $service = $this->phDi->get('fooAction');
        $I->assertInstanceOf(DiCestExampleB::class, $service);
    }

    public function testYamlLoader(UnitTester $I)
    {

    }

    public function testSetShare(UnitTester $I)
    {
        $obj = new DiCestExampleB('Jim');
        $this->phDi->setShared('hello', $obj);
        $obj2 = $this->phDi->get('hello');
        $obj3 = $this->phDi->getShared('hello');

        $I->assertEquals($obj, $obj2);
        $I->assertEquals($obj, $obj3);
    }
}

class DiCestExampleA
{

}

class DiCestExampleB
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

class InjectableComponent
{
    public $example;

    public $other;

    public function __construct($example = null)
    {
        $this->example = $example;
    }

    public function setExample($example)
    {
        $this->example = $example;
    }

    public function getExample()
    {
        return $this->example;
    }
}


class SomeServiceProvider implements ServiceProviderInterface
{
    public function register(DiInterface $di)
    {

        $di['foo'] = function () {
            return 'bar';
        };

        $di['fooAction'] = function () {
            return new DiCestExampleB('phalcon');
        };
    }
}
