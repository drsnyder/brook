<?php

namespace Brook;
use Brook\Util;
use Brook\TaskInterface;


class WorkerTest extends \PHPUnit_Framework_TestCase {

  public function testRunAsParent() {
    $worker = \Phake::partialMock('\Brook\Worker');
    $task   = \Phake::mock('\Brook\TaskInterface');

    \Phake::when($worker)->fork()->thenReturn(1);
    $result = $worker->run($task);
    $this->assertEquals(1, $result);
    \Phake::verify($worker)->fork();
  }

  public function testRunAsWorker() {
    $receiver   = \Phake::mock('ZMQSocket');
    $sender     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $poller     = \Phake::mock('ZMQPoll');

    $worker = \Phake::partialMock('\Brook\Worker');
    $task   = \Phake::mock('\Brook\TaskInterface');
    // signal we are a child
    \Phake::when($worker)->fork()->thenReturn(0);
    \Phake::when($worker)->poll()
      ->thenReturn(\Brook\Worker::READ_READY)
      ->thenReturn(\Brook\Worker::SHUTDOWN);


    $message = "one message";
    \Phake::when($task)->setup()->thenReturn(null);
    \Phake::when($task)->tearDown()->thenReturn(null);
    \Phake::when($task)->work($message)->thenReturn($message);

    \Phake::when($receiver)->recv()->thenReturn($message);
    \Phake::when($sender)->send($message)->thenReturn(true); // verify

    $worker->setReceiver($receiver);
    $worker->setController($controller);
    $worker->setSender($sender);
    $worker->setPoller($poller);


    $this->assertEquals(1, $worker->run($task));
    \Phake::verify($worker)->fork();
    \Phake::verify($sender)->send($message);
    \Phake::verify($worker)->forward($message);
    \Phake::verify($task)->setup();
    \Phake::verify($task)->tearDown();
    \Phake::verify($task)->work($message);
  }

  public function testPoll() {
    $receiver   = \Phake::mock('ZMQSocket');
    $sender     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $poller     = \Phake::mock('ZMQPoll');

    $worker = \Phake::partialMock('\Brook\Worker');

    $readable = array($receiver);
    $writeable = array();
    \Phake::when($poller)
      ->poll(\Phake::setReference($readable), \Phake::setReference($writeable), \Brook\Worker::POLL_TIMEOUT)
      ->thenReturn(1);

    $worker->setReceiver($receiver);
    $worker->setController($controller);
    $worker->setSender($sender);
    $worker->setPoller($poller);

    $readable = array($receiver);
    $writeable = array();
    \Phake::when($poller)
      ->poll(\Phake::setReference($readable), \Phake::setReference($writeable), \Brook\Worker::POLL_TIMEOUT)
      ->thenReturn(1);

    $this->assertEquals(\Brook\Worker::READ_READY, $worker->poll());

    $readable = array($controller);
    $writeable = array();
    \Phake::when($poller)
      ->poll(\Phake::setReference($readable), \Phake::setReference($writeable), \Brook\Worker::POLL_TIMEOUT)
      ->thenReturn(1);
    $this->assertEquals(\Brook\Worker::SHUTDOWN, $worker->poll());

  }



}
