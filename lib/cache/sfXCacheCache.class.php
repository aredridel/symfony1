<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Cache class that stores cached content in XCache.
 *
 * @package    symfony
 * @subpackage cache
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfXCacheCache.class.php 4586 2007-07-12 20:41:34Z fabien $
 */
class sfXCacheCache extends sfCache
{
  protected $prefix = '';

  /**
   * Initializes this sfCache instance.
   *
   * Available parameters:
   *
   * * see sfCache for default parameters available for all drivers
   *
   * @see sfCache
   */
  public function initialize($parameters = array())
  {
    parent::initialize($parameters);

    if (!function_exists('xcache_set'))
    {
      throw new sfInitializationException('You must have XCache installed and enabled to use sfXCacheCache class.');
    }

    $this->prefix = md5(sfConfig::get('sf_app_dir')).self::SEPARATOR;
  }

 /**
  * @see sfCache
  */
  public function get($key, $default = null)
  {
    return xcache_isset($this->prefix.$key) ? substr(xcache_get($this->prefix.$key), 12) : $default;
  }

  /**
   * @see sfCache
   */
  public function has($key)
  {
    return xcache_isset($this->prefix.$key);
  }

  /**
   * @see sfCache
   */
  public function set($key, $data, $lifetime = null)
  {
    return xcache_set($this->prefix.$key, str_pad(time() + $lifetime, 12, 0, STR_PAD_LEFT).$data, $this->getLifetime($lifetime));
  }

  /**
   * @see sfCache
   */
  public function remove($key)
  {
    return xcache_unset($this->prefix.$key);
  }

  /**
   * @see sfCache
   */
  public function clean($mode = sfCache::ALL)
  {
    if (!sfCache::ALL)
    {
      return true;
    }

    $this->checkAuth();

    for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++)
    {
      if (!xcache_clear_cache(XC_TYPE_VAR, $i))
      {
        return false;
      }
    }

    return true;
  }

  /**
   * @see sfCache
   */
  public function getLastModified($key)
  {
    if (!xcache_isset($this->prefix.$key))
    {
      return 0;
    }

    if ($info = $this->getCacheInfo($key))
    {
      return $info['ctime'];
    }

    return 0;
  }

  /**
   * @see sfCache
   */
  public function getTimeout($key)
  {
    return xcache_isset($this->prefix.$key) ? intval(substr(xcache_get($this->prefix.$key), 0, 12)) : 0;
  }

  /**
   * @see sfCache
   */
  public function removePattern($pattern)
  {
    $this->checkAuth();

    $regexp = self::patternToRegexp($this->prefix.$pattern);

    for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++)
    {
      $infos = xcache_list(XC_TYPE_VAR, $i);
      if (!is_array($infos['cache_list']))
      {
        return;
      }

      foreach ($infos['cache_list'] as $info)
      {
        if (preg_match($regexp, $info['name']))
        {
          xcache_unset($info['name']);
        }
      }
    }
  }

  protected function getCacheInfo($key)
  {
    $this->checkAuth();

    for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++)
    {
      $infos = xcache_list(XC_TYPE_VAR, $i);

      if (is_array($infos['cache_list']))
      {
        foreach ($infos['cache_list'] as $info)
        {
          if ($this->prefix.$key == $info['name'])
          {
            return $info;
          }
        }
      }
    }

    return null;
  }

  protected function checkAuth()
  {
    if (ini_get('xcache.admin.enable_auth'))
    {
      throw new sfConfigurationException('To use all features of the "sfXCacheCache" class, you must set "xcache.admin.enable_auth" to "Off" in your php.ini.');
    }
  }
}
