<?php

namespace Brook;

interface TaskInterface {

  public function setup();
  public function work($message);
  public function tearDown();

}
