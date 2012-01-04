<?php

namespace YapepBase\Test;

use YapepBase\DependencyInjection\SystemContainer;

require_once dirname(__FILE__) . '/../Application.php';

/**
 * Test class for Application.
 * Generated by PHPUnit on 2011-12-14 at 16:13:14.
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \YapepBase\Test\Mock\ApplicationMock
     */
    protected $object;
    protected $oldApp;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->oldApp = \YapepBase\Application::getInstance();
        $this->object = new Mock\ApplicationMock;
        \YapepBase\Application::setInstance($this->object);
    }

    protected function tearDown() {
        \YapepBase\Application::setInstance($this->oldApp);
    }

    public function testSetRouter() {
        $r = new \YapepBase\Test\Mock\Router\RouterMock();
        $this->object->setRouter($r);
        $this->assertEquals($r, $this->object->getRouter());
    }

    public function testSetDiContainer() {
        $c = new \YapepBase\DependencyInjection\SystemContainer();
        $this->object->setDiContainer($c);
        $this->assertEquals($c, $this->object->getDiContainer());
    }

    public function testSetRequest() {
        $r = new Mock\Request\RequestMock('');
        $this->object->setRequest($r);
        $this->assertEquals($r, $this->object->getRequest());
    }

    public function testSetResponse() {
        $r = new Mock\Response\ResponseMock();
        $this->object->setResponse($r);
        $this->assertEquals($r, $this->object->getResponse());
    }

    public function testGetErrorHandlerRegistry() {
        $this->assertInstanceOf('\YapepBase\ErrorHandler\ErrorHandlerRegistry', $this->object->getErrorHandlerRegistry());
    }

    public function testRun() {
        $out = new Mock\Response\OutputMock();
        $request = new Mock\Request\RequestMock('/');
        $response = new Mock\Response\ResponseMock($out);
        $router = new Mock\Router\ApplicationRouterMock();
        $this->object->getDiContainer()->setSearchNamespaces(SystemContainer::NAMESPACE_SEARCH_CONTROLLER,
            array('\YapepBase\Test\Mock\Controller'));
        $this->object->setRequest($request);
        $this->object->setResponse($response);
        $this->object->setRouter($router);
        $this->object->run();
        $response->send();
    }
}
