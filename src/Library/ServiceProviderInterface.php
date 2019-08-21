<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-18
 * Time: 下午5:15
 */
namespace Sharedsway\Di\Library;
use Sharedsway\Di\DiInterface;

/*
 +------------------------------------------------------------------------+
 | Code from Phalcon Framework                                            |
 +------------------------------------------------------------------------+
 | Phalcon Team (https://phalconphp.com)                                  |
 +------------------------------------------------------------------------+
 | Source of Phalcon (https://github.com/phalcon/cphalcon)                |
 +------------------------------------------------------------------------+
 */

interface ServiceProviderInterface
{
    /**
     * Registers a service provider.
     * @param DiInterface $di
     * @return mixed
     */
    public function register(DiInterface $di) ;
}