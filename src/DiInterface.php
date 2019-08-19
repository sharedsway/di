<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-17
 * Time: 下午7:19
 */


/*
 +------------------------------------------------------------------------+
 | Phalcon Framework                                                      |
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

namespace Sharedsway\Di;
use Sharedsway\Di\Library;


/**
 * Phalcon\DiInterface
 *
 * Interface for Phalcon\Di
 */
interface DiInterface extends \ArrayAccess
{


    /**
     * Registers a service in the services container
     * @param null|string $name
     * @param $definition
     * @param bool $shared
     * @return Library\ServiceInterface
     */
    public function set(?string $name, $definition, bool $shared = false) : Library\ServiceInterface;

    /**
     * Registers an "always shared" service in the services container
     * @param null|string $name
     * @param $definition
     * @return Library\ServiceInterface
     */
	public function setShared(?string $name, $definition) : Library\ServiceInterface;

    /**
     * Removes a service in the services container
     * @param null|string $name
     * @return mixed
     */
	public function remove(?string $name);

    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     * @param null|string $name
     * @param $definition
     * @param bool $shared
     * @return Library\ServiceInterface| bool
     */
	public function attempt(?string $name, $definition, bool $shared = false) ;

	/**
     * Resolves the service based on its configuration
     *
     * @param string name
     * @param array parameters
     * @return mixed
     */
	public function get(?string $name, $parameters = null);

	/**
     * Returns a shared service based on their configuration
     *
     * @param string name
     * @param array parameters
     * @return mixed
     */
	public function getShared(?string $name, $parameters );

    /**
     * Sets a service using a raw Phalcon\Di\Service definition
     * @param null|string $name
     * @param Library\ServiceInterface $rawDefinition
     * @return Library\ServiceInterface
     */
	public function setRaw(?string $name, Library\ServiceInterface $rawDefinition) : Library\ServiceInterface;

	/**
     * Returns a service definition without resolving
     *
     * @param string name
     * @return mixed
     */
	public function getRaw(?string $name);

    /**
     * Returns the corresponding Phalcon\Di\Service instance for a service
     * @param null|string $name
     * @return Library\ServiceInterface
     */
	public function getService(?string $name) : Library\ServiceInterface;

    /**
     * Check whether the DI contains a service by a name
     * @param null|string $name
     * @return bool
     */
	public function has(?string $name) :bool;

	/**
     * Check whether the last service obtained via getShared produced a fresh instance or an existing one
     */
	public function wasFreshInstance() :  bool ;

    /**
     * Return the services registered in the DI
     * @return Library\ServiceInterface[]
     */
	public function getServices() : array;

    /**
     * Registers a service provider.
     *
     * <code>
     * use Phalcon\DiInterface;
     * use Phalcon\Di\ServiceProviderInterface;
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
     * @param Library\ServiceProviderInterface $provider
     * @return mixed
     */
    public function register(Library\ServiceProviderInterface $provider);

    /**
     * Set a default dependency injection container to be obtained into static methods
     * @param DiInterface $dependencyInjector
     * @return mixed
     */
	public static function setDefault(DiInterface $dependencyInjector);

    /**
     * Return the last DI created
     * @return DiInterface
     */
	public static function getDefault() : DiInterface;

	/**
     * Resets the internal default DI
     */
	public static function reset();
}
