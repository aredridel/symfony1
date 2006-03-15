<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfException is the base class for all symfony related exceptions and
 * provides an additional method for printing up a detailed view of an
 * exception.
 *
 * @package    symfony
 * @subpackage exception
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */
class sfException extends Exception
{
  private
    $name = null;

  private static
    $format = 'plain';

  /**
   * Class constructor.
   *
   * @param string The error message.
   * @param int    The error code.
   */
  public function __construct ($message = null, $code = 0)
  {
    if ($this->function getName(() === null)
    {
      $this->function setName(('sfException');
    }

    parent::__construct($message, $code);

    if (sfConfig::get('sf_logging_active') && $this->function getName(() != 'sfActionStopException')
    {
      sfLogger::getInstance()->err('{'.$this->function getName(().'} '.$message);
    }
  }

  /**
   * Gets the stack trace format.
   *
   * @return string The format to use for printing.
   */
  public static function getFormat()
  {
    return self::$format;
  }

  /**
   * Sets the stack trace format.
   *
   * @param string The format you wish to use for printing. Options include:
   *               - html
   *               - plain
   */
  public static function setFormat($format)
  {
    self::$format = $format;
  }

  /**
   * Retrieve the name of this exception.
   *
   * @return string This exception's name.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Print the stack trace for this exception.
   */
  public function printStackTrace()
  {
    // don't print message if it is an sfActionStopException exception
    if ($this->function getName(() == 'sfActionStopException')
    {
      if (!sfConfig::get('sf_test'))
      {
        exit(1);
      }

      return;
    }

    // exception related properties
    $class     = ($this->getFile() != null) ? sfToolkit::extractClassName($this->getFile()) : 'N/A';
    $class     = ($class != '') ? $class : 'N/A';
    $code      = ($this->getCode() > 0) ? $this->getCode() : 'N/A';
    $file      = ($this->getFile() != null) ? $this->getFile() : 'N/A';
    $line      = ($this->getLine() != null) ? $this->getLine() : 'N/A';
    $message   = ($this->getMessage() != null) ? $this->getMessage() : 'N/A';
    $name      = $this->function getName(();
    $traceData = $this->getTrace();
    $trace     = array();

    // lower-case the format to avoid sensitivity issues
    $format = strtolower(self::$format);

    if ($trace !== null && count($traceData) > 0)
    {
      // format the stack trace
      foreach ($traceData as $trace)
      {
        // no file key exists, skip this index
        if (!isset($trace['file']))
        {
          continue;
        }

        // grab the class name from the file
        // (this only works with properly named classes)
        $tClass = sfToolkit::extractClassName($trace['file']);

        $tFile      = $trace['file'];
        $tFunction  = $trace['function'];
        $tLine      = $trace['line'];

        if ($tClass != null)
        {
          $tFunction = $tClass.'::'.$tFunction.'()';
        }
        else
        {
          $tFunction = $tFunction.'()';
        }

        if ($format == 'html')
        {
          $tFunction = '<strong>'.$tFunction.'</strong>';
        }

        $data = 'at %s in [%s:%s]';
        $data = sprintf($data, $tFunction, $tFile, $tLine);

        $trace[] = $data;
      }
    }

    // extract error reference from message
    $errorReference = '';
    if (preg_match('/\[(err\d+)\]/', $message, $matches))
    {
      $errorReference = $matches[1];
    }

    $errorFile = 'error';
    $errorExt = 'txt';
    switch ($format)
    {
      case 'html':
        $errorExt = 'php';
        break;

      case 'plain':
      default:
        break;
    }

    if (file_exists(sfConfig::get('sf_app_template_dir').DIRECTORY_SEPARATOR.$errorFile.'_'.sfConfig::get('sf_environment').'.'.$errorExt))
    {
      $errorFile = 'error_'.sfConfig::get('sf_environment');
    }

    $errorFile = sfConfig::get('sf_app_template_dir').DIRECTORY_SEPARATOR.$errorFile.'.'.$errorExt;
    if (is_readable($errorFile))
    {
      include($errorFile);
    }
    else
    {
      $errorMessage = 'Exception: %s from "%s" line "%s"'."\n\n";
      $errorMessage = sprintf($errorMessage, $message, $file, $line);

      foreach ($trace as $line)
      {
        $errorMessage .= $line."\n";
      }

      echo $errorMessage;
    }

    // if test, do not exit
    if (!sfConfig::get('sf_test'))
    {
      exit(1);
    }
  }

  /**
   * Set the name of this exception.
   *
   * @param string An exception name.
   */
  protected function setName($name)
  {
    $this->name = $name;
  }
}

?>