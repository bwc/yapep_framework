<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package      YapepBase
 * @subpackage   View
 * @author       Zsolt Szeberenyi <szeber@yapep.org>
 * @copyright    2011 The YAPEP Project All rights reserved.
 * @license      http://www.opensource.org/licenses/bsd-license.php BSD License
 */


namespace YapepBase\View;
use YapepBase\Application;
use YapepBase\Exception\ViewException;
use YapepBase\Mime\MimeType;
use YapepBase\Config;

/**
 * ViewAbstract class
 *
 * @package    YapepBase
 * @subpackage View
 */
abstract class ViewAbstract implements IView {

    /**
     * Stores the charset
     *
     * @var string
     */
    protected $charset;

    /**
     * Stores the content type
     *
     * @var string
     */
    protected $contentType;

    /**
     * Constructor.
     *
     * @param string $charset   The charset for the view.
     *
     * @return string
     */
    public function __construct($charset = null) {
        $this->charset = $charset;
        if (empty($this->charset)) {
            $this->charset = Config::getInstance()->get('system.defaultCharset');
        }
        $this->init();
    }

    /**
     * Called at class construction
     */
    protected function init() {
        // Implemented in descendant classes if neccessary
    }

    /**
     * Renders the view and returns it.
     *
     * @param string $contentType   The content type of the response.
     *                              {@uses \YapepBase\Mime\MimeType::*}
     * @param bool   $return        If TRUE, the method will return the output, otherwise it will print it.
     *
     * @return string   The rendered view or NULL if not returned
     */
    public function render($contentType, $return = true) {
        if ($return) {
            ob_start();
        }
        $this->contentType = $contentType;
        $this->renderContent();
        if ($return) {
            return ob_get_clean();
        } else {
            return null;
        }
    }

    /**
     * Renders and outputs a block.
     *
     * @param string $blockName   The name of the block.
     * @param array  $params      The parameters for the block.
     * @param array  $rawParams   The raw parameters for the block.
     *
     * @return string   The rendered output of the block.
     */
    protected function renderBlock($blockName, array $params = array(), array $rawParams = array()) {
        $block = Application::getInstance()->getDiContainer()->getBlock($blockName);
        if (count($rawParams) != count($params)) {
            throw new ViewException('The number of params does not match the number of raw params');
        }
        foreach($params as $name => $value) {
            if (!array_key_exists($name, $rawParams)) {
                throw new ViewException('The keys in the params do not match the keys in the raw params');
            }
            $block->set($name, $value, $rawParams[$name]);
        }

        // Render the block, and output it's contents
        $block->render($this->contentType, false);
    }

    /**
     * Recursively escapes the data.
     *
     * @param mixed $data         The data to escape.
     * @param bool  $escapeKeys   If TRUE then the keys will be escaped too.
     *
     * @return mixed   The escaped data.
     */
    protected function escape($data, $escapeKeys = false) {
        if (is_array($data) || (($data instanceof \Iterator) && ($data instanceof \ArrayAccess))) {
            foreach($data as $key => $value) {
                if ($escapeKeys) {
                    unset($data[$key]);
                    $data[$this->escapeSimpleValue($key)] = $this->escape($value);
                } else {
                    $data[$key] = $this->escape($value);
                }
            }
            return $data;
        } else {
            return $this->escapeSimpleValue($data);
        }
    }

    /**
     * Escapes a simple value
     *
     * @param mixed  $value
     * @param string $contentType   The content type to use for escaping.
     *
     * @return mixed
     */
    protected function escapeSimpleValue($value, $contentType = null) {
        if (empty($contentType)) {
            $contentType = $this->contentType;
        }
        if (is_null($value)) {
            return null;
        }
        switch ($contentType) {
            case MimeType::JAVASCRIPT:
                return \json_encode($value);
                break;

            case MimeType::JSON:
                // We don't escape JSON content, json_encode will take care of it.
            case MimeType::CSS:
                // We don't escape CSS content.
                return $value;
                break;

            case MimeType::HTML:
            case MimeType::XML:
            default:
                return htmlspecialchars($value, ENT_COMPAT, $this->charset);
                break;
        }
    }

    /**
     * Helper method to access the target of the route specified by controller and action.
     *
     * @see \YapepBase\Router\IRouter::getTargetForControllerAction()
     *
     * @param string $controller   The name of the controller.
     * @param string $action       The name of the action in the controller.
     * @param array $params        The parameters for the route.
     *
     * @return string   The route target.
     */
    protected function getRouteTarget($controller, $action, array $params = array()) {
        return Application::getInstance()->getRouter()->getTargetForControllerAction($controller, $action, $params);
    }

    /**
     * Does the actual rendering of the view
     */
    abstract protected function renderContent();
}