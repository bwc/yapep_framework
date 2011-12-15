<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package      YapepBase
 * @subpackage   Session
 * @author       Zsolt Szeberenyi <szeber@yapep.org>
 * @copyright    2011 The YAPEP Project All rights reserved.
 * @license      http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace YapepBase\Session;
use YapepBase\Util\Random;
use YapepBase\Request\IRequest;
use YapepBase\Response\IResponse;
use YapepBase\Application;
use YapepBase\Event\Event;
use YapepBase\Exception\ConfigException;
use YapepBase\Config;

/**
 * SessionAbstract class
 *
 * @package    YapepBase
 * @subpackage Session
 */
abstract class SessionAbstract implements ISession {

    /** The default session lifetime, if one is not provided in the config. */
    const DEFAULT_LIFETIME = 1800;

    /**
     * The storage instance.
     *
     * @var \YapepBase\Storage\IStorage
     */
    protected $storage;

    /**
     * The request instance.
     *
     * @var \YapepBase\Request\IRequest
     */
    protected $request;

    /**
     * The response instance.
     *
     * @var \YapepBase\Response\IResponse
     */
    protected $response;

    /**
     * The session lifetime in seconds.
     *
     * @var int
     */
    protected $lifetime = self::DEFAULT_LIFETIME;

    /**
     * The namespace used by the session.
     *
     * @var string
     */
    protected $namespace;

    /**
     * TRUE if the session data has already been loaded.
     *
     * @var bool
     */
    protected $isLoaded = false;

    /**
     * The session ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The session data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     *
     * @param string                        $configName
     * @param \YapepBase\Storage\IStorage   $storage
     * @param \YapepBase\Request\IRequest   $request
     * @param \YapepBase\Response\IResponse $response
     * @param bool                          $autoregister
     *
     * @throws \YapepBase\Exception\ConfigException
     */
    public function __construct(
        $configName, IStorage $storage, IRequest $request, IResponse $response, $autoregister = true
    ) {
        $config = Config::getInstance()->get($configName);
        if (empty($config)) {
            throw new ConfigException('Configuration not found for session handler');
        }
        if (!is_array($config)) {
            throw new ConfigException('Invalid configuration for session handler');
        }
        if (!isset($config['namespace'])) {
            throw new ConfigException('No namespace has been set for the session handler');
        }
        $this->namespace = $config['namespace'];
        $this->lifetime = (isset($config['lifetime']) ? (int)$config['lifetime'] : self::DEFAULT_LIFETIME);

        $this->validateConfig();

        if ($autoregister) {
            $this->registerEventHandler();
        }

        $this->id = $this->getSessionIdFromRequest();
    }

    /**
     * Validates the configuration.
     *
     * @param array $config
     *
     * @throws \YapepBase\Exception\ConfigException   On configuration problems
     * @throws \YapepBase\Exception\Exception         On other problems
     */
    abstract protected function validateConfig(array $config);

    /**
     * Returns the session ID from the request object. If the request has no session, it returns NULL.
     *
     * @return string
     */
    abstract protected function getSessionIdFromRequest();

    /**
     * This method is called when the session has been initialized (loaded or created).
     */
    protected function sessionInitialized() {
        // Empty default implementation. Should be implemented by descendant classes if needed
    }

    /**
     * Registers the instance as an event handler
     */
    public function registerEventHandler() {
        $registry = Application::getInstance()->getDiContainer()->getEventHandlerRegistry();
        $registry->registerEventHandler(Event::TYPE_APPSTART, $this);
        $registry->registerEventHandler(Event::TYPE_APPFINISH, $this);
    }

    /**
     * Removes event handler registration
     */
    public function removeEventHandler() {
        $registry = Application::getInstance()->getDiContainer()->getEventHandlerRegistry();
        $registry->removeEventHandler(Event::TYPE_APPSTART, $this);
        $registry->removeEventHandler(Event::TYPE_APPFINISH, $this);
    }

    /**
     * Returns the key used to store the session data
     *
     * @return string
     */
    protected function getStorageKey() {
        return 'session.' . $this->namespace . '.' . $this->id;
    }

    /**
     * Loads the session.
     *
     * If there is no session ID set, it creates a new session instead.
     */
    protected function loadSession() {
        if ($this->isLoaded) {
            return;
        }
        if (empty($this->id)) {
            $this->create();
            return;
        }
        $result = $this->storage->get($this->getStorageKey());
        if (false === $result) {
            $this->create();
            return;
        }
        $this->data = $result;
        $this->isLoaded = true;
    }

    /**
     * Saves the session. If it has not been loaded yet, it loads it first.
     */
    protected function saveSession() {
        if (!$this->isLoaded) {
            $this->loadSession();
        }
        $this->storage->set($this->getStorageKey(), $this->data, $this->lifetime);
    }

    /**
     * Creates a new session.
     */
    public function create() {
        if (!empty($this->id)) {
            $this->destroy();
        }
        $this->id = $this->generateId();
        $this->data = array();
        $this->isLoaded = true;

    }

    /**
     * Generates a session ID.
     */
    protected function generateId() {
        return Random::pseudoString(32);
    }

    /**
     * Destroys the session.
     */
    public function destroy() {
        $this->storage->delete($this->getStorageKey());
        $this->data = array();
        $this->isLoaded = false;
        $this->id = null;
    }

    /**
     * Handles the application start and finish events to load and save the session.
     *
     * @param \YapepBase\Event\Event $event
     *
     * @see YapepBase\Event.IEventHandler::handleEvent()
     */
    public function handleEvent(Event $event) {
        switch ($event) {
            case Event::TYPE_APPSTART:
                $this->loadSession();
                break;

            case Event::TYPE_APPFINISH:
                $this->saveSession();
                break;
        }
    }

    /**
     * Returns the session's namespace
     *
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

	/**
	 * Checks whether a key is set in the session
	 *
	 * @param string $offset
	 *
	 * @return bool
	 *
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists ($offset) {
        return isset($this->data[$offset]);

    }

	/**
	 * Returns a key from the session
	 *
	 * @param string offset
	 *
	 * @return mixed
	 *
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet ($offset) {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        return null;
    }

	/**
	 * Sets a key in the session
	 *
	 * @param string $offset
	 * @param mixed  $value
	 *
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet ($offset, $value) {
        $this->data[$offset] = $value;

    }

	/**
	 * Removes a key from the session
	 *
	 * @param string $offset
	 *
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset ($offset) {
        if (isset($this->data[$offset])) {
            unset($this->data[$offset]);
        }
    }

}