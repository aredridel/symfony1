<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Propel admin generator.
 *
 * This class executes all the logic for the current request.
 *
 * @package    symfony
 * @subpackage generator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelAdminGenerator extends sfPropelCrudGenerator
{
  private
    #$params = array(),
    $fields = array();

  public function initialize($generatorManager)
  {
    parent::initialize($generatorManager);

    $this->setGeneratorClass('sfPropelAdmin');
  }

  public function getHelpAsIcon($column, $type = '')
  {
    $help = $this->getParameterValue($type.'.fields.'.$column->getName().'.help');
    if ($help)
    {
      return "[?php echo image_tag(sfConfig::get('sf_admin_web_dir').'/images/help.png', array('align' => 'absmiddle', 'alt' => __('".$this->escapeString($help)."'), 'title' => __('".$this->escapeString($help)."'))) ?]";
    }

    return '';
  }

  public function getHelp($column, $type = '')
  {
    $help = $this->getParameterValue($type.'.fields.'.$column->getName().'.help');
    if ($help)
    {
      return "<div class=\"sf_admin_edit_help\">[?php echo __('".$this->escapeString($help)."') ?]</div>";
    }

    return '';
  }

  public function getButtonToAction($actionName, $params, $pk_link = false)
  {
    $options  = isset($params['params']) ? sfToolkit::stringToArray($params['params']) : array();
    $method   = 'button_to';
    $li_class = '';
    $only_for = isset($params['only_for']) ? $params['only_for'] : null;

    // default values
    if ($actionName[0] == '_')
    {
      $actionName     = substr($actionName, 1);
      $default_name   = strtr($actionName, '_', ' ');
      $default_icon   = sfConfig::get('sf_admin_web_dir').'/images/'.$actionName.'_icon.png';
      $default_action = $actionName;
      $default_class  = 'sf_admin_action_'.$actionName;

      if ($actionName == 'save' || $actionName == 'save_and_add')
      {
        $method = 'submit_tag';
        $options['name'] = $actionName;
      }

      if ($actionName == 'delete')
      {
        $options['post'] = true;
        if (!isset($options['confirm']))
        {
          $options['confirm'] = 'Are you sure?';
        }

        $li_class = 'float-left';

        $only_for = 'edit';
      }
    }
    else
    {
      $default_name   = strtr($actionName, '_', ' ');
      $default_icon   = sfConfig::get('sf_admin_web_dir').'/images/default_icon.png';
      $default_action = 'List'.sfInflector::camelize($actionName);
      $default_class  = '';
    }

    $name   = isset($params['name']) ? $params['name'] : $default_name;
    $icon   = isset($params['icon']) ? $params['icon'] : $default_icon;
    $action = isset($params['action']) ? $params['action'] : $default_action;
    $url_params = $pk_link ? '?'.$this->getPrimaryKeyUrlParams() : '\'';

    if (!isset($options['class']) && $default_class)
    {
      $options['class'] = $default_class;
    }
    else
    {
      $options['style'] = 'background: #ffc url('.$icon.') no-repeat 3px 2px';
    }

    $li_class = $li_class ? ' class="'.$li_class.'"' : '';

    $html = '<li'.$li_class.'>';

    if ($only_for == 'edit')
    {
      $html .= '[?php if ('.$this->getPrimaryKeyIsSet().'): ?]'."\n";
    }
    else if ($only_for == 'create')
    {
      $html .= '[?php if (!'.$this->getPrimaryKeyIsSet().'): ?]'."\n";
    }
    else if ($only_for !== null)
    {
      throw new sfConfigurationException(sprintf('The "only_for" parameter can only takes "create" or "edit" as argument ("%s")', $only_for));
    }

    if ($method == 'submit_tag')
    {
      $html .= '[?php echo submit_tag(__(\''.$name.'\'), '.var_export($options, true).') ?]';
    }
    else
    {
      $phpOptions = var_export($options, true);

      // little hack
      $phpOptions = preg_replace("/'confirm' => '(.+?)(?<!\\\)'/", '\'confirm\' => __(\'$1\')', $phpOptions);

      $html .= '[?php echo button_to(__(\''.$name.'\'), \''.$this->getModuleName().'/'.$action.$url_params.', '.$phpOptions.') ?]';
    }

    if ($only_for !== null)
    {
      $html .= '[?php endif; ?]'."\n";
    }

    $html .= '</li>'."\n";

    return $html;
  }

  public function getLinkToAction($actionName, $params, $pk_link = false)
  {
    $options = isset($params['params']) ? sfToolkit::stringToArray($params['params']) : array();

    // default values
    if ($actionName[0] == '_')
    {
      $actionName = substr($actionName, 1);
      $name       = $actionName;
      $icon       = sfConfig::get('sf_admin_web_dir').'/images/'.$actionName.'_icon.png';
      $action     = $actionName;

      if ($actionName == 'delete')
      {
        $options['post'] = true;
        if (!isset($options['confirm']))
        {
          $options['confirm'] = 'Are you sure?';
        }
      }
    }
    else
    {
      $name   = isset($params['name']) ? $params['name'] : $actionName;
      $icon   = isset($params['icon']) ? $params['icon'] : sfConfig::get('sf_admin_web_dir').'/images/default_icon.png';
      $action = isset($params['action']) ? $params['action'] : 'List'.sfInflector::camelize($actionName);
    }

    $url_params = $pk_link ? '?'.$this->getPrimaryKeyUrlParams() : '\'';

    $phpOptions = var_export($options, true);

    // little hack
    $phpOptions = preg_replace("/'confirm' => '(.+?)(?<!\\\)'/", '\'confirm\' => __(\'$1\')', $phpOptions);

    return '<li>[?php echo link_to(image_tag(\''.$icon.'\', array(\'alt\' => __(\''.$name.'\'), \'title\' => __(\''.$name.'\'))), \''.$this->getModuleName().'/'.$action.$url_params.($options ? ', '.$phpOptions : '').') ?]</li>'."\n";
  }

  public function getColumnEditTag($column, $params = array())
  {
    // user defined parameters
    $user_params = $this->getParameterValue('edit.fields.'.$column->getName().'.params');
    $user_params = is_array($user_params) ? $user_params : sfToolkit::stringToArray($user_params);
    $params      = $user_params ? array_merge($params, $user_params) : $params;

    if ($column->isComponent())
    {
      return "get_component('".$this->getModuleName()."', '".$column->getName()."', array('type' => 'edit', '{$this->getSingularName()}' => \${$this->getSingularName()}))";
    }
    else if ($column->isPartial())
    {
      return "get_partial('".$column->getName()."', array('type' => 'edit', '{$this->getSingularName()}' => \${$this->getSingularName()}))";
    }

    // default control name
    $params = array_merge(array('control_name' => $this->getSingularName().'['.$column->getName().']'), $params);

    // default parameter values
    $type = $column->getCreoleType();
    if ($type == CreoleTypes::DATE)
    {
      $params = array_merge(array('rich' => true, 'calendar_button_img' => sfConfig::get('sf_admin_web_dir').'/images/date.png'), $params);
    }
    else if ($type == CreoleTypes::TIMESTAMP)
    {
      $params = array_merge(array('rich' => true, 'withtime' => true, 'calendar_button_img' => sfConfig::get('sf_admin_web_dir').'/images/date.png'), $params);
    }

    // user sets a specific tag to use
    if ($inputType = $this->getParameterValue('edit.fields.'.$column->getName().'.type'))
    {
      if ($inputType == 'plain')
      {
        return $this->getColumnListTag($column, $params);
      }
      else
      {
        $params = $this->getObjectTagParams($params);
        
        return $this->getPHPObjectHelper($inputType, $column, $params);
      }
    }

    // guess the best tag to use with column type
    return parent::getColumnEditTag($column, $params);
  }

  public function getColumnCategories($paramName)
  {
    if (is_array($this->getParameterValue($paramName)))
    {
      $fields = $this->getParameterValue($paramName);

      // do we have categories?
      if (!isset($fields[0]))
      {
        return array_keys($fields);
      }

    }

    return array('NONE');
  }

  public function addCredentialCondition($content, $params = array())
  {
    if (isset($params['credentials']))
    {
      $credentials = str_replace("\n", ' ', var_export($params['credentials'], true));

      return <<<EOF
[?php if (\$sf_user->hasCredential($credentials)): ?]
$content
[?php endif; ?]
EOF;
    }
    else
    {
      return $content;
    }
  }

  /**
   * returns an array of sfAdminColumn objects
   * from the $paramName list or the list of all columns (in table) if it does not exist
   */
  public function getColumns($paramName, $category = 'NONE')
  {
    $phpNames = array();

    // user has set a personnalized list of fields?
    $fields = $this->getParameterValue($paramName);
    if (is_array($fields))
    {
      // categories?
      if (isset($fields[0]))
      {
        // simulate a default one
        $fields = array('NONE' => $fields);
      }

      if (!$fields)
      {
        return array();
      }

      foreach ($fields[$category] as $field)
      {
        list($field, $flag) = $this->splitFlag($field);
        
        $phpNames[] = $this->getAdminColumnForField($field, $flag);
      }
    }
    else // FIXME: this is not doctrine compatible
    {
      // no, just return the full list of columns in table
      foreach ($this->getTableMap()->getColumns() as $column)
      {
        $phpNames[] = new sfAdminColumn($column->getPhpName(), $column);
      }
    }

    return $phpNames;
  }
  
  public function getAdminColumnForField($field, $flag = null)
  {
    $phpName = sfInflector::camelize($field);
    return new sfAdminColumn($phpName, $this->getColumnForPhpName($phpName), $flag);
  }
  
  // returns a column phpName or null if none was found
  public function getColumnForPhpName($phpName)
  {
    // search the matching column for this column name

    foreach ($this->getTableMap()->getColumns() as $column)
    {
      if ($column->getPhpName() == $phpName)
      {
        $found = true;
        return $column;
        break;
      }
    }
    
    // not a "real" column, so we will simulate one
    return null;
  }

  public function splitFlag($text)
  {
    $flag = '';
    if (in_array($text[0], array('=', '-', '+', '_', '~')))
    {
      $flag = $text[0];
      $text = substr($text, 1);
    }

    return array($text, $flag);
  }

  // $name example: list.display
  // special default behaviour for fields. keys
  public function getParameterValue($key, $default = null)
  {
    if (preg_match('/^([^\.]+)\.fields\.(.+)$/', $key, $matches))
    {
      return $this->getFieldParameterValue($matches[2], $matches[1], $default);
    }
    else
    {
      return $this->getValueFromKey($key, $default);
    }
  }

  private function getFieldParameterValue($key, $type = '', $default = null)
  {
    $retval = $this->getValueFromKey($type.'.fields.'.$key, $default);
    if ($retval !== null)
    {
      return $retval;
    }

    $retval = $this->getValueFromKey('fields.'.$key, $default);
    if ($retval !== null)
    {
      return $retval;
    }

    if (preg_match('/\.name$/', $key))
    {
      // default field.name
      return sfInflector::humanize(($pos = strpos($key, '.')) ? substr($key, 0, $pos) : $key);
    }
    else
    {
      return null;
    }
  }

  private function getValueFromKey($key, $default = null)
  {
    $ref   =& $this->params;
    $parts =  explode('.', $key);
    $count =  count($parts);
    for ($i = 0; $i < $count; $i++)
    {
      $partKey = $parts[$i];
      if (!isset($ref[$partKey]))
      {
        return $default;
      }

      if ($count == $i + 1)
      {
        return $ref[$partKey];
      }
      else
      {
        $ref =& $ref[$partKey];
      }
    }

    return $default;
  }

  public function getI18NString($key, $default = null)
  {
    $value = $this->escapeString($this->getParameterValue($key, $default));

    // find %%xx%% strings
    preg_match_all('/%%([^%]+)%%/', $value, $matches, PREG_PATTERN_ORDER);
    $this->params['tmp']['display'] = array();
    foreach ($matches[1] as $name)
    {
      $this->params['tmp']['display'][] = $name;
    }

    $vars = array();
    foreach ($this->getColumns('tmp.display') as $column)
    {
      if ($column->isLink())
      {
        $vars[] = '\'%%'.$column->getName().'%%\' => link_to('.$this->getColumnListTag($column).', \''.$this->getModuleName().'/edit?'.$this->getPrimaryKeyUrlParams().')';
      }
      elseif ($column->isPartial())
      {
        $vars[] = '\'%%_'.$column->getName().'%%\' => '.$this->getColumnListTag($column);
      }
      else if ($column->isComponent())
      {
        $vars[] = '\'%%_'.$column->getName().'%%\' => '.$this->getColumnListTag($column);
      }
      else
      {
        $vars[] = '\'%%'.$column->getName().'%%\' => '.$this->getColumnListTag($column);
      }
    }

    // strip all = signs
    $value = preg_replace('/%%=([^%]+)%%/', '%%$1%%', $value);

    return '[?php echo __(\''.$value.'\', '."\n".'array('.implode(",\n", $vars).')) ?]';
  }

  public function replaceConstants($value)
  {
    // find %%xx%% strings
    preg_match_all('/%%([^%]+)%%/', $value, $matches, PREG_PATTERN_ORDER);
    $this->params['tmp']['display'] = array();
    foreach ($matches[1] as $name)
    {
      $this->params['tmp']['display'][] = $name;
    }

    foreach ($this->getColumns('tmp.display') as $column)
    {
      $value = str_replace('%%'.$column->getName().'%%', '{$this->'.$this->getSingularName().'->get'.$column->getPhpName().'()}', $value);
    }

    return $value;
  }

  public function getColumnListTag($column, $params = array())
  {
    $user_params = $this->getParameterValue('list.fields.'.$column->getName().'.params');
    $user_params = is_array($user_params) ? $user_params : sfToolkit::stringToArray($user_params);
    $params      = $user_params ? array_merge($params, $user_params) : $params;

    $type = $column->getCreoleType();
    
    $columnGetter = $this->getColumnGetter($column, true);

    if ($column->isComponent())
    {
      return "get_component('".$this->getModuleName()."', '".$column->getName()."', array('type' => 'list', '{$this->getSingularName()}' => \${$this->getSingularName()}))";
    }
    else if ($column->isPartial())
    {
      return "get_partial('".$column->getName()."', array('type' => 'list', '{$this->getSingularName()}' => \${$this->getSingularName()}))";
    }
    else if ($type == CreoleTypes::DATE || $type == CreoleTypes::TIMESTAMP)
    {
      $format = isset($params['date_format']) ? $params['date_format'] : 'f';
      return "($columnGetter !== null && $columnGetter !== '') ? format_date($columnGetter, \"$format\") : ''";
    }
    elseif ($type == CreoleTypes::BOOLEAN)
    {
      return "$columnGetter ? image_tag(sfConfig::get('sf_admin_web_dir').'/images/tick.png') : '&nbsp;'";
    }
    else
    {
      return "$columnGetter";
    }
  }

  public function getColumnFilterTag($column, $params = array())
  {
    $user_params = $this->getParameterValue('list.fields.'.$column->getName().'.params');
    $user_params = is_array($user_params) ? $user_params : sfToolkit::stringToArray($user_params);
    $params      = $user_params ? array_merge($params, $user_params) : $params;

    if ($column->isComponent())
    {
      return "get_component('".$this->getModuleName()."', '".$column->getName()."', array('type' => 'list'))";
    }
    else if ($column->isPartial())
    {
      return "get_partial('".$column->getName()."', array('type' => 'filter', 'filters' => \$filters))";
    }

    $type = $column->getCreoleType();

    $default_value = "isset(\$filters['".$column->getName()."']) ? \$filters['".$column->getName()."'] : null";
    $name = '\'filters['.$column->getName().']\'';

    if ($column->isForeignKey())
    {
      $relatedTable = $this->getMap()->getDatabaseMap()->getTable($column->getRelatedTableName());
      $params = $this->getObjectTagParams($params, array('include_blank' => true));

      $options = "objects_for_select(".$relatedTable->getPhpName()."Peer::doSelect(new Criteria()), 'getPrimaryKey', '__toString', $default_value, $params)";

      return "select_tag($name, $options, $params)";
    }
    else if ($type == CreoleTypes::DATE)
    {
      // rich=false not yet implemented
      $params = $this->getObjectTagParams($params, array('rich' => true, 'calendar_button_img' => sfConfig::get('sf_admin_web_dir').'/images/date.png'));
      return "input_date_range_tag($name, $default_value, $params)";
    }
    else if ($type == CreoleTypes::TIMESTAMP)
    {
      // rich=false not yet implemented
      $params = $this->getObjectTagParams($params, array('rich' => true, 'withtime' => true, 'calendar_button_img' => sfConfig::get('sf_admin_web_dir').'/images/date.png'));
      return "input_date_range_tag($name, $default_value, $params)";
    }
    else if ($type == CreoleTypes::BOOLEAN)
    {
      $defaultIncludeCustom = '__("yes or no")';
      $params = $this->getObjectTagParams($params, array('include_custom' => $defaultIncludeCustom));

      // little hack
      $params = preg_replace("/'".preg_quote($defaultIncludeCustom)."'/", $defaultIncludeCustom, $params);

      $options = "options_for_select(array(1 => __('yes'), 0 => __('no')), $default_value, $params)";

      return "select_tag($name, $options, $params)";
    }
    else if ($type == CreoleTypes::CHAR || $type == CreoleTypes::VARCHAR)
    {
      $size = ($column->getSize() < 15 ? $column->getSize() : 15);
      $params = $this->getObjectTagParams($params, array('size' => $size));
      return "input_tag($name, $default_value, $params)";
    }
    else if ($type == CreoleTypes::INTEGER || $type == CreoleTypes::TINYINT || $type == CreoleTypes::SMALLINT || $type == CreoleTypes::BIGINT)
    {
      $params = $this->getObjectTagParams($params, array('size' => 7));
      return "input_tag($name, $default_value, $params)";
    }
    else if ($type == CreoleTypes::FLOAT || $type == CreoleTypes::DOUBLE || $type == CreoleTypes::DECIMAL || $type == CreoleTypes::NUMERIC || $type == CreoleTypes::REAL)
    {
      $params = $this->getObjectTagParams($params, array('size' => 7));
      return "input_tag($name, $default_value, $params)";
    }
    else if ($type == CreoleTypes::TEXT || $type == CreoleTypes::LONGVARCHAR)
    {
      $params = $this->getObjectTagParams($params, array('size' => '15x2'));
      return "textarea_tag($name, $default_value, $params)";
    }
    else
    {
      $params = $this->getObjectTagParams($params, array('disabled' => true));
      return "input_tag($name, $default_value, $params)";
    }
  }

  private function escapeString($string)
  {
    return preg_replace('/\'/', '\\\'', $string);
  }
}

/**
 * Propel admin generator column.
 *
 * @package    symfony
 * @subpackage generator
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfAdminColumn
{
  protected
    $phpName    = '',
    $column     = null,
    $flag       = '';

  public function __construct($phpName, $column = null, $flag = '')
  {
    $this->phpName = $phpName;
    $this->column  = $column;
    $this->flag    = $flag;
  }

  public function __call ($name, $arguments)
  {
    return $this->column ? $this->column->$name() : null;
  }

  public function isReal ()
  {
    return $this->column ? true : false;
  }

  public function getPhpName ()
  {
    return $this->phpName;
  }

  public function getName ()
  {
    return sfInflector::underscore($this->phpName);
  }

  public function isPartial ()
  {
    return (($this->flag == '_') ? true : false);
  }

  public function isComponent ()
  {
    return (($this->flag == '~') ? true : false);
  }

  public function isLink ()
  {
    return (($this->flag == '=' || $this->isPrimaryKey()) ? true : false);
  }
}
