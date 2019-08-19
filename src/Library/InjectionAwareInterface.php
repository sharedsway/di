<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-18
 * Time: 上午12:31
 */
namespace Sharedsway\Di\Library;
use Sharedsway\Di\DiInterface;

/**
 * Phalcon\Di\InjectionAwareInterface
 *
 * This interface must be implemented in those classes that uses internally the Phalcon\Di that creates them
 */
interface InjectionAwareInterface
{

    /**
     * Sets the dependency injector
     */
    public function setDI(DiInterface $dependencyInjector);

    /**
     * Returns the internal dependency injector
     * @return DiInterface
     */
	public function getDI() : DiInterface ;
}
