<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfViewConfigHandler allows you to configure views.
 *
 * @package    symfony
 * @subpackage config
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfViewConfigHandler extends sfYamlConfigHandler
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
   * @throws <b>sfInitializationException</b> If a view.yml key check fails.
   */
  public function execute($configFiles)
  {
    // set our required categories list and initialize our handler
    $categories = array('required_categories' => array());
    $this->initialize($categories);

    // parse the yaml
    $myConfig = $this->parseYamls($configFiles);

    $myConfig['all'] = sfToolkit::arrayDeepMerge(
      isset($myConfig['default']) && is_array($myConfig['default']) ? $myConfig['default'] : array(),
      isset($myConfig['all']) && is_array($myConfig['all']) ? $myConfig['all'] : array()
    );

    // merge javascripts and stylesheets
    $myConfig['all']['stylesheets'] = array_merge(isset($myConfig['default']['stylesheets']) ? $myConfig['default']['stylesheets'] : array(), isset($myConfig['all']['stylesheets']) ? $myConfig['all']['stylesheets'] : array());
    $myConfig['all']['javascripts'] = array_merge(isset($myConfig['default']['javascripts']) ? $myConfig['default']['javascripts'] : array(), isset($myConfig['all']['javascripts']) ? $myConfig['all']['javascripts'] : array());

    unset($myConfig['default']);

    $this->yamlConfig = $myConfig;

    // init our data array
    $data = array();

    $data[] = "\$sf_safe_slot = sfConfig::get('sf_safe_slot');\n";

    // iterate through all view names
    $first = true;
    foreach ($this->yamlConfig as $viewName => $values)
    {
      if ($viewName == 'all')
      {
        continue;
      }

      $data[] = ($first ? '' : 'else ')."if (\$this->viewName == '$viewName')\n".
                "{\n";

      // template name
      $templateName = $this->getConfigValue('template', $viewName);
      if ($templateName)
      {
        $data[] = "  \$templateName = \$action->getTemplate() ? \$action->getTemplate() : '$templateName';\n";
      }
      else
      {
        $data[] = "  \$templateName = \$action->getTemplate() ? \$action->getTemplate() : \$this->getContext()->getActionName();\n";
      }

      $data[] = "  if (!\$sf_safe_slot || (\$sf_safe_slot && !\$actionStackEntry->isSlot()))\n";
      $data[] = "  {\n";

      $data[] = $this->addLayout($viewName);
      $data[] = $this->addSlots($viewName);
      $data[] = $this->addComponentSlots($viewName);
      $data[] = $this->addHtmlHead($viewName);
      $data[] = $this->addEscaping($viewName);

      $data[] = "  }\n";

      $data[] = $this->addHtmlAsset($viewName);

      $data[] = "}\n";

      $first = false;
    }

    // general view configuration
    $data[] = ($first ? '' : "else\n{")."\n";
    $templateName = $this->getConfigValue('template', 'all');
    if ($templateName)
    {
      $data[] = "  \$templateName = \$action->getTemplate() ? \$action->getTemplate() : '$templateName';\n";
    }
    else
    {
      $data[] = "  \$templateName = \$action->getTemplate() ? \$action->getTemplate() : \$this->getContext()->getActionName();\n";
    }

    $data[] = "  if (!\$sf_safe_slot || (\$sf_safe_slot && !\$actionStackEntry->isSlot()))\n";
    $data[] = "  {\n";

    $data[] = $this->addLayout();
    $data[] = $this->addSlots();
    $data[] = $this->addComponentSlots();
    $data[] = $this->addHtmlHead();
    $data[] = $this->addEscaping();

    $data[] = "  }\n";

    $data[] = $this->addHtmlAsset();
    $data[] = ($first ? '' : "}")."\n";

    // compile data
    $retval = sprintf("<?php\n".
                      "// auto-generated by sfViewConfigHandler\n".
                      "// date: %s\n%s\n?>",
                      date('Y/m/d H:i:s'), implode('', $data));

    return $retval;
  }

  private function addComponentSlots($viewName = '')
  {
    $data = '';

    $components = $this->mergeConfigValue('components', $viewName);
    foreach ($components as $name => $component)
    {
      if (!is_array($component) || count($component) < 1)
      {
        $component = array(null, null);
      }

      $data .= "    \$this->setComponentSlot('$name', '{$component[0]}', '{$component[1]}');\n";
      $data .= "    if (sfConfig::get('sf_logging_active')) \$context->getLogger()->info('{sfViewConfig} set component \"$name\" ({$component[0]}/{$component[1]})');\n";
    }

    return $data;
  }

  private function addSlots($viewName = '')
  {
    $data = '';

    $slots = null;

    $use_default_slots = $this->getConfigValue('use_default_slots', $viewName);

    if ($use_default_slots)
    {
      $slots = $this->mergeConfigValue('slots', $viewName);
    }
    else
    {
      if ($viewName == '')
      {
        // is category all: turning off default_slots or was it just not set?
        if (isset($this->yamlConfig['all']['use_default_slots']))
        {
          // only use slots defined within all
          if (isset($this->yamlConfig['all']['slots']))
          {
            $slots = $this->yamlConfig['all']['slots'];
          }
        }
        else
        {
          // all: didn't define anything, default slots are on by default
          $slots = $this->getConfigValue('slots', $viewName);
        }
      }
      else
      {
        $slots = isset($this->yamlConfig[$viewName]['slots']) ? $this->yamlConfig[$viewName]['slots'] : null;
      }
    }

    if (is_array($slots))
    {
      foreach ($slots as $name => $slot)
      {
        if (count($slot) > 1)
        {
          $data .= "    \$this->setSlot('$name', '{$slot[0]}', '{$slot[1]}');\n";
          $data .= "    if (sfConfig::get('sf_logging_active')) \$context->getLogger()->info('{sfViewConfig} set slot \"$name\" ({$slot[0]}/{$slot[1]})');\n";
        }
      }
    }

    return $data;
  }

  private function addLayout($viewName = '')
  {
    $data = '';

    $has_layout = $this->getConfigValue('has_layout', $viewName);
    if ($has_layout)
    {
      $layout = $this->getconfigValue('layout', $viewName);
      $data .= "    \$this->setDecoratorDirectory(sfConfig::get('sf_app_template_dir'));\n".
               "    \$this->setDecoratorTemplate('$layout.php');\n";
    }

    return $data;
  }

  private function addHtmlHead($viewName = '')
  {
    $data = array();

    foreach ($this->mergeConfigValue('http_metas', $viewName) as $httpequiv => $content)
    {
      $data[] = sprintf("    \$action->getResponse()->addHttpMeta('%s', '%s', false);", $httpequiv, $content);
    }

    foreach ($this->mergeConfigValue('metas', $viewName) as $name => $content)
    {
      $data[] = sprintf("    \$action->getResponse()->addMeta('%s', '%s', false);", $name, $content);
    }

    return implode("\n", $data)."\n";
  }

  private function addHtmlAsset($viewName = '')
  {
    $data = array();

    $stylesheets = $this->mergeConfigValue('stylesheets', $viewName);
    if (is_array($stylesheets))
    {
      // remove javascripts marked with a beginning '-'
      $delete = array();
      foreach ($stylesheets as $stylesheet)
      {
        $key = is_array($stylesheet) ? key($stylesheet) : $stylesheet;
        if (substr($key, 0, 1) == '-')
        {
          $delete[] = $key;
          $delete[] = substr($key, 1);
        }
      }
      $stylesheets = array_diff($stylesheets, $delete);

      foreach ($stylesheets as $css)
      {
        if (is_array($css))
        {
          $key = key($css);
          $options = $css[$key];
          $data[] = sprintf("  \$action->getResponse()->addStylesheet('%s', '', %s);", $key, var_export($options, true));
        }
        else
        {
          $data[] = sprintf("  \$action->getResponse()->addStylesheet('%s');", $css);
        }
      }
    }

    $javascripts = $this->mergeConfigValue('javascripts', $viewName);
    if (is_array($javascripts))
    {
      // remove javascripts marked with a beginning '-'
      $delete = array();
      foreach ($javascripts as $javascript)
      {
        if (substr($javascript, 0, 1) == '-')
        {
          $delete[] = $javascript;
          $delete[] = substr($javascript, 1);
        }
      }
      $javascripts = array_diff($javascripts, $delete);

      foreach ($javascripts as $js)
      {
        $data[] = sprintf("  \$action->getResponse()->addJavascript('%s');", $js);
      }
    }

    return implode("\n", $data)."\n";
  }

  private function addEscaping($viewName = '')
  {
    $data = array();

    $escaping = $this->getConfigValue('escaping', $viewName);

    if(isset($escaping['strategy']))
    {
      $data[] = sprintf("  \$this->setEscaping(%s);", var_export($escaping['strategy'], true));
    }

    if(isset($escaping['method']))
    {
      $data[] = sprintf("  \$this->setEscapingMethod(%s);", var_export($escaping['method'], true));
    }

    return implode("\n", $data)."\n";
  }
}

?>
