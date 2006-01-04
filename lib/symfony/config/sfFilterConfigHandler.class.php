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
 * sfFilterConfigHandler allows you to register filters with the system.
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <skerr@mojavi.org>
 * @version    SVN: $Id$
 */
class sfFilterConfigHandler extends sfYamlConfigHandler
{
  /**
   * Execute this configuration handler.
   *
   * @param string An absolute filesystem path to a configuration file.
   *
   * @return string Data to be written to a cache file.
   *
   * @throws sfConfigurationException If a requested configuration file does not exist or is not readable.
   * @throws sfParseException If a requested configuration file is improperly formatted.
   */
  public function & execute ($configFile, $param = array())
  {
    // parse the yaml
    $config = $this->parseYaml($configFile);

    // init our data and includes arrays
    $data     = array();
    $includes = array();

    // let's do our fancy work
    foreach ($config as $category => &$keys)
    {
      if (!isset($keys['class']))
      {
        // missing class key
        $error = 'Configuration file "%s" specifies category "%s" with missing class key';
        $error = sprintf($error, $configFile, $category);

        throw new sfParseException($error);
      }

      $class =& $keys['class'];

      if (isset($keys['file']))
      {
        // we have a file to include
        $file =& $keys['file'];
        $file =  $this->replaceConstants($file);
        $file =  $this->replacePath($file);

        if (!is_readable($file))
        {
          // filter file doesn't exist
          $error = 'Configuration file "%s" specifies class "%s" with nonexistent or unreadable file "%s"';
          $error = sprintf($error, $configFile, $class, $file);

          throw new sfParseException($error);
        }

        // append our data
        $tmp        = "require_once('%s');";
        $includes[] = sprintf($tmp, $file);
      }

      // parse parameters
      $parameters = (isset($keys['param']) ? var_export($keys['param'], true) : 'null');

      // append new data
      $tmp = "\$filter = new %s();\n" .
             "\$filter->initialize(\$this->context, %s);\n" .
             "\$filters[] = \$filter;";

      $data[] = sprintf($tmp, $class, $parameters);
    }

    // compile data
    $retval = "<?php\n" .
            "// auth-generated by sfFilterConfigHandler\n" .
            "// date: %s\n%s\n%s\n%s\n%s\n?>";

    $retval = sprintf($retval, date('m/d/Y H:i:s'),
                      implode("\n", $includes), '$filters = array();',
                      implode("\n", $data),
                      '$list[$moduleName] =& $filters;');

    return $retval;
  }
}

?>