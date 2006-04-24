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
    if ($this->getName() === null)
    {
      $this->setName('sfException');
    }

    parent::__construct($message, $code);

    if (sfConfig::get('sf_logging_active') && $this->getName() != 'sfActionStopException')
    {
      sfLogger::getInstance()->err('{'.$this->getName().'} '.$message);
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
  public function getName ()
  {
    return $this->name;
  }

  /**
   * Print the stack trace for this exception.
   */
  public function printStackTrace ($exception = null)
  {
    if (!$exception)
    {
      $exception = $this;
    }

    // don't print message if it is an sfActionStopException exception
    if (method_exists($exception, 'getName') && $exception->getName() == 'sfActionStopException')
    {
      if (!sfConfig::get('sf_test'))
      {
        exit(1);
      }

      return;
    }

    // send an error 500 if not in debug mode
    if (!sfConfig::get('sf_debug'))
    {
      header('HTTP/1.0 500 Internal Server Error');
      $file = sfConfig::get('sf_web_dir').'/error500.html';
      if (is_readable($file))
      {
        include($file);
      }
      else
      {
        error_log($exception->getMessage());
        echo 'internal server error';
      }

      if (!sfConfig::get('sf_test'))
      {
        exit(1);
      }

      return;
    }

    // lower-case the format to avoid sensitivity issues
    $format = strtolower(self::$format);

    $message = ($exception->getMessage() != null) ? $exception->getMessage() : 'n/a';
    $name    = get_class($exception);

    $traceData = $exception->getTrace();
    array_unshift($traceData, array(
      'function' => '',
      'file'     => ($exception->getFile() != null) ? $exception->getFile() : 'n/a',
      'line'     => ($exception->getLine() != null) ? $exception->getLine() : 'n/a',
      'args'     => array(),
    ));

    $traces = array();
    if ($format == 'html')
    {
      $lineFormat = 'at <strong>%s%s%s</strong>(%s)<br />in <em>%s</em> line %s <a href="#" onclick="toggle(\'%s\'); return false;">...</a><br /><ul id="%s" style="display: %s">%s</ul>';
    }
    else
    {
      $lineFormat = 'at %s%s%s(%s) in %s line %s';
    }
    for ($i = 0, $count = count($traceData); $i < $count; $i++)
    {
      $line = isset($traceData[$i]['line']) ? $traceData[$i]['line'] : 'n/a';
      $file = isset($traceData[$i]['file']) ? $traceData[$i]['file'] : 'n/a';
      $shortFile = preg_replace(array('#^'.preg_quote(sfConfig::get('sf_root_dir')).'#', '#^'.preg_quote(realpath(sfConfig::get('sf_symfony_lib_dir'))).'#'), array('SF_ROOT_DIR', 'SF_SYMFONY_LIB_DIR'), $file);
      $args = isset($traceData[$i]['args']) ? $traceData[$i]['args'] : array();
      $traces[] = sprintf($lineFormat,
        (isset($traceData[$i]['class']) ? $traceData[$i]['class'] : ''),
        (isset($traceData[$i]['type']) ? $traceData[$i]['type'] : ''),
        $traceData[$i]['function'],
        $this->formatArgs($args, false, $format),
        $shortFile,
        $line,
        'trace_'.$i,
        'trace_'.$i,
        $i == 0 ? 'block' : 'none',
        $this->fileExcerpt($file, $line)
      );
    }

    // extract error reference from message
    $error_reference = '';
    if (preg_match('/\[(err\d+)\]/', $message, $matches))
    {
      $error_reference = $matches[1];
    }

    // dump main objects values
    $sf_settings = '';
    if (sfContext::hasInstance())
    {
      $context = sfContext::getInstance();
      $settingsTable = $this->settingsAsHtml($context);
      $requestTable  = $this->requestAsHtml($context);
      $responseTable = $this->responseAsHtml($context);
      $globalsTable = $this->globalsAsHtml($context);
    }

    include(sfConfig::get('sf_symfony_data_dir').'/data/exception.'.($format == 'html' ? 'php' : 'txt'));

    // if test, do not exit
    if (!sfConfig::get('sf_test'))
    {
      exit(1);
    }
  }

  private function globalsAsHtml($context)
  {
    $values = array();
    foreach (array('cookie', 'server', 'get', 'post', 'files', 'env', 'session') as $name)
    {
      foreach ($GLOBALS['_'.strtoupper($name)] as $key => $value)
      {
        $values[$name.'/'.$key] = $value;
      }
    }

    ksort($values);

    return $this->formatArrayAsTable($values);
  }

  private function requestAsHtml($context)
  {
    $parameters = $this->flattenParameterHolder($context->getRequest()->getParameterHolder());
    $attributes = $this->flattenParameterHolder($context->getRequest()->getAttributeHolder(), 'attribute');

    $values = array_merge($parameters, $attributes);

    ksort($values);

    return $this->formatArrayAsTable($values);
  }

  private function responseAsHtml($context)
  {
    $parameters = $this->flattenParameterHolder($context->getResponse()->getParameterHolder());

    $headers = array();
    foreach ($context->getResponse()->getHttpHeaders() as $key => $value)
    {
      $headers['http_header/'.$key] = $value;
    }

    $cookies = array();
    foreach ($context->getResponse()->getCookies() as $key => $value)
    {
      $cookies['cookie/'.$key] = $value;
    }

    $values = array_merge($parameters, $headers, $cookies);

    ksort($values);

    return $this->formatArrayAsTable($values);
  }

  private function settingsAsHtml($context)
  {
    $config = sfConfig::getAll();

    ksort($config);

    return $this->formatArrayAsTable($config);
  }

  private function formatArrayAsTable($values)
  {
    $table = '<table cellspacing="0" class="vars"><tr><th>variable</th><th>value</th></tr>';
    foreach ($values as $key => $value)
    {
      $table .= '<tr><td>'.$key.'</td><td>'.$this->formatArgs($value, true).'</td></tr>';
    }
    $table .= '</table>';

    return $table;
  }

  public function flattenParameterHolder($parameterHolder, $prefix = 'parameter')
  {
    $values = array();
    foreach ($parameterHolder->getNamespaces() as $ns)
    {
      foreach ($parameterHolder->getAll($ns) as $key => $value)
      {
        $values[$prefix.'/'.$ns.'/'.$key] = $value;
      }
    }

    ksort($values);

    return $values;
  }

  private function fileExcerpt($file, $line)
  {
    if (is_readable($file))
    {
      $content = preg_split('#<br />#', highlight_file($file, true));

      $lines = array();
      for ($i = max($line - 3, 0), $max = min($line + 3, count($content)); $i <= $max; $i++)
      {
        $lines[] = '<li'.($i == $line ? ' class="selected"' : '').'>'.$content[$i - 1].'</li>';
      }

      return '<ol start="'.($line - 3).'">'.implode("\n", $lines).'</ol>';
    }
  }

  private function formatArgs($args, $single = false, $format = 'html')
  {
    $result = array();

    $single and $args = array($args);

    foreach ($args as $key => $value)
    {
      if (is_object($value))
      {
        $result[] = ($format == 'html' ? '<em>object</em>' : 'object').'(\''.get_class($value).'\')';
      }
      else if (is_array($value))
      {
        $result[] = ($format == 'html' ? '<em>array</em>' : 'array').'('.self::formatArgs($value).')';
      }
      else if ($value === null)
      {
        $result[] = '<em>null</em>';
      }
      else if (!is_int($key))
      {
        $result[] = "'$key' =&gt; '$value'";
      }
      else
      {
        $result[] = "'".$value."'";
      }
    }

    return implode(', ', $result);
  }

  /**
   * Set the name of this exception.
   *
   * @param string An exception name.
   */
  protected function setName ($name)
  {
    $this->name = $name;
  }
}

?>
