<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-21
 * Time: 上午9:10
 */

namespace Sharedsway\Di\Library;

use Sharedsway\Event;

abstract class Injectable implements InjectionAwareInterface, Event\EventsAwareInterface
{

    use Event\EventAwareTrait;

    use InjectableAwareTrait;

}
