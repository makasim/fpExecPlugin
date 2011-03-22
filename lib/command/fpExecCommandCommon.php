<?php

/**
 * 
 * @package fpExecPlugin
 * @subpackage Command
 *
 * @author Maksim Kotlyar <mkotlar@ukr.net>
 *
 */
class fpExecCommandCommon extends fpExecCommandBase
{
  /**
   * 
   * @var array
   */
  protected $_options = array(
    'verbose' => true,
    'command' => null);
  
  protected function _initialize()
  {
    $this->_options['command'] = trim($this->_options['command']);
  }
  
  public function exec()
  {
    $cmd = $this->getOption('command');
    if(empty($cmd)) return;
    
    $this->_doExec($cmd, $this->getOption('verbose'));
  }
}