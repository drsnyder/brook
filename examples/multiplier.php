<?php

require __DIR__.'/../vendor/autoload.php';

$messageCount = 20;
$multiplier   = 2;

$channel = new Brook\FanOut();
$channel->distributeWork(2, function($value) use ($multiplier) {
  return sprintf("%d %d", $value, $value * $multiplier);
});

for ($i=1; $i<=$messageCount; $i++) {
  $channel->enqueue($i);
  echo "sent $i", PHP_EOL;
}

for ($i=1; $i<=$messageCount; $i++) {
  $result = $channel->readFromSink();
  list($value, $multiplied) = explode(' ', $result);

  echo "got $value, $multiplied", PHP_EOL;
  assert($multiplied == ($multiplier * $value));
}

$channel->shutdown();
