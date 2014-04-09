<?php

namespace Brook;
use Brook\Util;

class Channel {

  protected $workers;

  protected $serverPort;
  protected $controllerPort;
  protected $sinkPort;

  protected $context;
  protected $controller;
  protected $sender;
  protected $sink;

  public function setController($controller) {
    $this->controller = $controller;
  }

  function __construct($serverPort=7778, $controllerPort=7779, $sinkPort=7780) {
    if (!class_exists('ZMQContext')) {
      throw new \RuntimeException("ZMQ Extension is required!");
    }

    $this->serverPort     = $serverPort;
    $this->controllerPort = $controllerPort;
    $this->senderPort     = $senderPort;

    $this->context = new \ZMQContext();

    $this->workers = array();
  }

  public function work($concurrency, $fn) {

    for ($i=0; $i<$concurrency; $i++) {
      $worker = new Worker($this->serverPort, $this->controllerPort, $this->senderPort);
      $worker->run($fn);
      $this->workers[] = $worker;
    }

    $this->setupSender($this->serverPort);
  }

  public function readFromSink() {
    if ($this->sink) {
      return $this->sink->recv();
    }

    return false;
  }

  public function enqueue($message) {
    $this->sender->send($message);
  }

  public function shutdown() {
    $this->sendShutdown();
  }

  protected function sendShutdown() {
    $this->controller->send("SHUTDOWN");
  }

  protected function setupSender() {
    if (!$this->sender) {
      $this->sender = new ZMQSocket($this->context, ZMQ::SOCKET_PUSH);
      $this->sender->bind(Util::genURI($this->serverPort, "*"));
  }

}
