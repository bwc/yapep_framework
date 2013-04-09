<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package    YapepBase
 * @subpackage Autoloader
 * @copyright  2011 The YAPEP Project All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace YapepBase\Autoloader;


/**
 * Autoloader base abstract class
 *
 * Be careful when you are extending this class, as probably at that point you wont have an autoloader,
 * so you have to take care of file include yourself.
 *
 * @package    YapepBase
 * @subpackage Autoloader
 */
abstract class AutoloaderAbstract {

	/**
	 * The class paths to use
	 *
	 * @var array
	 */
	protected $classPaths = array();

	/**
	 * The class paths to use for given namespace prefixes.
	 *
	 * The key is tha namespace prefix, and the value is the path
	 *
	 * @var array
	 */
	protected $classPathsWithNamespace = array();

	/**
	 * Adds a path to use on class loading.
	 *
	 * @param string $path             The path to use.
	 * @param string $forceNameSpace   A full namespace. If given all the classes in a namespace having
	 *                                    this prefix will be searched at this path only.
	 *
	 * @return AutoloaderAbstract
	 */
	public function addClassPath($path, $forceNameSpace = null) {
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		if (!is_null($forceNameSpace)) {

			$forceNameSpace = ltrim($forceNameSpace, '\\');
			$this->classPathsWithNamespace[$forceNameSpace] = $path;
		}
		else {
			$this->classPaths[] = $path;
		}

		return $this;
	}

	/**
	 * Loads the specified class if it can be found by name.
	 *
	 * @param string $className   Name of the class.
	 *
	 * @return bool   TRUE if the class was loaded, FALSE if it can't be loaded.
	 */
	abstract public function load($className);
}