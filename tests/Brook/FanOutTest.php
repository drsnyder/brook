<?php

namespace Brook;
use Brook\Util;


class FanOutTest extends \PHPUnit_Framework_TestCase {

  public function testWork() {
    $worker  = \Phake::partialMock('\Brook\Worker');
    $channel = \Phake::partialMock('\Brook\FanOut');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $channel->setServer($server);
    $channel->setController($controller);
    $channel->setSink($sink);

    \Phake::when($channel)->sendOffWorker('Util::passThroughCallback')->thenReturn($worker);
    \Phake::when($channel)->initialize()->thenReturn(true);

    $channel->distributeWork(3, 'Util::passThroughCallback');
    $this->assertEquals(3, count($channel->getWorkers()));
    \Phake::verify($channel, \Phake::times(3))->sendOffWorker('Util::passThroughCallback');
    \Phake::verify($channel)->initialize();
  }

  public function testShutdown() {
    $channel = \Phake::partialMock('\Brook\FanOut');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $channel->setServer($server);
    $channel->setController($controller);
    $channel->setSink($sink);

    \Phake::when($controller)->send("SHUTDOWN")->thenReturn(true);

    $channel->shutdown();
    \Phake::verify($controller)->send("SHUTDOWN");
  }


  public function testSendOffWorker() {
    // make sure we exit the child
    $worker  = \Phake::partialMock('\Brook\Worker');
    $channel = \Phake::partialMock('\Brook\FanOut');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $channel->setServer($server);
    $channel->setController($controller);
    $channel->setSink($sink);

    \Phake::when($worker)->run('Util::passThroughCallback')->thenReturn(true);
    \Phake::when($worker)->getPid()->thenReturn(0);

    \Phake::when($channel)->createWorker(7778, 7779, 7780)->thenReturn($worker);
    \Phake::when($channel)->exitChild()->thenReturn(true);
    \Phake::when($channel)->initialize()->thenReturn(true);

    $this->assertEquals($worker, $channel->sendOffWorker('Util::passThroughCallback'));
    \Phake::verify($channel)->createWorker(7778, 7779, 7780);
    \Phake::verify($worker)->run('Util::passThroughCallback');
    \Phake::verify($worker)->getPid();
    \Phake::verify($channel)->exitChild();

  }
}
