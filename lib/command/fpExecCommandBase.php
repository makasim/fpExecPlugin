<?php

/**
 * 
 * @package fpExecPlugin
 * @subpackage Command
 *
 * @author Maksim Kotlyar <mkotlar@ukr.net>
 *
 */
abstract class fpExecCommandBase
{
  /**
   * 
   * @var sfTimer
   */
  protected $_timer;
  
  /**
   * 
   * @var array
   */
  protected $_options = array('verbose' => true);
  
  public function __construct(array $options = array())
  {
    $this->_timer = new sfTimer();
    $this->_timer->startTimer();
    
    $options = array_intersect_key($options, $this->_options);
    
    $this->_options = array_merge($this->_options, $options);
    
    $this->_initialize();
  }
  
  protected function _initialize()
  {
    
  }
  
  abstract public function exec();
  
  /**
   * 
   * @return sfTimer
   */
  public function getTimer()
  {
    return $this->_timer;
  }
  
  protected function _doExec($command, $verbose = true)
  {
    chdir(sfConfig::get('sf_root_dir'));
    
    $command = trim($command);

    $exitCode = 0;
    $result = array();
    
    $verbose ?
      passthru($command, $exitCode) : 
      exec($command, $result, $exitCode);
    
    if ((int) $exitCode > 0) {
      throw new Exception('Command: `' . $command . '`. Exit with error code: `' . $exitCode . '`');
    }
  }
  
  protected function _doExecBackground($command, $verbose = false)
  {
    $this->_doExec("nohup $command &", $verbose);
  }
  
  protected function _doExecUntilChanging($command, $verbose = false)
  {
    echo "\n";
    
    $current = 'curr';
    $preview = 'prev';
    $count = 0;
    while ($preview != $current) {
      ob_start();
      
      $this->_doExec($command, true);
      
      $preview = $current;
      $current = ob_get_clean();
      
      echo $verbose ? '.' : '';
      $count++;

      if ($count > 30) {
        echo $verbose ? ' | ' . trim($current) . "\n" : '';
        $count = 0;
      }

      sleep(5);
    }
    
    if ($count != 0) {
      echo $verbose ? ' | ' . trim($current) . "\n" : '';
    }
    
    echo "\n";
  }
  
  /**
   * 
   * @param string $name
   * @throws InvalidArgumentException
   * 
   * @return mixed
   */
  public function getOption($name)
  {
    if (!array_key_exists($name, $this->_options)) {
      throw new InvalidArgumentException('The option with a given name `'.$name.'` does not exist');
    }
    
    return $this->_options[$name];
  }
}