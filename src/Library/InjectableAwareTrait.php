<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-21
 * Time: 上午9:10
 */

namespace Sharedsway\Di\Library;

use Sharedsway\Di\Di;
use Sharedsway\Di\DiInterface;
use Sharedsway\Di\Exception;

trait InjectableAwareTrait
{

    /**
     * Dependency Injector
     *
     * @var DiInterface
     */
    protected $_dependencyInjector;


    /**
     * Sets the dependency injector
     * @param DiInterface $dependencyInjector
     * @return mixed|void
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }


    /**
     * Returns the internal dependency injector
     * @return DiInterface
     */
    public function getDI(): DiInterface
    {

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = Di::getDefault();
        }

        return $dependencyInjector;
    }


    /**
     * Magic method __get
     * @param string $propertyName
     * @return mixed|null|DiInterface
     * @throws Exception
     */
    public function __get(string $propertyName)
    {
        $dependencyInjector = $this->getDI();
        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injection object is required to access the application services");
        }

        /**
         * Fallback to the PHP userland if the cache is not available
         */
        if ($dependencyInjector->has($propertyName)) {
            $service               = $dependencyInjector->getShared($propertyName);
            $this->{$propertyName} = $service;
            return $service;
        }

        if ($propertyName == "di") {
            $this->{"di"} = $dependencyInjector;
            return $dependencyInjector;
        }

        /**
         * Accessing the persistent property will create a session bag on any class
         */
        //这部分不用
//        if ($propertyName == "persistent") {
//            $persistent = (BagInterface)$dependencyInjector->get("sessionBag", [get_class($this)]),
//				$this->{
//                "persistent"} = $persistent;
//			return $persistent;
//		}

        /**
         * A notice is shown if the property is not defined and isn't a valid service
         */
        trigger_error("Access to undefined property " . $propertyName);
        return null;
    }
}
