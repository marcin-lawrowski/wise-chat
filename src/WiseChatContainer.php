<?php

/**
 * WiseChat DI container.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatContainer {
	/**
	* @var array Array of created instances
	*/
	private static $instances = array();

	/**
	 * @var array Array of proxy classes
	 */
	private static $proxies = array();

	/**
	* @var array Array of dependencies for classes
	*/
	private static $dependencies = array();

	/**
	* Returns singleton instance of the specified class.
	*
	* @param string $classPathAndName Plugin-relative path and name to the class
	*
	* @return object
	*/
	public static function get($classPathAndName) {
		if (array_key_exists($classPathAndName, self::$instances)) {
			return self::$instances[$classPathAndName];
		}

		self::load($classPathAndName);

		$className = basename($classPathAndName);
		$instance = self::getObjectOfClass($className);

		if ($instance !== null) {
			self::$instances[$classPathAndName] = $instance;
		}

		return $instance;
	}

	/**
	 * Returns a proxy-class object of the specified class. The real instance is created on first use.
	 *
	 * @param string $classPathAndName Plugin-relative path and name to the class
	 *
	 * @return object
	 */
	public static function getLazy($classPathAndName) {
		if (array_key_exists($classPathAndName, self::$instances)) {
			return self::$instances[$classPathAndName];
		}

		if (array_key_exists($classPathAndName, self::$proxies)) {
			return self::$proxies[$classPathAndName];
		}

		self::$proxies[$classPathAndName] = new WiseChatContainerProxyClass($classPathAndName);

		return self::$proxies[$classPathAndName];
	}

    /**
     * Links the given class name with the given object and stores it in container.
     * All the following self::get() and self::getLazy() invocations will return the object.
     *
     * @param string $classPathAndName
     * @param object $instance
     */
    public static function replace($classPathAndName, $instance) {
        if ($instance !== null) {
            self::$instances[$classPathAndName] = $instance;
        }
    }

	/**
	* Loads file containing the class.
	*
	* @param string $classPathAndName Plugin-relative path and name to the class
	*
	* @return null
	* @throws Exception If the file or class was not found
	*/
	public static function load($classPathAndName) {
		$className = basename($classPathAndName);
		if (!class_exists($className, false)) {
			$currentDirectory = dirname(__FILE__);
			$classPath = $currentDirectory.'/'.$classPathAndName.'.php';

			if (!file_exists($classPath)) {
				throw new Exception('File '.$classPath.' was not found');
			}

			require_once($classPath);

			if (!class_exists($className, false)) {
				throw new Exception('Class '.$className.' was not found');
			}
		}
	}

	private static function getObjectOfClass($className) {
		if (!array_key_exists($className, self::$dependencies)) {
			return new $className();
		}

		return null;
	}
}

/**
 * Wise Chat DI proxy class.
 */
class WiseChatContainerProxyClass {
	/**
	 * @var string
	 */
	private $targetClassPathAndName;

	/**
	 * @var object
	 */
	private $targetClassObject;

	/**
	 * WiseChatContainerProxyClass constructor.
	 *
	 * @param string $classPathAndName
	 */
	public function __construct($classPathAndName) {
		$this->targetClassPathAndName = $classPathAndName;
	}

	/**
	 * Proxy method.
	 *
	 * @param string $name
	 * @param mixed $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		if ($this->targetClassObject === null) {
			$this->targetClassObject = WiseChatContainer::get($this->targetClassPathAndName);
		}

		return call_user_func_array(array($this->targetClassObject, $name), $arguments);
	}
}