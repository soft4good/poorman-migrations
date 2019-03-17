<?php

class Logger
{
  protected $logs;
  protected $stdOut;

  public $hasErrors;

  function __construct() {
    $this->stdOut = false;
    $this->hasErrors = false;
    $this->logs = [];
  }

  public function getLogs() {
    return $this->logs;
  }

  public function clean() {
    $this->logs = [];
  }

  private function logEntry( $level, $message) {
    if ( $level === 'ERROR') {
      $this->hasErrors = true;
    }

    $this->logs[] = $entry = [
      'date' => date('Y-m-d H:i:s'),
      'level' => $level,
      'message' => $message
    ];

    if ( $this->stdOut ) {
      echo "[$entry[date]] $entry[level]: $entry[message]\n";
    }
  }

  public function log( $level, $messages ) {
    if ( is_array($messages) ) {
      foreach( $messages as $message) {
        $this->logEntry( $level, $message );
      }
    }
    else {
      $this->logEntry( $level, $messages );
    }
  }

  public function toggleStdOut() {
    $this->stdOut = !$this->stdOut;
  }

}
