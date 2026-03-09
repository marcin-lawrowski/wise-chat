<?php

namespace Kainex\WiseChat;

class Container {

	/** @var Container */
	private static $instance;

	/** @var array */
	private $cache;

	/**
	 * Container constructor.
	 */
	private function __construct() {
		$this->cache = [];
	}

	/**
	 * @return Container
	 */
	public static function getInstance(): Container {
		if (!self::$instance) {
			self::$instance = new Container();
		}

		return self::$instance;
	}

	/**
	 * @param string $classNameOrAlias
	 * @return object
	 * @throws \Exception
	 */
	public function get(string $classNameOrAlias) {
		if (isset($this->cache[$classNameOrAlias])) {
			return $this->cache[$classNameOrAlias];
		}

		$reflect  = new \ReflectionClass($classNameOrAlias);
		$instance = $reflect->newInstanceWithoutConstructor(); // NOTICE: to avoid cyclic dependency infinite loop
		$this->cache[$classNameOrAlias] = $instance;

		$dependencies = [];
		if ($reflect->getConstructor()) {
			foreach ($reflect->getConstructor()->getParameters() as $param) {
				if ($param->getType() === null) {
					$dependencies[] = $param->getDefaultValue();
				} else {
					$dependencies[] = $this->get($param->getType()->getName());
				}
			}
		}

		if ($const = $reflect->getConstructor()) {
			$constName = $const->getName();
			call_user_func_array([$instance, $constName], $dependencies);
		}

		return $instance;
	}

	/**
	 * Populates the container with manually created objects.
	 *
	 * @param mixed $object
	 * @param string $alias
	 * @throws \Exception
	 */
	public function set($object, string $alias) {
		if (isset($this->cache[$alias])) {
			throw new \Exception('Alias is already defined: '.$alias);
		}

		$this->cache[$alias] = $object;
	}

}
