<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-18
 * Time: 上午12:31
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

namespace Sharedsway\Di\Library;
use Sharedsway\Di\DiInterface;

/**
 * Sharedsway\\Di\Di\InjectionAwareInterface
 *
 * This interface must be implemented in those classes that uses internally the Sharedsway\\Di\Di that creates them
 */
interface InjectionAwareInterface
{

    /**
     * Sets the dependency injector
     * @param DiInterface $dependencyInjector
     * @return mixed
     */
    public function setDI(DiInterface $dependencyInjector);

    /**
     * Returns the internal dependency injector
     * @return DiInterface
     */
	public function getDI() : DiInterface ;
}
