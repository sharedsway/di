<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-17
 * Time: 下午7:14
 */

/*
 +------------------------------------------------------------------------+
 | Code from Phalcon Framework                                            |
 +------------------------------------------------------------------------+
 | Phalcon Team (https://phalconphp.com)                                  |
 +------------------------------------------------------------------------+
 | Source of Phalcon (https://github.com/phalcon/cphalcon)                |
 +------------------------------------------------------------------------+
 */

namespace Sharedsway\Di;

use Sharedsway\Di\Library\Service;
use Sharedsway\Di\Library\ServiceInterface;
use Sharedsway\Di\Library\InjectionAwareInterface;
use Sharedsway\Event;
use Sharedsway\Di\Library\ServiceProviderInterface;
use Sharedsway\Common;


class Di implements DiInterface
{
    /**
     * List of registered services
     * @var ServiceInterface[]
     */
    protected $_services;

    /**
     * List of shared instances
     */
    protected $_sharedInstances;

    /**
     * To know if the latest resolved instance was shared or not
     */
    protected $_freshInstance = false;

    /**
     * Events Manager
     *
     * @var Event\ManagerInterface
     */
    protected $_eventsManager;

    /**
     * Latest DI build
     */
    protected static $_default;

    /**
     * Sharedsway\Di\Di constructor
     *
     * 想了一下，把这部分加上
     *
     * 在swoole中使用时，需要重写该方法，避免使用静态变量
     *
     */
    public function __construct()
    {
        $di = self::$_default;
        if (!$di) {
            self::$_default = $this;
        }
    }


    /**
     * Sets the internal event manager
     * @param Event\ManagerInterface $eventsManager
     */
    public function setInternalEventsManager(Event\ManagerInterface $eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }


    /**
     * Returns the internal event manager
     * @return Event\ManagerInterface
     */
    public function getInternalEventsManager(): Event\ManagerInterface
    {
        return $this->_eventsManager;
    }


    /**
     * Registers a service in the services container
     * @param null|string $name
     * @param $definition
     * @param bool $shared
     * @return ServiceInterface
     */
    public function set(?string $name, $definition, bool $shared = false): ServiceInterface
    {
        $service                = new Service($name, $definition, $shared);
        $this->_services[$name] = $service;
        return $service;
    }


    /**
     * Registers an "always shared" service in the services container
     * @param null|string $name
     * @param $definition
     * @return ServiceInterface
     */
    public function setShared(?string $name, $definition): ServiceInterface
    {
        return $this->set($name, $definition, true);
    }

    /**
     */

    /**
     * Removes a service in the services container
     * It also removes any shared instance created for the service
     * @param null|string $name
     * @return mixed|void
     */
    public function remove(?string $name)
    {
        unset($this->_services[$name]);
        unset($this->_sharedInstances[$name]);
    }


    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     * @param null|string $name
     * @param $definition
     * @param bool $shared
     * @return ServiceInterface|bool
     */
    public function attempt(?string $name, $definition, bool $shared = false)
    {

        if (!isset($this->_services[$name])) {
            $service = new Service($name, $definition, $shared);

            $this->_services[$name] = $service;
            return $service;
        }

        return false;
    }

    /**
     * Sets a service using a raw Sharedsway\\Di\Di\Service definition
     * @param null|string $name
     * @param ServiceInterface $rawDefinition
     * @return ServiceInterface
     */
    public function setRaw(?string $name, ServiceInterface $rawDefinition): ServiceInterface
    {
        $this->_services[$name] = $rawDefinition;
        return $rawDefinition;
    }

    /**
     * Returns a service definition without resolving
     * @param null|string $name
     * @return mixed
     * @throws \Sharedsway\Di\Exception
     */
    public function getRaw(?string $name)
    {
        $service = $this->_services[$name] ?? null;
        if ($service) {
            return $service->getDefinition();
        }

        throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
    }

    /**
     * Returns a Sharedsway\\Di\Di\Service instance
     * @param null|string $name
     * @return ServiceInterface
     * @throws \Sharedsway\Di\Exception
     */
    public function getService(?string $name): ServiceInterface
    {
        $service = $this->_services[$name] ?? null;
        if ($service) {
            return $service;
        }

        throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
    }

    /**
     * @param $className
     * @param null $params
     * @return mixed|\stdClass
     * @throws Exception
     */
    private function makeInstance($className, $params = null)
    {
        try {

            if (is_array($params) && count($params)) {
                $instance = create_instance_params($className, $params);
            } else {
                $instance = create_instance($className);
            }

            return $instance;
        } catch (Common\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Resolves the service based on its configuration
     * @param null|string $name
     * @param null $parameters
     * @return mixed|null|\stdClass
     * @throws \Sharedsway\Di\Exception
     */
    public function get(?string $name, $parameters = null)
    {

        $instance = null;

        $eventsManager = $this->_eventsManager;

        if (is_object($eventsManager)) {
            $instance = $eventsManager->fire(
                "di:beforeServiceResolve",
                $this,
                ["name" => $name, "parameters" => $parameters]
            );
        }

        if (!is_object($instance)) {
            $service = $this->_services[$name] ?? null;
            if ($service) {
                /**
                 * The service is registered in the DI
                 */
                $instance = $service->resolve($parameters, $this);
            } else {
                /**
                 * The DI also acts as builder for any class even if it isn't defined in the DI
                 */
                if (!class_exists($name)) {
                    throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
                }

                $instance = $this->makeInstance($name, $parameters);
            }
        }

        /**
         * Pass the DI itself if the instance implements \Sharedsway\\Di\Di\InjectionAwareInterface
         */
        if (is_object($instance)) {
            if ($instance instanceof InjectionAwareInterface) {
                $instance->setDI($this);
            }
        }

        if (is_object($eventsManager)) {
            $eventsManager->fire(
                "di:afterServiceResolve",
                $this,
                [
                    "name"       => $name,
                    "parameters" => $parameters,
                    "instance"   => $instance
                ]
            );
        }


        return $instance;
    }


    /**
     * Resolves a service, the resolved service is stored in the DI, subsequent
     * requests for $this service will return the same instance
     * @param null|string $name
     * @param null $parameters
     * @return mixed|null|\stdClass
     * @throws \Sharedsway\Di\Exception
     */
    public function getShared(?string $name, $parameters = null)
    {

        /**
         * $this method provides a first level to shared instances allowing to use non-shared services as shared
         */
        $instance = $this->_sharedInstances[$name] ?? null;
        if ($instance) {
            $this->_freshInstance = false;
        } else {

            /**
             * Resolve the instance normally
             */
            $instance = $this->get($name, $parameters);

            /**
             * Save the instance in the first level shared
             */
            $this->_sharedInstances[$name] = $instance;
            $this->_freshInstance          = true;
        }

        return $instance;
    }

    /**
     * Check whether the DI contains a service by a name
     * @param null|string $name
     * @return bool
     */
    public function has(?string $name): bool
    {
        return isset($this->_services[$name]);
    }

    /**
     * Check whether the last service obtained via getShared produced a fresh instance or an existing one
     * @return bool
     */
    public function wasFreshInstance(): bool
    {
        return $this->_freshInstance;
    }

    /**
     * Return the services registered in the DI
     * @return Library\ServiceInterface[]
     */
    public function getServices(): array
    {
        return $this->_services;
    }

    /**
     * Check if a service is registered using the array syntax
     * @param mixed $name
     * @return bool
     */
    public function offsetExists($name): bool
    {
        return $this->has($name);
    }


    /**
     * Allows to register a shared service using the array syntax
     *
     *<code>
     * $di["request"] = new \Sharedsway\\Di\Http\Request();
     *</code>
     * @param mixed $name
     * @param mixed $definition
     * @return bool
     */
    public function offsetSet($name, $definition): bool
    {
        $this->setShared($name, $definition);
        return true;
    }


    /**
     * Allows to obtain a shared service using the array syntax
     *
     *<code>
     * var_dump($di["request"]);
     *</code>
     * @param mixed $name
     * @return mixed|null|\stdClass
     * @throws \Sharedsway\Di\Exception
     */
    public function offsetGet($name)
    {
        return $this->getShared($name);
    }

    /**
     * Removes a service from the services container using the array syntax
     * @param mixed $name
     * @return bool
     */
    public function offsetUnset($name): bool
    {
        return false;
    }


    /**
     * Magic method to get or set services using setters/getters
     * @param null|string $method
     * @param null $arguments
     * @return mixed|null|\stdClass
     * @throws \Sharedsway\Di\Exception
     */
    public function __call(?string $method, $arguments = null)
    {
        /**
         * If the magic method starts with "get" we try to get a service with that name
         */
        if (Common\Text::startsWith($method, "get")) {
            $services = $this->_services;

            $possibleService = lcfirst(substr($method, 3));
            if (isset ($services[$possibleService])) {
                if (count($arguments)) {
                    $instance = $this->get($possibleService, $arguments);
                } else {
                    $instance = $this->get($possibleService);
                }
                return $instance;
            }
        }

        /**
         * If the magic method starts with "set" we try to set a service using that name
         */
        if (Common\Text::startsWith($method, "set")) {
            $definition = $arguments[0] ?? null;
            if ($definition) {
                $this->set(lcfirst(substr($method, 3)), $definition);
                return null;
            }
        }

        /**
         * The method doesn't start with set/get throw an exception
         */
        throw new Exception("Call to undefined method or service '" . $method . "'");
    }


    /**
     * Registers a service provider.
     *
     * <code>
     * use Sharedsway\\Di\DiInterface;
     * use Sharedsway\\Di\Di\ServiceProviderInterface;
     *
     * class SomeServiceProvider implements ServiceProviderInterface
     * {
     *     public function register(DiInterface $di)
     *     {
     *         $di->setShared('service', function () {
     *             // ...
     *         });
     *     }
     * }
     * </code>
     * @param ServiceProviderInterface $provider
     */
    public function register(ServiceProviderInterface $provider)
    {
        $provider->register($this);
    }


    /**
     * Set a default dependency injection container to be obtained into static methods
     *
     * 想了一下，把这部分加上
     *
     * 在swoole中使用时，需要重写该方法，避免使用静态变量
     *
     * @param \Sharedsway\Di\DiInterface $dependencyInjector
     * @return mixed|void
     */
    public static function setDefault(DiInterface $dependencyInjector)
    {
        self::$_default = $dependencyInjector;
    }


    /**
     * Return the latest DI created
     *
     * 想了一下，把这部分加上
     *
     * 在swoole中使用时，需要重写该方法，避免使用静态变量
     *
     * @return \Sharedsway\Di\DiInterface
     */
    public static function getDefault(): DiInterface
    {
        return self::$_default;
    }


    /**
     * Resets the internal default DI
     *
     * 想了一下，把这部分加上
     *
     * 在swoole中使用时，需要重写该方法，避免使用静态变量
     */
    public static function reset()
    {
        self::$_default = null;
    }


}
