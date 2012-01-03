<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package      YapepBase
 * @subpackage   DependencyInjection
 * @author       Zsolt Szeberenyi <szeber@yapep.org>
 * @copyright    2011 The YAPEP Project All rights reserved.
 * @license      http://www.opensource.org/licenses/bsd-license.php BSD License
 */


namespace YapepBase\DependencyInjection;
use YapepBase\Exception\Exception;

use YapepBase\Session\SessionRegistry;

use YapepBase\Event\EventHandlerRegistry;
use YapepBase\Response\IResponse;
use YapepBase\Request\IRequest;
use YapepBase\ErrorHandler\ErrorHandlerRegistry;
use YapepBase\Lib\Pimple\Pimple;
use YapepBase\Log\Message\ErrorMessage;

/**
 * SystemContainer class
 *
 * @package    YapepBase
 * @subpackage DependencyInjection
 */
class SystemContainer extends Pimple {

    // Container keys
    /** Error log message key. */
    const KEY_ERROR_LOG_MESSAGE = 'errorLogMessage';
    /** Error handler registry key. */
    const KEY_ERROR_HANDLER_REGISTRY = 'errorHandlerRegistry';
    /** Event handler container key. */
    const KEY_EVENT_HANDLER_REGISTRY = 'eventHandlerRegistry';
    /** Session registry key. */
    const KEY_SESSION_REGISTRY = 'sessionRegistry';
    /** Memcache key. */
    const KEY_MEMCACHE = 'memcache';
    /** Memcache key. */
    const KEY_MEMCACHED = 'memcached';

    /**
     * List of namespaces to search for controllers in.
     * @var array
     */
    protected $controllerSearchNamespaces = array('\\YapepBase\\Controller');
    /**
     * List of namespaces to search for blocks in.
     * @var array
     */
    protected $blockSearchNamespaces = array('\\YapepBase\\View\\Block');

    /**
     * Constructor. Sets up the system DI objects.
     *
     * @return \YapepBase\Log\Message\ErrorMessage
     */
    public function __construct() {
        $this[self::KEY_ERROR_LOG_MESSAGE] = function($container) {
            return new ErrorMessage();
        };
        $this[self::KEY_ERROR_HANDLER_REGISTRY] = function($container) {
            return new ErrorHandlerRegistry();
        };
        $this[self::KEY_EVENT_HANDLER_REGISTRY] = $this->share(function($container) {
            return new EventHandlerRegistry();
        });
        $this[self::KEY_SESSION_REGISTRY] = $this->share(function($container) {
            return new SessionRegistry();
        });
        if (class_exists('\Memcache')) {
            $this[self::KEY_MEMCACHE] = function($container) {
                return new \Memcache();
            };
        }
        if (class_exists('\Memcached')) {
            $this[self::KEY_MEMCACHED] = function($container) {
                return new \Memcached();
            };
        }
    }

    /**
     * Returns a logging ErrorMessage instance
     *
     * @return \YapepBase\Log\Message\ErrorMessage
     */
    public function getErrorLogMessage() {
        return $this[self::KEY_ERROR_LOG_MESSAGE];
    }

    /**
     * Returns an error handler registry instance
     *
     * @return \YapepBase\ErrorHandler\ErrorHandlerRegistry
     */
    public function getErrorHandlerRegistry() {
        return $this[self::KEY_ERROR_HANDLER_REGISTRY];
    }

    /**
     * Returns an event handler registry instance
     *
     * @return \YapepBase\Event\EventHandlerRegistry
     */
    public function getEventHandlerRegistry() {
        return $this[self::KEY_EVENT_HANDLER_REGISTRY];
    }

    /**
     * Returns a session registry instance
     *
     * @return \YapepBase\Session\SessionRegistry
     */
    public function getSessionRegistry() {
        return $this[self::KEY_SESSION_REGISTRY];
    }

    /**
     * Returns a memcache instance
     *
     * @return \Memcache
     *
     * @throws \YapepBase\Exception\Exception   If there is no Memcache support in PHP.
     */
    public function getMemcache() {
        // @codeCoverageIgnoreStart
        if (!isset($this[self::KEY_MEMCACHE])) {
            throw new Exception('No memcache support in PHP');
        }
        // @codeCoverageIgnoreEnd
        return $this[self::KEY_MEMCACHE];
    }

    /**
     * Returns a memcache instance
     *
     * @return \Memcached
     *
     * @throws \YapepBase\Exception\Exception   If there is no Memcached support in PHP.
     */
    public function getMemcached() {
        // @codeCoverageIgnoreStart
        if (!isset($this[self::KEY_MEMCACHED])) {
            throw new Exception('No memcached support in PHP');
        }
        // @codeCoverageIgnoreEnd
        return $this[self::KEY_MEMCACHED];
    }

    /**
     * Set a list of namespace roots to search for controllers in.
     * @param array $namespaces a list of namespace roots to search for the controller in.
     */
    public function setControllerSearchNamespaces($namespaces = array()) {
        $this->controllerSearchNamespaces = $namespaces;
    }

    /**
     * Adds a namespace to the namespace roots to search for controllers in.
     * @param string $namespace a single namespace to add to the search list
     */
    public function addControllerSearchNamespace($namespace) {
        $this->controllerSearchNamespaces[] = $namespace;
    }

    /**
     * Searches for the controller in all the controller search namespaces
     * @param  string $controllerName
     * @return string controller name
     * @throws \YapepBase\Exception\ControllerException if the controller was not found
     */
    protected function searchForController($controllerName) {
        foreach ($this->controllerSearchNamespaces as $nsroot) {
            $className = $nsroot . '\\' . $controllerName . 'Controller';
            if (\class_exists($className, true)) {
                return $className;
            }
        }
        throw new \YapepBase\Exception\ControllerException('Controller ' . $controllerName . ' not found in '
            . \implode('; ', $this->controllerSearchNamespaces), \YapepBase\Exception\ControllerException::ERR_CONTROLLER_NOT_FOUND);
    }

    /**
     * Set a list of namespace roots to search for controllers in.
     * @param array $namespaces a list of namespace roots to search for the controller in.
     */
    public function setBlockSearchNamespaces($namespaces = array()) {
        $this->controllerSearchNamespaces = $namespaces;
    }

    /**
     * Adds a namespace to the namespace roots to search for blocks in.
     * @param string $namespace a single namespace to add to the search list
     */
    public function addBlockSearchNamespace($namespace) {
        $this->blockSearchNamespaces[] = $namespace;
    }

    /**
     * Searches for the controller in all the controller search namespaces
     * @param  string $controllerName
     * @return string controller name
     * @throws \YapepBase\Exception\ViewException if the controller was not found
     */
    protected function searchForBlock($blockName) {
        foreach ($this->blockSearchNamespaces as $nsroot) {
            $className = $nsroot . '\\' . $blockName . 'Block';
            if (\class_exists($className, true)) {
                return $className;
            }
        }
        throw new \YapepBase\Exception\ViewException('Block ' . $blockName . ' not found in '
            . \implode('; ', $this->blockSearchNamespaces), \YapepBase\Exception\ViewException::ERR_BLOCK_NOT_FOUND);
    }

    /**
     * Returns a controller by it's name.
     *
     * @param string    $controllerName   The name of the controller class to return.
     *                                    (Without the namespace and Controller suffix)
     * @param IRequest  $request          The request object for the controller.
     * @param IResponse $response         The response object for the controller.
     *
     * @return \YapepBase\Controller\IController
     */
    public function getController($controllerName, IRequest $request, IResponse $response) {
        $fullClassName = $this->searchForController($controllerName);
        return new $fullClassName($request, $response);
    }

    /**
     * Returns a block by it's name
     *
     * @param string $blockName   The name of the block class to return.
     *                            (Without the namespace and Block suffix)
     *
     * @return \YapepBase\View\Block
     */
    public function getBlock($blockName) {
        $fullClassName = $this->searchForBlock($blockName);
        return new $fullClassName();
    }
}