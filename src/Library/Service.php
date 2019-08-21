<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-17
 * Time: 下午8:15
 */

/*
 +------------------------------------------------------------------------+
 | Code from Phalcon Framework                                                      |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2017 Phalcon Team (https://phalconphp.com)          |
 +------------------------------------------------------------------------+
 | This source file is subject to the New BSD License that is bundled     |
 | with this package in the file LICENSE.txt.                             |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@phalconphp.com so we can send you a copy immediately.       |
 +------------------------------------------------------------------------+
 | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
 |          Eduar Carvajal <eduar@phalconphp.com>                         |
 +------------------------------------------------------------------------+
 */

namespace Sharedsway\Di\Library;

use Sharedsway\Di\DiInterface;
use Sharedsway\Di\Exception;
use Sharedsway\Di\Library\Service\Builder;
use Sharedsway\Di\Library\ServiceInterface;

/**
 * Sharedsway\Di\Service
 *
 * Represents individually a service in the services container
 *
 *<code>
 * $service = new \Sharedsway\Di\Service(
 *     "request",
 *     "Sharedsway\\Http\\Request"
 * );
 *
 * $request = service->resolve();
 *</code>
 */
class Service implements ServiceInterface
{

    protected $_name;

    protected $_definition;

    protected $_shared = false;

    protected $_resolved = false;

    protected $_sharedInstance;

    /**
     * Sharedsway\Di\Service
     *
     * @param string name
     * @param mixed definition
     * @param bool shared
     */
    public final function __construct(?string $name, $definition, bool $shared = false)
    {
        $this->_name       = $name;
        $this->_definition = $definition;
        $this->_shared     = $shared;
    }

    /**
     * Returns the service's name
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Sets if the service is shared or not
     */
    public function setShared(bool $shared): void
    {
        $this->_shared = $shared;
    }

    /**
     * Check whether the service is shared or not
     */
    public function isShared(): bool
    {
        return $this->_shared;
    }

    /**
     * Sets/Resets the shared instance related to the service
     *
     * @param mixed sharedInstance
     */
    public function setSharedInstance($sharedInstance): void
    {
        $this->_sharedInstance = $sharedInstance;
    }

    /**
     * Set the service definition
     *
     * @param mixed definition
     */
    public function setDefinition($definition): void
    {
        $this->_definition = $definition;
    }

    /**
     * Returns the service definition
     *
     * @return mixed
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /**
     * @param $className
     * @param null $params
     * @return mixed|object
     * @throws Exception
     */
    private function makeInstance($className,$params=null)
    {
        try {

            if (is_array($params) && count($params)) {
                $instance = create_instance_params($className, $params);
            } else {
                $instance = create_instance($className);
            }

            return $instance;
        } catch (\Sharedsway\Common\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Resolves the service
     * @param null $parameters
     * @param DiInterface|null $dependencyInjector
     * @return mixed|null|object
     * @throws Exception
     * @throws \ReflectionException
     */
    public function resolve($parameters = null, DiInterface $dependencyInjector = null)
    {

        $shared = $this->_shared;

        /**
         * Check if the service is shared
         */
        if ($shared) {
            $sharedInstance = $this->_sharedInstance;
            if ($sharedInstance !== null) {
                return $sharedInstance;
            }
        }

        $found    = true;
        $instance = null;

        $definition = $this->_definition;
        if (is_string($definition)) {

            /**
             * String definitions can be class names without implicit parameters
             */
            if (class_exists($definition)) {
                $instance = $this->makeInstance($definition, $parameters);
            } else {
                $found = false;
            }
        } else {

            /**
             * Object definitions can be a Closure or an already resolved instance
             */
            if (is_object($definition)) {
                if ($definition instanceof \Closure) {

                    /**
                     * Bounds the closure to the current DI
                     */
                    if (is_object($dependencyInjector)) {
                        $definition = \Closure::bind($definition, $dependencyInjector);
                    }

                    if (is_array($parameters)) {
                        $instance = call_user_func_array($definition, $parameters);
                    } else {
                        $instance = call_user_func($definition);
                    }
                } else {
                    $instance = $definition;
                }
            } else {
                /**
                 * Array definitions require a 'className' parameter
                 */
                if (is_array($definition)) {
                    //
                    $builder  = new Builder();
                    $instance = $builder->build($dependencyInjector, $definition, $parameters);
                } else {
                    $found = false;
                }
            }
        }

        /**
         * If the service can't be $built, we must throw an exception
         */
        if ($found === false) {
            throw new Exception("Service '" . $this->_name . "' cannot be resolved");
        }

        /**
         * Update the shared instance if the service is shared
         */
        if ($shared) {
            $this->_sharedInstance = $instance;
        }

        $this->_resolved = true;

        return $instance;
    }

    /**
     * Changes a parameter in the definition without resolve the service
     */
    public function setParameter(int $position, ?array $parameter): ServiceInterface
    {

        $definition = $this->_definition;
        if (!is_array($definition)) {
            throw new Exception("Definition must be an array to update its parameters");
        }

        /**
         * Update the parameter
         */
        $arguments = $definition["arguments"] ?? null;
        if ($arguments) {
            $arguments[$position] = $parameter;
        } else {
            //这句没懂
            //$arguments = [position: parameter];//
            $arguments = [$position => $parameter];//
        }

        /**
         * Re-update the arguments
         */
        $definition["arguments"] = $arguments;

        /**
         * Re-update the definition
         */
        $this->_definition = $definition;

        return $this;
    }

    /**
     * Returns a parameter in a specific position
     *
     * @param int position
     * @return array
     */
    public function getParameter(int $position)
    {
        $definition = $this->_definition;
        if (!is_array($definition)) {
            throw new Exception("Definition must be an array to obtain its parameters");
        }

        /**
         * Update the parameter
         */
        $arguments = $definition["arguments"] ?? null;
        if ($arguments) {
            $parameter = $arguments[$position] ?? null;
            if ($parameter) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * Returns true if the service was resolved
     */
    public function isResolved(): bool
    {
        return $this->_resolved;
    }

    /**
     * Restore the internal state of a service
     */
    public static function __set_state(?array $attributes): ServiceInterface
    {
        $name = $attributes["_name"] ?? null;
        if (null === $name) {
            throw new Exception("The attribute '_name' is required");
        }
        $definition = $attributes["_definition"] ?? null;
        if (null === $definition) {
            throw new Exception("The attribute '_definition' is required");
        }

        $shared = $attributes["_shared"] ?? null;
        if (null === $shared) {
            throw new Exception("The attribute '_shared' is required");
        }

        return new self($name, $definition, $shared);
    }
}