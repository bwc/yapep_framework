<?php

namespace YapepBase\Autoloader;

class AutoloaderRegistry {
    /**
     *
     * @var \SplObjectStorage
     */
    protected $registry;
    /**
     * Automatically register/unregister with SPL
     * @var bool default true
     */
    protected $autoregister = true;
    /**
     *
     * @var \YapepBase\Autoloader\AutoloaderRegistry
     */
    protected static $instance;

    /**
     * Singleton instance getter.
     * @return \YapepBase\Autoloader\AutoloaderRegistry
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize object storage.
     */
    public function __construct() {
        $this->registry = new \SplObjectStorage();
    }

    /**
     * Sets or clears the flag to automatically register with SPL
     * @param type $autoregister
     */
    public function setAutoregister($autoregister) {
        $this->autoregister = (bool)$autoregister;
    }

    /**
     * Registers this registry with SPL.
     */
    public function registerWithSpl() {
        \spl_autoload_register(array($this, 'load'));
    }

    /**
     * Unregisters this registry with SPL
     */
    public function unregisterFromSpl() {
        \spl_autoload_unregister(array($this, 'load'));
    }

    /**
     * Runs through all Autoloaders and tries to load a class.
     * @param  string  $className
     * @return bool
     */
    public function load($className) {
        foreach ($this->registry as $autoloader) {
            if ($autoloader->load($className)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Register an autoloader with the registry
     * @param  \YapepBase\Autoloader\AutoloaderBase $object
     * @param  bool                                 $autoregister  default null  Automatically register with SPL
     */
    public function register(\YapepBase\Autoloader\AutoloaderBase $object, $autoregister = null) {
        if (($autoregister || $this->autoregister) && !$this->registry->count()) {
            $this->registerWithSpl();
        }
        $this->registry->attach($object);
    }
    /**
     * Unregister from registry.
     * @param \YapepBase\Autoloader\AutoloaderBase $autoloader
     * @param bool                                 $autounregister  default null  automatically unregister from SPL if
     *                                                                            no more autoloaders are left.
     */
    public function unregister($autoloader, $autounregister = null) {
        $this->registry->detach($autoloader);
        if (($autounregister || $this->autoregister) && !$this->registry->count()) {
            $this->unregisterFromSpl();
        }
    }
    /**
     * Remove all autoloaders by class name.
     * @param string $autoloaderClass
     * @param bool   $autounregister  default null  Automatically unregister from SPL if no more autoloaders are left.
     */
    public function unregisterByClass($autoloaderClass, $autounregister = null) {
        foreach ($this->registry as $autoloader) {
            if (\get_class($autoloader) == $autoloaderClass) {
                $this->detach($autoloader, $autounregister);
            }
        }
    }
}