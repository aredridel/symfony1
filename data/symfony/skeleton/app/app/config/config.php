<?php

// symfony directories
if (is_readable(SF_ROOT_DIR.'/lib/symfony'))
{
  // symlink exists
  $sf_symfony_lib_dir  = SF_ROOT_DIR.'/lib/symfony';
  $sf_symfony_data_dir = SF_ROOT_DIR.'/data/symfony';
}
else
{
  // PEAR config
  if ((include('symfony/symfony/pear.php')) != 'OK')
  {
    throw new Exception('Unable to find symfony librairies');
  }
}

require_once($sf_symfony_lib_dir.'/symfony/config/sfConfig.class.php');

sfConfig::add(array(
  'sf_root_dir'         => SF_ROOT_DIR,
  'sf_app'              => SF_APP,
  'sf_environment'      => SF_ENVIRONMENT,
  'sf_debug'            => SF_DEBUG,
  'sf_symfony_lib_dir'  => $sf_symfony_lib_dir,
  'sf_symfony_data_dir' => $sf_symfony_data_dir,
  'sf_test'             => false,
));

// start timer
if (sfConfig::get('sf_debug'))
{
  sfConfig::set('sf_timer_start', microtime(true));
}

// directory layout
include($sf_symfony_data_dir.'/symfony/config/constants.php');

// include path
set_include_path(
  sfConfig::get('sf_lib_dir').PATH_SEPARATOR.
  sfConfig::get('sf_symfony_lib_dir').PATH_SEPARATOR.
  sfConfig::get('sf_app_lib_dir').PATH_SEPARATOR.
  sfConfig::get('sf_model_dir').PATH_SEPARATOR.
  get_include_path()
);

// check to see if we're not in a cache cleaning process
require_once('symfony/util/sfToolkit.class.php');
if (sfToolkit::hasLockFile(SF_ROOT_DIR.DIRECTORY_SEPARATOR.SF_APP.'_'.SF_ENVIRONMENT.'.lck', 5))
{
  // application is not yet available
  include(SF_WEB_DIR.'/unavailable.html');
  die(1);
}

// require project configuration
require_once(dirname(__FILE__).'/../../config/config.php');

// go
$bootstrap = sfConfig::get('sf_config_cache_dir').'/config_bootstrap_compile.yml.php';
if (is_readable($bootstrap))
{
  require_once($bootstrap);
}
else
{
  require_once 'symfony/symfony.php';
}

?>