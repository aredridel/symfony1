<?php

/*
 * This file is part of the symfony package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * CacheHelper.
 *
 * @package    symfony
 * @subpackage helper
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */

/* Usage

<?php if (!cache('name')): ?>

... HTML ...

  <?php cache_save() ?>
<?php endif ?>

*/
function cache($suffix, $lifeTime = null)
{
  $context = sfContext::getInstance();
  $config  = $context->getConfig();

  if ($lifeTime === null)
  {
    $lifeTime = $config->get('sf_default_cache_lifetime');
  }

  if (!$config->get('sf_cache'))
  {
    return null;
  }

  $request = $context->getRequest();
  $cache   = $context->getViewCacheManager();

  if ($request->getAttribute('cache_started') !== null)
  {
    throw new sfCacheException('Cache already started');
  }

  $data = $cache->start($suffix, $lifeTime);

  if ($data === null)
  {
    $request->setAttribute('started', 1, 'symfony/action/sfAction/cache');
    $request->setAttribute('current_suffix', $suffix, 'symfony/action/sfAction/cache');

    return 0;
  }
  else
  {
    echo $data;

    return 1;
  }
}

function cache_save()
{
  $context = sfContext::getInstance();
  $config  = $context->getConfig();

  if (!$config->get('sf_cache'))
  {
    return null;
  }

  $request = $context->getRequest();

  if ($request->getAttribute('started', null, 'symfony/action/sfAction/cache') === null)
  {
    throw new sfCacheException('Cache not started');
  }

  $suffix = $request->getAttribute('current_suffix', '', 'symfony/action/sfAction/cache');

  $data = $context->getViewCacheManager()->stop($suffix);

  $request->setAttribute('started', null, 'symfony/action/sfAction/cache');
  $request->setAttribute('current_suffix', null, 'symfony/action/sfAction/cache');

  echo $data;
}

?>