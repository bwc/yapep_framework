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
use YapepBase\Request\HttpRequest;
use YapepBase\Exception\Exception;
use YapepBase\Application;
use YapepBase\Event\Event;
use YapepBase\Event\IEventHandler;
use YapepBase\Response\HttpResponse;
use YapepBase\Storage\IStorage;
use YapepBase\Exception\ConfigException;
use YapepBase\Config;

/**
 * HttpSession class
 *
 * @package    YapepBase
 * @subpackage Session
 */
class HttpSession extends SessionAbstract {

    /**
     * The request instance.
     *
     * @var \YapepBase\Request\HttpRequest
     */
    protected $request;

    /**
     * The response instance.
     *
     * @var \YapepBase\Response\HttpResponse
     */
    protected $response;

    /**
     * Stores the name of the cookie.
     *
     * @var string
     */
    protected $cookieName;

    /**
     * Stores the domain of the cookie.
     *
     * @var string
     */
    protected $cookieDomain;

    /**
     * Stores the path of the cookie
     *
     * @var string
     */
    protected $cookiePath;

    /**
     * If TRUE, the cache limiters will be sent to the client in the response.
     *
     * @var bool
     */
    protected $cacheLimitersEnabled;

    /**
     * Validates the configuration.
     *
     * @param array $config
     *
     * @throws \YapepBase\Exception\ConfigException   On configuration problems
     * @throws \YapepBase\Exception\Exception         On other problems
     */
    protected function validateConfig(array $config) {
        if (!($this->request instanceof HttpRequest)) {
            throw new Exception('The request object is not an HttpRequest instance');
        }

        if (!($this->response instanceof HttpResponse)) {
            throw new Exception('The response object is not an HttpResponse instance');
        }

        if (empty($config['cookieName'])) {
            throw new ConfigException('No cookie name set for the session handler');
        }

        $this->cookieName = $config['cookieName'];
        $this->cookieDomain = (empty($config['cookieDomain']) ? '/' : $config['cookieDomain']);
        $this->cookiePath = (empty($config['cookiePath']) ? '/' : $config['cookiePath']);
        $this->cacheLimitersEnabled = (empty($config['cacheLimitersEnabled']) ? true : $config['cacheLimitersEnabled']);
    }

    /**
     * Returns the session ID from the request object. If the request has no session, it returns NULL.
     *
     * @return string
     */
    protected function getSessionIdFromRequest() {
        return $this->request->getCookie($this->cookieName, null);
    }

    /**
     * This method is called when the session has been initialized (loaded or created).
     *
     * @see YapepBase\Session.SessionAbstract::sessionInitialized()
     */
    protected function sessionInitialized() {
        parent::sessionInitialized();
        if ($this->cacheLimitersEnabled) {
            $this->response->addHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');
            $this->response->addHeader('Cache-Control',
            	'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            $this->response->addHeader('Pragma', 'no-cache');
        }
    }

    /**
     * Creates a new session.
     *
     * @see YapepBase\Session.SessionAbstract::create()
     */
    public function create() {
        parent::create();

        $this->response->setCookie($this->cookieName, $this->id, 0, $this->cookiePath, $this->cookieDomain);
    }

    /**
     * Destroys the session.
     *
     * @see YapepBase\Session.SessionAbstract::destroy()
     */
    public function destroy() {
        parent::destroy();

        $this->response->setCookie($this->cookieName, '', 1, $this->cookiePath, $this->cookieDomain);
    }
}