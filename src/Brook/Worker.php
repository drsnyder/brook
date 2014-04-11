<?php

namespace Brook;
use Brook\Util;
use Brook\TaskInterface;

class Worker {

  const READ_READY = 1;
  const SHUTDOWN   = -1;

  const POLL_TIMEOUT = 100;


  protected $serverPort;
  protected $controllerPort;
  protected $senderPort;

  protected $context;
  protected $controller = null;
  protected $receiver   = null;
  protected $sender     = null;

  protected $poller = null;

  protected $pid;

  protected $active = false;

  public function setController(\ZMQSocket $controller) {
    $this->controller = $controller;
  }

  public function setReceiver(\ZMQSocket $receiver) {
    $this->receiver = $receiver;
  }

  public function setSender(\ZMQSocket $sender) {
    $this->sender = $sender;
  }

  public function setPoller(\ZMQPoll $poller) {
    $this->poller = $poller;
  }

  function __construct($serverPort=7778, $controllerPort=7779, $senderPort=7780) {
    if (!class_exists('ZMQContext')) {
      throw new \RuntimeException("ZMQ Extension is required!");
    }

    $this->serverPort     = $serverPort;
    $this->controllerPort = $controllerPort;
    $this->senderPort     = $senderPort;

    $this->context = new \ZMQContext();
  }

  public function run(TaskInterface $task) {
    $handled = 0;
    $pid = $this->fork();

    if ($pid) {
      // return back to parent
      return $pid;
    }

    // initialize the sockets after we fork
    $this->initialize();
    $task->setup();

    while (true) {
      $ret = $this->poll();
      if ($ret === self::READ_READY) {
        $message = $this->receiver->recv();
        $result = $task->work($message);
        $this->forward($result);
        $handled++;
      } elseif ($ret === self::SHUTDOWN) {
        break;
      }
    }

    $task->tearDown();
    return $handled;
  }

  public function poll() {
    $readable = $writeable = array();
    $events = $this->poller->poll($readable, $writeable, self::POLL_TIMEOUT);
    if ($events > 0) {
      foreach ($readable as $socket) {
        if ($socket === $this->receiver) {
          return self::READ_READY;
        } else if ($socket === $this->controller) {
          return self::SHUTDOWN;
        } else {
          fprintf(STDERR, "Unknown socket!\n");
        }
      }
    }

    return false;
  }

  public function forward($message) {
    if ($this->sender) {
      $this->sender->send($message);
    }
  }

  protected function fork() {
    $this->pid = pcntl_fork();

    if ($this->pid === -1) {
      throw new \RuntimeException("Failed to fork!");
    } else if ($this->pid) {
      // child
    } else {
      // parent
    }

    $this->active = true;
    return $this->pid;
  }

  public function getPid() {
    return $this->pid;
  }

  public function isActive() {
    return $this->active;
  }

  protected function initialize() {
    $this->setupReceiver();
    $this->setupSender();
    $this->setupController();
    $this->setupPoller();
  }

  protected function setupReceiver() {
    if (!$this->receiver) {
      $this->receiver = new \ZMQSocket($this->context, \ZMQ::SOCKET_PULL);
      $this->receiver->connect(Util::genURI($this->serverPort));
    }
  }

  protected function setupSender() {
    if (!$this->sender && $this->senderPort) {
      $this->sender = new \ZMQSocket($this->context, \ZMQ::SOCKET_PUSH);
      $this->sender->connect(Util::genURI($this->senderPort));
    }
  }

  protected function setupController() {
    if (!$this->controller) {
      $this->controller = new \ZMQSocket($this->context, \ZMQ::SOCKET_SUB);
      $this->controller->connect(Util::genURI($this->controllerPort));
      $this->controller->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
    }
  }

  protected function setupPoller() {
    if (!$this->poller) {
      $this->poller = new \ZMQPoll();
      $this->poller->add($this->receiver, \ZMQ::POLL_IN);
      $this->poller->add($this->controller, \ZMQ::POLL_IN);
    }
  }

}
