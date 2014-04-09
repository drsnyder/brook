<?php

namespace Brook;

class Util {

  public static function genURI($port, $host="localhost") {
    return sprintf("tcp://%s:%d", $host, $port);
  }

  public static function passThroughCallback($msg) {
    return $msg;
  }

}
