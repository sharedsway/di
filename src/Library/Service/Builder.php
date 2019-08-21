<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-17
 * Time: 下午9:10
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

namespace Sharedsway\Di\Library\Service;

use Sharedsway\Di\DiInterface;
use Sharedsway\Di\Exception;

/**
 * Sharedsway\Di\Service\Builder
 *
 * This class builds instances based on complex definitions
 */
class Builder
{

    /**
     * Resolves a constructor/call parameter
     * @param DiInterface $dependencyInjector
     * @param int $position
     * @param array|null $argument
     * @return mixed
     * @throws Exception
     */
    private function _buildParameter(DiInterface $dependencyInjector, int $position, ?array $argument)
    {

        /**
         * All the arguments must have a type
         */
        $type = $argument["type"] ?? null;
        if (!$type) {
            throw new Exception("Argument at position " . $position . " must have a type");
        }

        switch ($type) {

            /**
             * If the argument type is 'service', we obtain the service from the DI
             */
            case "service":
                $name = $argument["name"] ?? null;
                if (!$name) {
                    throw new Exception("Service 'name' is required in parameter on position " . $position);
                }
                if (!is_object($dependencyInjector)) {
                    throw new Exception("The dependency injector container is not valid");
                }
                return $dependencyInjector->get($name);

            /**
             * If the argument type is 'parameter', we assign the value as it is
             */
            case "parameter":
                $value = $argument["value"] ?? null;
                if (!$value) {
                    throw new Exception("Service 'value' is required in parameter on position " . $position);
                }
                return $value;

            /**
             * If the argument type is 'instance', we assign the value as it is
             */
            case "instance":
                $name = $argument["className"] ?? null;
                if (!$name) {
                    throw new Exception("Service 'className' is required in parameter on position " . $position);
                }

                if (!is_object($dependencyInjector)) {
                    throw new Exception("The dependency injector container is not valid");
                }

                $instanceArguments = $argument["arguments"] ?? null;
                if ($instanceArguments) {
                    /**
                     * Build the instance with arguments
                     */
                    return $dependencyInjector->get($name, $instanceArguments);
                }

                /**
                 * The instance parameter does not have arguments for its constructor
                 */
                return $dependencyInjector->get($name);

            default:
                /**
                 * Unknown parameter type
                 */
                throw new Exception("Unknown service type in parameter on position " . $position);
        }
    }

    /**
     * Resolves an array of parameters
     * @param DiInterface $dependencyInjector
     * @param array|null $arguments
     * @return array
     * @throws Exception
     */
    private function _buildParameters(DiInterface $dependencyInjector, ?array $arguments): array
    {

        $buildArguments = [];
        foreach ($arguments as $position => $argument) {
            //for position, argument in arguments {
            $buildArguments[] = $this->_buildParameter($dependencyInjector, $position, $argument);
        }
        return $buildArguments;
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
        } catch (\Sharedsway\Common\Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Builds a service using a complex service definition
     * @param DiInterface $dependencyInjector
     * @param array|null $definition
     * @param null $parameters
     * @return mixed
     * @throws Exception
     */
    public function build(DiInterface $dependencyInjector, ?array $definition, $parameters = null)
    {
        $instance = null;

        /**
         * The class name is required
         */
        $className = $definition["className"] ?? null;
        if (!$className) {
            throw new Exception("Invalid service definition. Missing 'className' parameter");
        }

        if (is_array($parameters)) {

            try {

                /**
                 * Build the instance overriding the definition constructor parameters
                 */
                $this->makeInstance($className, $parameters);
            } catch (\Sharedsway\Common\Exception $exception) {
                throw new Exception($exception->getMessage());
            }

        } else {

            /**
             * Check if the argument has constructor arguments
             */
            $arguments = $definition["arguments"] ?? null;
            if ($arguments) {

                /**
                 * Create the instance based on the parameters
                 */
                $instance = $this->makeInstance($className, $this->_buildParameters($dependencyInjector, $arguments));

            } else {
                $instance = $this->makeInstance($className);
            }
        }

        /**
         * The definition has calls?
         */
        $paramCalls = $definition["calls"] ?? null;
        if ($paramCalls) {

            if (!is_object($instance)) {
                throw new Exception(
                    "The definition has setter injection parameters but the constructor didn't return an instance"
                );
            }

            if (!is_array($paramCalls)) {
                throw new Exception("Setter injection parameters must be an array");
            }

            /**
             * The method call has parameters
             */
            foreach ($paramCalls as $methodPosition => $method) {
                //for methodPosition, method in paramCalls {

                /**
                 * The call parameter must be an array of arrays
                 */
                if (!is_array($method)) {
                    throw new Exception("Method call must be an array on position " . $methodPosition);
                }

                /**
                 * A param 'method' is required
                 */
                $methodName = $method["method"] ?? null;
                if (!$methodName) {
                    throw new Exception("The method name is required on position " . $methodPosition);
                }

                /**
                 * Create the method call
                 */
                $methodCall = [$instance, $methodName];
                $arguments  = $method["arguments"] ?? null;
                if ($arguments) {

                    if (!is_array($arguments)) {
                        throw new Exception("Call arguments must be an array " . $methodPosition);
                    }

                    if (count($arguments)) {

                        /**
                         * Call the method on the instance
                         */
                        call_user_func_array($methodCall, $this->_buildParameters($dependencyInjector, $arguments));

                        /**
                         * Go to next method call
                         */
                        continue;
                    }
                }

                /**
                 * Call the method on the instance without arguments
                 */
                call_user_func($methodCall);
            }
        }

        /**
         * The definition has properties?
         */
        $paramCalls = $definition["properties"] ?? null;
        if ($paramCalls) {

            if (!is_object($instance)) {
                throw new Exception(
                    "The definition has properties injection parameters but the constructor didn't return an instance"
                );
            }

            if (!is_array($paramCalls)) {
                throw new Exception("Setter injection parameters must be an array");
            }

            /**
             * The method call has parameters
             */
            foreach ($paramCalls as $propertyPosition => $property) {
                //for propertyPosition, property in paramCalls {

                /**
                 * The call parameter must be an array of arrays
                 */
                if (!is_array($property)) {
                    throw new Exception("Property must be an array on position " . $propertyPosition);
                }

                /**
                 * A param 'name' is required
                 */
                $propertyName = $property["name"] ?? null;
                if (!$propertyName) {
                    throw new Exception("The property name is required on position " . $propertyPosition);
                }

                /**
                 * A param 'value' is required
                 */
                $propertyValue = $property["value"] ?? null;
                if (!$propertyValue) {
                    throw new Exception("The property value is required on position " . $propertyPosition);
                }

                /**
                 * Update the public property
                 */
                $instance->{$propertyName} = $this->_buildParameter($dependencyInjector, $propertyPosition, $propertyValue);
            }
        }

        return $instance;
    }
}
