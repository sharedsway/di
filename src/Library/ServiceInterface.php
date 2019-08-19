<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-17
 * Time: 下午7:55
 */


namespace Sharedsway\Di\Library;
use Sharedsway;


/**
 * Represents a service in the services container
 * Interface ServiceInterface
 * @package Sharedsway\Di
 */
interface ServiceInterface
{
    /**
     * Returns the service's name
     *
     * @param string
     */
    public function getName();

    /**
     * Sets if the service is shared or not
     * @param bool $shared
     */
    public function setShared(bool $shared);

    /**
     * Check whether the service is shared or not
     */
    public function isShared() : bool ;

	/**
     * Set the service definition
     *
     * @param mixed definition
     */
	public function setDefinition($definition);

	/**
     * Returns the service definition
     *
     * @return mixed
     */
	public function getDefinition();

    /**
     * Resolves the service
     * @param null $parameters
     * @param Sharedsway\Di\DiInterface|null $dependencyInjector
     * @return mixed
     */
	public function resolve($parameters = null, Sharedsway\Di\DiInterface $dependencyInjector = null);



    public function isResolved():bool ;

    /**
     * Changes a parameter in the definition without resolve the service
     * @param int $position
     * @param array|null $parameter
     * @return ServiceInterface
     */
	public function setParameter(int $position, ?array $parameter) : ServiceInterface;

    /**
     * Restore the internal state of a service
     * @param array|null $attributes
     * @return ServiceInterface
     */
	public static function __set_state(?array $attributes) : ServiceInterface;

}
