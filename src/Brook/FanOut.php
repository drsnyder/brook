<?php

namespace Brook;
use Brook\Util;
use Brook\TaskInterface;

class FanOut {

  protected $workers;

  protected $serverPort;
  protected $controllerPort;
  protected $sinkPort;

  protected $context;
  protected $controller;
  protected $server;
  protected $sink;

  public function setController(\ZMQSocket $controller) {
    $this->controller = $controller;
  }

  public function setServer(\ZMQSocket $server) {
    $this->server = $server;
  }

  public function setSink(\ZMQSocket $sink) {
    $this->sink = $sink;
  }

  function __construct($serverPort=7778, $controllerPort=7779, $sinkPort=7780) {
    if (!class_exists('ZMQContext')) {
      throw new \RuntimeException("ZMQ Extension is required!");
    }

    $this->serverPort     = $serverPort;
    $this->controllerPort = $controllerPort;
    $this->sinkPort       = $sinkPort;

    $this->context = new \ZMQContext();

    $this->workers = array();
  }

  public function distributeWork($concurrency, TaskInterface $task) {
    for ($i=0; $i<$concurrency; $i++) {
      $this->workers[] = $this->sendOffWorker($task);
    }

    $this->initialize();
    sleep(1);
  }

  public function sendOffWorker($task) {
    $worker = $this->createWorker($this->serverPort, $this->controllerPort, $this->sinkPort);
    $worker->run($task);

    if ($worker->getPid() === 0) {
      // child
      $this->exitChild();
    }

    return $worker;
  }

  public function getWorkers() {
    return $this->workers;
  }

  public function readFromSink() {
    if ($this->sink) {
      return $this->sink->recv();
    }

    return false;
  }

  public function enqueue($message) {
    $this->server->send($message);
  }

  public function shutdown() {
    $this->sendShutdown();
  }

  protected function sendShutdown() {
    $ret = $this->controller->send("SHUTDOWN");
    foreach ($this->getWorkers() as $worker) {
      pcntl_waitpid($worker->getPid(), $status);
    }
  }

  protected function initialize() {
    $this->setupServer();
    $this->setupController();
    $this->setupSink();
  }

  protected function setupServer() {
    if (!$this->server) {
      $this->server = new \ZMQSocket($this->context, \ZMQ::SOCKET_PUSH);
      $this->server->bind(Util::genURI($this->serverPort, "*"));
    }
  }

  protected function setupController() {
    if (!$this->controller) {
      $this->controller = new \ZMQSocket($this->context, \ZMQ::SOCKET_PUB);
      $this->controller->bind(Util::genURI($this->controllerPort, "*"));
    }
  }

  protected function setupSink() {
    if (!$this->sink && $this->sinkPort) {
      $this->sink = new \ZMQSocket($this->context, \ZMQ::SOCKET_PULL);
      $this->sink->bind(Util::genURI($this->sinkPort, "*"));
    }
  }

  protected function exitChild($code=0) {
    exit($code);
  }

  protected function createWorker($serverPort=7778, $controllerPort=7779, $senderPort=7780) {
    return new Worker($serverPort, $controllerPort, $senderPort);
  }

}
