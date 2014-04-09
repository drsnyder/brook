<?php

namespace Brook;
use Brook\Util;


class ChannelTest extends \PHPUnit_Framework_TestCase {

  public function testWork() {
    $worker  = \Phake::partialMock('\Brook\Worker');
    $channel = \Phake::partialMock('\Brook\Channel');

    $server     = \Phake::mock('ZMQSocket');
    $controller = \Phake::mock('ZMQSocket');
    $sink       = \Phake::mock('ZMQSocket');

    $channel->setServer($server);
    $channel->setController($controller);
    $channel->setSink($sink);

    \Phake::when($channel)->sendOffWorker('Util::passThroughCallback')->thenReturn($worker);
    \Phake::when($channel)->initialize()->thenReturn(true);

    $channel->work(3, 'Util::passThroughCallback');
    $this->assertEquals(3, count($channel->getWorkers()));
    \Phake::verify($channel, \Phake::times(3))->sendOffWorker('Util::passThroughCallback');
    \Phake::verify($channel)->initialize();
  }

  public function testShutdown() {
    $channel = \Phake::partialMock('\Brook\Channel');

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
}
