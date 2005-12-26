<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004, 2005 Sean Kerr.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Pre-initialization script.
 *
 * @package    symfony
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */

/**
 * Handles autoloading of classes that have been specified in autoload.yml and myautoload.yml.
 *
 * @param string A class name.
 *
 * @return void
 */
function __autoload($class)
{
  static $loaded = false;

  if (!$loaded)
  {
    try
    {
      // load the list of autoload classes
      $config = sfConfigCache::checkConfig(sfConfig::get('sf_app_config_dir_name').'/autoload.yml');

      $loaded = true;
    }
    catch (sfException $e)
    {
      $e->printStackTrace();
    }
    catch (Exception $e)
    {
      // unknown exception
      $e = new sfException($e->getMessage());

      $e->printStackTrace();
    }

    require_once($config);
  }

  $classes = sfConfig::get('sf_class_autoload', array());

  if (!isset($classes[$class]))
  {
    // see if the file exists in the current module lib directory
    // must be in a module context
    $current_module = sfContext::getInstance()->getModuleName();
    if ($current_module)
    {
      $module_lib = sfConfig::get('sf_app_module_dir').'/'.$current_module.'/'.sfConfig::get('sf_app_module_lib_dir_name').'/'.$class.'.class.php';
      if (is_readable($module_lib))
      {
        require_once($module_lib);

        return;
      }
    }

    // unspecified class
    $error = 'Autoloading of class "%s" failed. Try to clear the symfony cache and refresh. [err0003]';
    $error = sprintf($error, $class);
    $e = new sfAutoloadException($error);

    $e->printStackTrace();
  }
  else
  {
    // class exists, let's include it
    require_once($classes[$class]);
  }
}

try
{
  ini_set('unserialize_callback_func', '__autoload');

  // symfony version information
/*        
  define('sf_app_name',          'symfony');
  define('sf_app_major_version', '1');
  define('sf_app_minor_version', '0');
  define('sf_app_micro_version', '0');
  define('sf_app_branch',        'dev-1.0.0');
  define('sf_app_status',        'DEV');
  define('sf_app_version',       SF_APP_MAJOR_VERSION.'.'.
                                 SF_APP_MINOR_VERSION.'.'.
                                 SF_APP_MICRO_VERSION.'-'.SF_APP_STATUS);
  define('sf_app_url',           'http://www.symfony-project.com/');
  define('sf_app_info',          SF_APP_NAME.' '.SF_APP_VERSION.' ('.SF_APP_URL.')');
*/

  // get config instance
  $sf_app_config_dir_name = sfConfig::get('sf_app_config_dir_name');

  if (!sfConfig::get('sf_in_bootstrap'))
  {
    // YAML support
    require_once('spyc/spyc.php');
    require_once('symfony/util/sfYaml.class.php');

    // cache support
    require_once('symfony/cache/sfCache.class.php');
    require_once('symfony/cache/sfFileCache.class.php');

    // config support
    require_once('symfony/config/sfConfigCache.class.php');
    require_once('symfony/config/sfConfigHandler.class.php');
    require_once('symfony/config/sfYamlConfigHandler.class.php');
    require_once('symfony/config/sfAutoloadConfigHandler.class.php');
    require_once('symfony/config/sfRootConfigHandler.class.php');

    // basic exception classes
    require_once('symfony/exception/sfException.class.php');
    require_once('symfony/exception/sfAutoloadException.class.php');
    require_once('symfony/exception/sfCacheException.class.php');
    require_once('symfony/exception/sfConfigurationException.class.php');
    require_once('symfony/exception/sfParseException.class.php');

    // utils
    require_once('symfony/util/sfParameterHolder.class.php');

    // create bootstrap file for next time
    if (!sfConfig::get('sf_debug') && !sfConfig::get('sf_test'))
    {
      sfConfigCache::checkConfig($sf_app_config_dir_name.'/bootstrap_compile.yml');
    }
  }

  // set exception format
  sfException::setFormat(isset($_SERVER['HTTP_HOST']) ? 'html' : 'plain');

  if (sfConfig::get('sf_debug'))
  {
    // clear our config and module cache
    sfConfigCache::clear();
  }

  // load base settings
  include(sfConfigCache::checkConfig($sf_app_config_dir_name.'/logging.yml'));
  sfConfigCache::import($sf_app_config_dir_name.'/php.yml');
  include(sfConfigCache::checkConfig($sf_app_config_dir_name.'/settings.yml'));
  include(sfConfigCache::checkConfig($sf_app_config_dir_name.'/app.yml'));

  // error settings
  ini_set('display_errors', sfConfig::get('sf_debug') ? 'on' : 'off');
  error_reporting(sfConfig::get('sf_error_reporting'));

  // compress output
  ob_start(sfConfig::get('sf_compressed') ? 'ob_gzhandler' : '');

/*
  if (sfConfig::get('sf_logging_active'))
  {
    set_error_handler(array('sfLogger', 'errorHandler'));
  }
*/

  // required core classes for the framework
  // create a temp var to avoid substitution during compilation
  if (!sfConfig::get('sf_debug') && !sfConfig::get('sf_test'))
  {
    $core_classes = $sf_app_config_dir_name.'/core_compile.yml';
    sfConfigCache::import($core_classes);
  }

  if (sfConfig::get('sf_routing'))
  {
    sfConfigCache::import($sf_app_config_dir_name.'/routing.yml');
  }
}
catch (sfException $e)
{
  $e->printStackTrace();
}
catch (Exception $e)
{
  // unknown exception
  $e = new sfException($e->getMessage());

  $e->printStackTrace();
}

?>