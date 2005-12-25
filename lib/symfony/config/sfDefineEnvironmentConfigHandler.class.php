<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfDefineEnvironmentConfigHandler extends sfYamlConfigHandler
{
  /**
   * Execute this configuration handler.
   *
   * @param  string An absolute filesystem path to a configuration file.
   *
   * @return string Data to be written to a cache file.
   *
   * @throws sfConfigurationException If a requested configuration file does not exist or is not readable.
   * @throws sfParseException If a requested configuration file is improperly formatted.
   */
  public function & execute($configFile, $param = array())
  {
    // parse the yaml
    $config = $this->parseYaml($configFile);

    // get our prefix
    $prefix = $this->getParameterHolder()->get('prefix', '');

    // add dynamic prefix if needed
    if (isset($param['prefix']))
    {
      $prefix .= strtoupper($param['prefix']);
    }

    // get default configuration
    $defaultConfigFile = sfConfig::get('sf_symfony_data_dir').'/symfony/config/'.basename($configFile);
    if (is_readable($defaultConfigFile))
    {
      $defaultConfig = $this->parseYaml($defaultConfigFile);
      $defaultConfig = $defaultConfig['default'];
    }
    if (!isset($defaultConfig))
    {
      $defaultConfig = array();
    }

    // get all configuration
    if (isset($config['all']))
    {
      $allConfig = $config['all'];
    }
    if (!isset($allConfig))
    {
      $allConfig = array();
    }

    // merge with environment configuration if needed
     $myConfig = sfToolkit::array_deep_merge($defaultConfig, $allConfig);
    if (isset($config[sfConfig::get('sf_environment')]) && is_array($config[sfConfig::get('sf_environment')]))
    {
      $myConfig = sfToolkit::array_deep_merge($myConfig, $config[sfConfig::get('sf_environment')]);
    }

    $values = array();
    foreach ($myConfig as $category => $keys)
    {
      $values = array_merge($values, $this->getValues($prefix, $category, $keys));
    }

    // init our data array
    $data = array();

    // populate data
/*
    foreach ($values as $key => $value)
    {
      $tmp = "\$config->set('%s', %s);";
      $data[] = sprintf($tmp, $key, $value);
    }
*/
    // compile data
    if ($values)
    {
      $retval = "<?php\n".
                "// auth-generated by sfDefineEnvironmentConfigHandler\n".
                "// date: %s\nsfConfig::add(%s);\n?>";
      $retval = sprintf($retval, date('m/d/Y H:i:s'), var_export($values, true));
    }

    return $retval;
  }

  protected function getValues($prefix, $category, $keys)
  {
    if (!is_array($keys))
    {
      list($key, $value) = $this->fixCategoryValue($prefix.$category, '', $keys);
      return array($key => $value);
    }

    $values = array();

    $category = $this->fixCategoryName($category, $prefix);

    // loop through all key/value pairs
    foreach ($keys as $key => &$value)
    {
      list($key, $value) = $this->fixCategoryValue($category, $key, $value);
      $values[$key] = $value;
    }

    return $values;
  }

  protected function fixCategoryValue($category, $key, $value)
  {
    // prefix the key
    $key = strtolower($category.$key);

    // replace constant values
    $value = $this->replaceConstants($value);

    return array($key, $value);
  }

  protected function fixCategoryName($category, $prefix)
  {
    // categories starting without a period will be prepended to the key
    if ($category[0] != '.')
    {
      $category = $prefix.preg_replace('/^[^_]+_/', '', $category).'_';
    }
    else
    {
      $category = $prefix;
    }

    return strtolower($category);
  }
}

?>