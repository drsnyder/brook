<?php


require __DIR__.'/../vendor/autoload.php';

class MultiplicationTask implements \Brook\TaskInterface {
  const MULTIPLIER = 2;

  public function setup() { }
  public function tearDown() { }

  public function work($value) {
    return sprintf("%d %d", $value, $value * self::MULTIPLIER);
  }
}


$messageCount = 20;

$fanOut = new Brook\FanOut();
$fanOut->distributeWork(2, new MultiplicationTask());

for ($i=1; $i<=$messageCount; $i++) {
  $fanOut->enqueue($i);
  echo "sent $i", PHP_EOL;
}

for ($i=1; $i<=$messageCount; $i++) {
  $result = $fanOut->readFromSink();
  list($value, $multiplied) = explode(' ', $result);

  echo "got $value, $multiplied", PHP_EOL;
  assert($multiplied == (MultiplicationTask::MULTIPLIER * $value));
}

$fanOut->shutdown();
