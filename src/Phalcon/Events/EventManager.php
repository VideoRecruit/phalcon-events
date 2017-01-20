<?php

namespace VideoRecruit\Phalcon\Events;

use Doctrine\Common\EventSubscriber;
use Kdyby;
use Phalcon\DiInterface;

/**
 * Event manager which is able to lazy load listeners at the time they're called.
 *
 * This package is inspired by Kdyby\Events package.
 *
 * @package VideoRecruit\Phalcon\Events
 */
class EventManager extends Kdyby\Events\EventManager
{

	/**
	 * @var array
	 */
	private $listenersList;

	/**
	 * @var DiInterface
	 */
	private $di;

	/**
	 * EventManager constructor.
	 *
	 * @param array $listeners
	 * @param DiInterface $di
	 */
	public function __construct(array $listeners, DiInterface $di)
	{
		$this->listenersList = $listeners;
		$this->di = $di;
	}

	/**
	 * @param string $eventName
	 * @return EventSubscriber[]
	 */
	public function getListeners($eventName = NULL)
	{
		if (!empty($this->listenersList[$eventName])) {
			$this->initializeListener($eventName);
		}

		if ($eventName === NULL) {
			while (($type = key($this->listenersList)) !== NULL) {
				$this->initializeListener($type);
			}
		}

		return parent::getListeners($eventName);
	}

	/**
	 * @param string $eventName
	 */
	private function initializeListener($eventName)
	{
		foreach ($this->listenersList[$eventName] as $serviceName) {
			$listener = $this->di->get($serviceName);

			if ($listener instanceof \Closure) {
				$this->addEventListener($eventName, $listener);
			} elseif ($listener instanceof EventSubscriber) {
				$this->addEventSubscriber($listener);
			}
		}

		unset($this->listenersList[$eventName]);
	}
}
