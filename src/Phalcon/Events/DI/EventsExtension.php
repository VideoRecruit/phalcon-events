<?php

namespace VideoRecruit\Phalcon\Events\DI;

use Kdyby\Events\EventManager;
use Phalcon\Config;
use VideoRecruit\Phalcon\DI\Container;
use VideoRecruit\Phalcon\Events\InvalidArgumentException;

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
	 */
	private function loadManager(array $config)
	{
		$container = $this->di;
		$tagName = self::EVENT_MANAGER;

		$container->setShared(self::EVENT_MANAGER, function () use ($container, $tagName) {
			$services = $container->getServicesByTag($tagName);
			$eventsManager = new EventManager;

			foreach ($services as $service) {
				$eventsManager->addEventSubscriber($service->resolve());
			}
		});
	}
}
