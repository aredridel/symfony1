<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfPhpConfigHandler allows you to override php.ini configuration at runtime.
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPhpConfigHandler extends sfYamlConfigHandler
{
  /**
   * Execute this configuration handler.
   *
   * @param array An array of absolute filesystem path to a configuration file.
   *
   * @return string Data to be written to a cache file.
   *
   * @throws <b>sfConfigurationException</b> If a requested configuration file does not exist or is not readable.
   * @throws <b>sfParseException</b> If a requested configuration file is improperly formatted.
   * @throws <b>sfInitializationException</b> If a php.yml key check fails.
   */
  public function execute($configFiles)
  {
    $this->initialize();

    // parse the yaml
    $config = $this->parseYamls($configFiles);

    // init our data array
    $data = array();

    // get all php.ini configuration
    $configs = ini_get_all();

    // let's do our fancy work
    if (isset($config['set']))
    {
      foreach ($config['set'] as $key => $value)
      {
        $key = strtolower($key);

        // key exists?
        if (!array_key_exists($key, $configs))
        {
          $error = sprintf('Configuration file "%s" specifies key "%s" which is not a php.ini directive', $configFiles[0], $key);
          throw new sfParseException($error);
        }

        // key is overridable?
        if ($configs[$key]['access'] != 7)
        {
          $error = sprintf('Configuration file "%s" specifies key "%s" which cannot be overrided', $configFiles[0], $key);
          throw new sfParseException($error);
        }

        // escape value
        $value = str_replace("'", "\\'", $value);

        $data[] = sprintf("ini_set('%s', '%s');", $key, $value);
      }
    }

    if (isset($config['check']))
    {
      foreach ($config['check'] as $key => $value)
      {
        $key = strtolower($key);

        // key exists?
        if (!array_key_exists($key, $configs))
        {
          $error = sprintf('Configuration file "%s" specifies key "%s" which is not a php.ini directive [err0002]', $configFiles[0], $key);
          throw new sfParseException($error);
        }

        if (ini_get($key) != $value)
        {
          $error = sprintf('Configuration file "%s" specifies that php.ini "%s" key must be set to "%s". The current value is "%s" (%s). [err0001]', $configFiles[0], $key, $value, ini_get($key), $this->get_ini_path());
          throw new sfInitializationException($error);
        }
      }
    }

    // compile data
    $retval = sprintf("<?php\n".
                      "// auto-generated by sfPhpConfigHandler\n".
                      "// date: %s\n%s\n?>", date('Y/m/d H:i:s'), implode("\n", $data));

    return $retval;
  }

  private function get_ini_path()
  {
    $cfg_path = get_cfg_var('cfg_file_path');
    if ($cfg_path == '')
    {
      $ini_path = 'WARNING: system is not using a php.ini file';
    }
    else
    {
      $ini_path = 'php.ini location: "%s"';
      $ini_path = sprintf($ini_path, $cfg_path);
    }

    return $ini_path;
  }
}
