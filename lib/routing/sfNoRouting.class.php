<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfNoRouting class is a very simple routing class that uses GET parameters.
 *
 * @package    symfony
 * @subpackage controller
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfNoRouting.class.php 4435 2007-06-27 12:41:22Z fabien $
 */
class sfNoRouting extends sfRouting
{
  /**
   * Gets the internal URI for the current request.
   *
   * @param boolean Whether to give an internal URI with the route name (@route)
   *                or with the module/action pair
   *
   * @return string The current internal URI
   */
  public function getCurrentInternalUri($with_route_name = false)
  {
    $request = $this->context->getRequest();

    $parameters = $_GET;
    unset($parameters['module']);
    unset($parameters['action']);
    $parameters = count($parameters) ? '?'.http_build_query($parameters) : '';

    return sprintf('%s/%s%s', $request->getParameter('module', sfConfig::get('sf_default_module')), $request->getParameter('action', sfConfig::get('sf_default_action')), $parameters);
  }

  /**
   * Gets the current compiled route array.
   *
   * @return array The route array
   */
  public function getRoutes()
  {
    return array();
  }

  /**
   * Sets the compiled route array.
   *
   * @param array The route array
   *
   * @return array The route array
   */
  public function setRoutes($routes)
  {
    return array();
  }

  /**
   * Returns true if this instance has some routes.
   *
   * @return  boolean
   */
  public function hasRoutes()
  {
    return false;
  }

  /**
   * Clears all current routes.
   */
  public function clearRoutes()
  {
  }

 /**
  * Generates a valid URLs for parameters.
  *
  * @param  array  The parameter values
  * @param  string The divider between key/value pairs
  * @param  string The equal sign to use between key and value
  *
  * @return string The generated URL
  */
  public function generate($name, $params, $querydiv = '/', $divider = '/', $equals = '/')
  {
    $parameters = http_build_query($params);

    return '/'.($parameters ? '?'.$parameters : '');
  }

 /**
  * Parses a URL to find a matching route.
  *
  * Returns null if no route match the URL.
  *
  * @param  string URL to be parsed
  *
  * @return array  An array of parameters
  */
  public function parse($url)
  {
    $parameters = parse_url($url);
    if (isset($parameters['query']))
    {
      parse_str($parameters['query'], $parameters);
    }
    else
    {
      $parameters = array();
    }

    return array_merge(array('module' => sfConfig::get('sf_default_module'), 'action' => sfConfig::get('sf_default_action')), $parameters);
  }
}
