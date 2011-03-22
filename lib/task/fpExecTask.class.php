<?php

class fpExecTask extends sfBaseTask
{
  protected $_totalTimer;
  
  protected function configure()
  {
    $this->namespace        = 'fp';
    $this->name             = 'exec';
    $this->briefDescription = 'run a command or a list of commands';

    $this->detailedDescription = <<<EOF
Execute a list of commands
EOF;

    $this->addArguments(array(
      new sfCommandArgument('profile', sfCommandArgument::REQUIRED, 'Build profile')));
      
    $this->addOptions(array(
      new sfCommandOption('verbose', null, sfCommandOption::PARAMETER_NONE, 'Hide the comands output')));
      
    // init total timer
    $this->_totalTimer = new sfTimer();
    $this->_totalTimer->startTimer();
  }

  protected function execute($arguments = array(), $options = array())
  { 
    try {
      
      foreach ($this->_prepareCommands($arguments['profile']) as $command) {
        $options['command'] = $command;
        $this->_doCommand($options);
      }
      
      $this->showTime($this->getTotalTimmer(), 'Total time: ');
      
    } catch (Exception $e) {
      $this->showTime($this->getTotalTimmer(), 'Total time: ');
      
      throw $e;
    }
  }
  
  protected function _doCommand($options)
  {
    $command = new fpExecCommandCommon($options);
    if (!$command->getOption('command')) return;
    
    $this->logSection("Command :", $command->getOption('command'));
    
    $command->exec();
    
    $options['verbose'] && $this->showTime($command->getTimer());
  }
  
  /**
   * 
   * @param string $source
   * 
   * @throws Exceptino if the source cannot be found.
   * 
   * @return array
   */
  protected function _prepareCommands($source)
  {
    // guess file form project root config directory.
    $relativeConfigPath = sfConfig::get('sf_root_dir').'/config/'.$source;
    if (file_exists($relativeConfigPath)) {
      return file($relativeConfigPath);
    }

    // guess relative from project root
    $relativeRootPath = sfConfig::get('sf_root_dir') . '/'. $source;
    if (file_exists($relativeRootPath)) {
      return file($relativeRootPath);
    }
    
    // guess absolute
    $absolutePath = $source;
    if (file_exists($absolutePath)) {
      return file($absolutePath);
    }
    
    throw new Exception("Provided path to build profile is not exist or invalid. Given parameter is `{$source}`. The next pathes were tried: " . 
      "\nRelative from config `{$relativeConfigPath}`," .
      "\nRelative from root - `{$relativeRootPath}`," .
      "\nAbsolute - `{$absolutePath}`");
  }
  
  /**
   * @return sfTimer
   */
  protected function getTotalTimmer()
  {
    return $this->_totalTimer;
  }

  protected function showTime(sfTimer $timer, $message = 'Time : ', $afterMessage = "\n\n")
  {
    $timer->addTime();
    $this->log('');
    $this->logSection($message, date('i:s', (int) $timer->getElapsedTime()));
    $this->log('');
    $this->log('');
  }
}