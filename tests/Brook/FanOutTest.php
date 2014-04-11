<?php

namespace Brook;
use Brook\Util;


class FanOutTest extends \PHPUnit_Framework_TestCase {

  public function testWork() {
    $worker  = \Phake::partialMock('\Brook\Worker');
    $fanOut = \Phake::partialMock('\Brook\FanOut');
    $task    = \Phake::mock('\Brook\TaskInterface');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $fanOut->setServer($server);
    $fanOut->setController($controller);
    $fanOut->setSink($sink);

    \Phake::when($fanOut)->sendOffWorker($task)->thenReturn($worker);
    \Phake::when($fanOut)->initialize()->thenReturn(true);

    $fanOut->distributeWork(3, $task);
    $this->assertEquals(3, count($fanOut->getWorkers()));
    \Phake::verify($fanOut, \Phake::times(3))->sendOffWorker($task);
    \Phake::verify($fanOut)->initialize();
  }

  public function testShutdown() {
    $fanOut = \Phake::partialMock('\Brook\FanOut');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $fanOut->setServer($server);
    $fanOut->setController($controller);
    $fanOut->setSink($sink);

    \Phake::when($controller)->send("SHUTDOWN")->thenReturn(true);

    $fanOut->shutdown();
    \Phake::verify($controller)->send("SHUTDOWN");
  }


  public function testSendOffWorker() {
    // make sure we exit the child
    $worker  = \Phake::partialMock('\Brook\Worker');
    $fanOut = \Phake::partialMock('\Brook\FanOut');
    $task    = \Phake::mock('\Brook\TaskInterface');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $fanOut->setServer($server);
    $fanOut->setController($controller);
    $fanOut->setSink($sink);

    \Phake::when($worker)->run($task)->thenReturn(true);
    \Phake::when($worker)->getPid()->thenReturn(0);

    \Phake::when($fanOut)->createWorker(7778, 7779, 7780)->thenReturn($worker);
    \Phake::when($fanOut)->exitChild()->thenReturn(true);
    \Phake::when($fanOut)->initialize()->thenReturn(true);

    $this->assertEquals($worker, $fanOut->sendOffWorker($task));
    \Phake::verify($fanOut)->createWorker(7778, 7779, 7780);
    \Phake::verify($worker)->run();
    \Phake::verify($worker)->getPid();
    \Phake::verify($fanOut)->exitChild();
  }
}
