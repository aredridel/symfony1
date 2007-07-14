<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage log
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfFileLogger implements sfLoggerInterface
{
  protected
    $fp = null;

  /**
   * Initializes the file logger.
   *
   * @param array Options for the logger
   */
  public function initialize($options = array())
  {
    if (!isset($options['file']))
    {
      throw new sfConfigurationException('File option is mandatory for a file logger.');
    }

    $dir = dirname($options['file']);

    if (!is_dir($dir))
    {
      mkdir($dir, 0777, 1);
    }

    if (!is_writable($dir) || (file_exists($options['file']) && !is_writable($options['file'])))
    {
      throw new sfFileException(sprintf('Unable to open the log file "%s" for writing.', $options['file']));
    }

    $this->fp = fopen($options['file'], 'a');
  }

  /**
   * Logs a message.
   *
   * @param string Message
   * @param string Message priority
   */
  public function log($message, $priority = null)
  {
    $line = sprintf("%s %s [%s] %s%s", strftime('%b %d %H:%M:%S'), 'symfony', sfLogger::getPriorityName($priority), $message, DIRECTORY_SEPARATOR == '\\' ? "\r\n" : "\n");

    flock($this->fp, LOCK_EX);
    fwrite($this->fp, $line);
    flock($this->fp, LOCK_UN);
  }

  /**
   * Executes the shutdown method.
   */
  public function shutdown()
  {
    if ($this->fp)
    {
      fclose($this->fp);
    }
  }
}
