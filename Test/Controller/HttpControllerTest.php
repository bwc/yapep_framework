<?php

namespace YapepBase\Controller;

/**
 * Test class for HttpController.
 * Generated by PHPUnit on 2011-12-15 at 09:27:34.
 */
class HttpControllerTest extends \PHPUnit_Framework_TestCase {
    function testConstructor() {
        try {
            $request = new \YapepBase\Test\Mock\Request\RequestMock('');
            $response = new \YapepBase\Response\HttpResponse();
            $o = new \YapepBase\Test\Mock\Controller\HttpMockController($request, $response);
            $this->fail('Passing a non-HTTP request to the HttpController should result in a ControllerException');
        } catch (\YapepBase\Exception\ControllerException $e) {
            $this->assertEquals(\YapepBase\Exception\ControllerException::ERR_INCOMPATIBLE_REQUEST, $e->getCode());
        }
        try {
            $_SERVER['REQUEST_URI'] = '/';
            $request = new \YapepBase\Request\HttpRequest();
            $response = new \YapepBase\Test\Mock\Response\ResponseMock();
            $o = new \YapepBase\Test\Mock\Controller\HttpMockController($request, $response);
            $this->fail('Passing a non-HTTP request to the HttpController should result in a ControllerException');
        } catch (\YapepBase\Exception\ControllerException $e) {
            $this->assertEquals(\YapepBase\Exception\ControllerException::ERR_INCOMPATIBLE_RESPONSE, $e->getCode());
        }
        $_SERVER['REQUEST_URI'] = '/';
        $request = new \YapepBase\Request\HttpRequest();
        $response = new \YapepBase\Response\HttpResponse(new \YapepBase\Test\Mock\Response\OutputMock());
        $o = new \YapepBase\Test\Mock\Controller\HttpMockController($request, $response);
    }

    function testRedirect() {
        $_SERVER['REQUEST_URI'] = '/';
        $request = new \YapepBase\Request\HttpRequest();
        $out = new \YapepBase\Test\Mock\Response\OutputMock();
        $response = new \YapepBase\Response\HttpResponse($out);
        $o = new \YapepBase\Test\Mock\Controller\HttpMockController($request, $response);
        try {
            $o->testRedirect();
            $this->fail('Redirect test should result in a RedirectException');
        } catch (\YapepBase\Exception\RedirectException $e) {
            $this->assertEquals(\YapepBase\Exception\RedirectException::TYPE_EXTERNAL, $e->getCode());
        }
        $response->send();
        $this->assertEquals(301, $out->responseCode);
        $this->assertEquals(array('http://www.example.com/'), $out->headers['Location']);
    }

    function testRedirectToRoute() {
        $_SERVER['REQUEST_URI'] = '/';
        $router = new \YapepBase\Test\Mock\Router\RouterMock();
        \YapepBase\Application::getInstance()->setRouter($router);
        $request = new \YapepBase\Request\HttpRequest();
        $out = new \YapepBase\Test\Mock\Response\OutputMock();
        $response = new \YapepBase\Response\HttpResponse($out);
        $o = new \YapepBase\Test\Mock\Controller\HttpMockController($request, $response);
        try {
            $o->testRedirectToRoute();
            $this->fail('RedirectToRoute test should result in a RedirectException');
        } catch (\YapepBase\Exception\RedirectException $e) {
            $this->assertEquals(\YapepBase\Exception\RedirectException::TYPE_EXTERNAL, $e->getCode());
        }
        $response->send();
        $this->assertEquals(303, $out->responseCode);
        $this->assertEquals(array('/?test=test&test2%5B0%5D=test1&test2%5B1%5D=test2#test'), $out->headers['Location']);
    }
}