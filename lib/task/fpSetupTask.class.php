<?php

class fpSetupTask extends sfBaseTask
{
  protected $optionsValues;

  protected function configure()
  {
    $this->namespace        = 'fp';
    $this->name             = 'setup';
    $this->briefDescription = 'Prepares sets of files and setup spesific variables';

    $this->detailedDescription = <<<EOF

EOF;

//    $this->addArguments(array(
//      new sfCommandArgument('profile', sfCommandArgument::REQUIRED, 'Build profile')));

    $this->addOptions(array(
      new sfCommandOption('files', null, sfCommandOption::PARAMETER_REQUIRED, 'A file contains sets of files to be processed'),
      new sfCommandOption('vars', null, sfCommandOption::PARAMETER_REQUIRED, 'A file contains sets of setup related data, such as connection to database'),
      new sfCommandOption('verbose', null, sfCommandOption::PARAMETER_NONE, 'Show more info on what is going on during the execution'),
      new sfCommandOption('ovewrite', null, sfCommandOption::PARAMETER_NONE, 'Show more info on what is going on during the execution')));

    // init total timer
    $this->_totalTimer = new sfTimer();
    $this->_totalTimer->startTimer();
  }

  /**
   * (non-PHPdoc)
   * @see sfTask::execute()
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->optionsValues = $options;

    $this->logVerbose('Parse a `vars` file');
    $vars = $this->_parseConfigFile($options['vars']);

    $this->logVerbose('Parse a `files` file');
    $files = $this->_parseConfigFile($options['files']);

    $this->logVerbose('Normilize files pathes');
    $normalizedFiles = array();
    foreach ($files as $sourceFile => $targetFile) {

      if (empty($targetFile)) {
        throw new Exception('The target file cannot be empty. Source file: `'.$sourceFile.'`');
      }

      $sourceFile = $this->_guessFilePath($sourceFile);
      $targetFile = dirname($sourceFile).'/'.$targetFile;
      if ($sourceFile === $targetFile) {
        throw new Exception('The source file `'.$sourceFile.'` cannot be equals to target file `'.$targetFile.'`.');
      }

      $normalizedFiles[$sourceFile] = $targetFile;
    }

    $this->logVerbose('Process files');
    foreach ($normalizedFiles as $sourceFile => $targetFile) {
      $this->_processFile($targetFile, $sourceFile, $vars);
    }
  }

  /**
   *
   * @param string $targetFile
   * @param string $sourceFile
   * @param arrray $vars
   *
   * @throws Exception
   *
   * @return void
   */
  protected function _processFile($target, $source, array $vars = array())
  {
    if (!$this->_isOverwrite() && file_exists($target)) {
      $this->logVerbose('Skipped. Target file `'.$target.'` exists. Use --overwrite option to force it changes');
      return;
    }

    if (!$fp = fopen($target, 'w+')) {
      throw new Exception('Failed to open file `'.basename($target).'`%s for writing');
    }

    fputs($fp, $this->_renderFile($source, $vars));
    fclose($fp);

    $this->logVerbose('Target file `'.$target.'` is created\updated on the base of file: `'.$source.'`');
  }

  /**
   * Renders a template content and assembles the variable placeholders
   *
   * @param string $source the template content
   * @param array $vars
   *
   * @return string the assembled content
   */
  protected function _renderFile($source, array $input_vars = array())
  {
    $content = file_get_contents($source);
    $matches = array();
  //  var_dump($content);
    if (!preg_match_all('/%%(.*?)%%/m', $content, $matches)) {
      $this->logVerbose('Source file does not contain any of predined varables (Looks like: %%VAR%%)');
      return;
    }

    $matches = array_unique($matches[1]);
    $this->logVerbose('There were some predined vars found: `'.implode('`, `', $matches).'`');

    $vars = array();
    foreach($matches as $key) {
      if (array_key_exists($key, $input_vars)) {
        $vars["%%{$key}%%"] = $input_vars[$key];
        continue;
      }

      throw new Exception('There is predefined var `'.$key.'` in file `'.$source.'` but the value for this var is not defined. Defined values are `'.implode('`, `', array_keys($input_vars)).'`');
    }

    return str_replace(array_keys($vars), array_values($vars), $content);
  }



  /**
   *
   * @param string $file
   *
   * @throws Exception if config file invalid
   * @throws Exception if config file empty
   *
   * @return array
   */
  protected function _parseConfigFile($file)
  {
    $content = file($this->_guessFilePath($file));
    $formattedResult = array();
    foreach ($content as $line => $row) {
      $row = trim($row);
      if (empty($row)) continue;

      $explodedRow = explode('=', $row, 2);

      if (count($explodedRow) != 2) {
        throw new Exception('The config file contains invalid option on line: `'.$line.'` and content: `'.$row.'`');
      }

      $formattedName = trim($explodedRow[0]);
      if (empty($formattedName)) {
        throw new Exception('The name of the option cannot be empty: `'.$line.'` and content: `'.$row.'`');
      }

      $formattedValue = trim($explodedRow[1]);

      $formattedResult[$formattedName] = $formattedValue;
    }

    return $formattedResult;
  }

  /**
   *
   * @param string $file
   *
   * @throws Exceptino if the source cannot be found.
   *
   * @return string
   */
  protected function _guessFilePath($file)
  {
    // guess file form project root config directory.
    $relativeConfigPath = sfConfig::get('sf_root_dir').'/config/'.$file;
    if (file_exists($relativeConfigPath)) {
      $this->logVerbose('Guess path is `'.$relativeConfigPath.'` ');
      return $relativeConfigPath;
    }

    // guess relative from project root
    $relativeRootPath = sfConfig::get('sf_root_dir') . '/'. $file;
    if (file_exists($relativeRootPath)) {
      $this->logVerbose('Guess path is `'.$relativeConfigPath.'` ');
      return $relativeRootPath;
    }

    // guess absolute
    $absolutePath = $file;
    if (file_exists($absolutePath)) {
      $this->logVerbose('Guess path is `'.$relativeConfigPath.'` ');
      return $absolutePath;
    }

    throw new Exception("Provided path to build profile is not exist or invalid. Given parameter is `{$file}`. The next pathes were tried: " .
      "\n2) Relative from config: `{$relativeConfigPath}`," .
      "\n3) Relative from prj root: `{$relativeRootPath}`," .
      "\n4) Absolute: `{$absolutePath}`\n");
  }

  /**
   *
   * @return bool
   */
  protected function isVerbose()
  {
    return (bool) $this->optionsValues['verbose'];
  }

  /**
   *
   * @return bool
   */
  protected function _isOverwrite()
  {
    return (bool) $this->optionsValues['ovewrite'];
  }

  /**
   *
   * @param array|string $messages
   */
  protected function logVerbose($messages)
  {
    if ($this->isVerbose()) {
      $this->log($messages);
    }
  }
}