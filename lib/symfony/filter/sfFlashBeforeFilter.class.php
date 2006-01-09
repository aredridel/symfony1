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
 * @subpackage filter
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfFlashBeforeFilter extends sfFilter
{
  /**
   * Execute this filter.
   *
   * @param FilterChain A FilterChain instance.
   *
   * @return void
   */
  public function execute ($filterChain)
  {
    static $loaded;

    // execute this filter only once
    if (!isset($loaded))
    {
      // load the filter
      $loaded = true;

      // flag current flash to be removed after the execution filter
      $context = $this->getContext();
      $userAttributeHolder = $context->getUser()->getAttributeHolder();
      $names = $userAttributeHolder->getNames('symfony/flash');
      if ($names)
      {
        $context->getLogger()->info('{sfController} flag old flash messages ("'.implode('", "', $names).'")');
        foreach ($names as $name)
        {
          $userAttributeHolder->set($name, true, 'symfony/flash/remove');
        }
      }
    }

    // execute next filter
    $filterChain->execute();
  }
}

?>