<?php

namespace VideoRecruit\Phalcon\Events\DI;

use Doctrine\Common\EventSubscriber;
use Kdyby\Events\Event;
use Phalcon\Config;
use VideoRecruit\Phalcon\DI\Container;
use VideoRecruit\Phalcon\Events\EventManager;
use VideoRecruit\Phalcon\Events\InvalidArgumentException;
use VideoRecruit\Phalcon\Events\InvalidStateException;
use VideoRecruit\Phalcon\Events\MemberAccessException;

/**
 * Class EventsExtension
 *
 * @package VideoRecruit\Phalcon\Events\DI
 */
class EventsExtension
{
	const TAG_SUBSCRIBER = 'videorecruit.events.subscriber';
	const EVENT_MANAGER = 'videorecruit.events.manager';

	/**
	 * @var Container
	 */
	private $di;

	/**
	 * @var array
	 */
	private $listeners = [];

	/**
	 * DoctrineOrmExtension constructor.
	 *
	 * @param Container $di
	 * @param array|Config $config
	 * @throws InvalidArgumentException
	 */
	public function __construct(Container $di, $config)
	{
		$this->di = $di;

		if ($config instanceof Config) {
			$config = $config->toArray();
		} elseif (!is_array($config)) {
			throw new InvalidArgumentException('Config has to be either an array or ' .
				'a configuration service name within the DI container.');
		}

		$this->loadManager($config);
	}

	/**
	 * Register events extension.
	 *
	 * @param Container $di
	 * @param array|Config $config
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function register(Container $di, $config)
	{
		return new self($di, $config);
	}

	/**
	 * @param array $config
	 * @throws InvalidStateException
	 * @throws MemberAccessException
	 */
	private function loadManager(array $config)
	{
		$container = $this->di;
		$tagName = self::TAG_SUBSCRIBER;

		$container->setShared(self::EVENT_MANAGER, function () use ($container, $tagName) {
			foreach ($services = $container->getServicesByTag($tagName) as $service) {
				if (!is_array($service->getDefinition())) {
					throw new InvalidStateException(sprintf('Listeners have to be defined using an array syntax. %s listener is defined in different way.', $service->getName()));
				}

				$serviceName = $service->getName();
				$eventNames = [];
				$listenerClassName = $service->getDefinition()['className'];
				$listener = self::createInstanceWithoutConstructor($listenerClassName);

				// exception message
				$msg = 'Event listener %s::%s() is not implemented.';

				// create event map
				foreach ($listener->getSubscribedEvents() as $eventName => $params) {
					if (is_numeric($eventName) && is_string($params)) { // [EventName, ...]
						list(, $method) = Event::parseName($params);
						$eventNames[] = ltrim($params, '\\');

						if (!method_exists($listener, $method)) {
							throw new MemberAccessException(sprintf($msg, $listenerClassName, $method));
						}
					} elseif (is_string($eventName)) { // [EventName => ???, ...]
						$eventNames[] = ltrim($eventName, '\\');

						if (is_string($params)) { // [EventName => method, ...]
							if (!method_exists($listener, $params)) {
								throw new MemberAccessException(sprintf($msg, $listenerClassName, $params));
							}
						} elseif (is_string($params[0])) { // [EventName => [method, priority], ...]
							if (!method_exists($listener, $params[0])) {
								throw new MemberAccessException(sprintf($msg, $listenerClassName, $params[0]));
							}
						} else {
							foreach ($params as $listener) { // [EventName => [[method, priority], ...], ...]
								if (!method_exists($listener, $listener[0])) {
									throw new MemberAccessException(sprintf($msg, $listenerClassName, $listener[0]));
								}
							}
						}
					}
				}

				$this->listeners[$serviceName] = array_unique($eventNames);
				$listeners = [];

				// optimize listeners
				foreach ($this->listeners as $serviceName => $eventNames) {
					foreach ($eventNames as $eventName) {
						$listeners[$eventName][] = $serviceName;
					}
				}
				foreach ($listeners as $id => $subscribers) {
					$listeners[$id] = array_unique($subscribers);
				}
			}

			return new EventManager($listeners, $this);
		});
	}

	/**
	 * @param string $className
	 * @return EventSubscriber
	 */
	private static function createInstanceWithoutConstructor($className)
	{
		return (new \ReflectionClass($className))->newInstanceWithoutConstructor();
	}
}
