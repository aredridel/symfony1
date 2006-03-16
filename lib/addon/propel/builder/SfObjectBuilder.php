<?php

require_once 'propel/engine/builder/om/php5/PHP5ComplexObjectBuilder.php';

class sfObjectBuilder extends PHP5ComplexObjectBuilder
{
  protected function addIncludes(&$script)
  {
    parent::addIncludes($script);

    // include the i18n classes if needed
    if ($this->getTable()->getAttribute('isI18N'))
    {
      $script .= '
require_once \''.$this->getFilePath($this->getStubObjectBuilder()->getPackage().'.'.$this->getStubObjectBuilder()->getClassName().'I18nPeer').'\';
require_once \''.$this->getFilePath($this->getStubObjectBuilder()->getPackage().'.'.$this->getStubObjectBuilder()->getClassName().'I18n').'\';
';
    }
  }

  protected function addClassBody(&$script)
  {
    parent::addClassBody($script);

    if ($this->getTable()->getAttribute('isI18N'))
    {
      if (count($this->getTable()->getPrimaryKey()) > 1)
      {
        throw new Exception('i18n support only works with a single primary key');
      }

      $this->addCultureAccessorMethod($script);
      $this->addCultureMutatorMethod($script);

      $this->addI18nMethods($script);
    }
  }

  protected function addAttributes(&$script)
  {
    parent::addAttributes($script);

    if ($this->getTable()->getAttribute('isI18N'))
    {
      $script .= '
  /**
   * The value for the culture field.
   * @var string
   */
  protected $culture;
';
    }
  }

  protected function addCultureAccessorMethod(&$script)
  {
    $script .= '
  public function getCulture()
  {
    return $this->culture;
  }
';
  }

  protected function addCultureMutatorMethod(&$script)
  {
    $script .= '
  public function setCulture($culture)
  {
    $this->culture = $culture;
  }
';
  }

  protected function addI18nMethods(&$script)
  {
    $table = $this->getTable();
    $pks = $table->getPrimaryKey();
    $pk = $pks[0]->getPhpName();

    foreach ($table->getReferrers() as $fk)
    {
      $tblFK = $fk->getTable();
      if ($tblFK->getName() == $table->getAttribute('i18nTable'))
      {
        $className = $tblFK->getPhpName();
        $culture = '';
        $culturePeername = '';
        foreach ($tblFK->getColumns() as $col)
        {
          if (("true" === strtolower($col->getAttribute('isCulture'))))
          {
            $culture = $col->getPhpName();
            $culturePeername = PeerBuilder::getColumnName($col, $className);
          }
        }

        foreach ($tblFK->getColumns() as $col)
        {
          if ($col->isPrimaryKey())
          {
            continue;
          }

          $script .= '
  public function get'.$col->getPhpName().'()
  {
    $obj = $this->getCurrent'.$className.'();

    return ($obj ? $obj->get'.$col->getPhpName().'() : null);
  }

  public function set'.$col->getPhpName().'($value)
  {
    $this->getCurrent'.$className.'()->set'.$col->getPhpName().'($value);
  }
';
        }

$script .= '
  protected $currentI18n = array();

  public function getCurrent'.$className.'()
  {
    if (!isset($this->currentI18n[$this->culture]))
    {
      $obj = '.$className.'Peer::retrieveByPK($this->get'.$pk.'(), $this->culture);
      if ($obj)
      {
        $this->set'.$className.'ForCulture($obj, $this->culture);
      }
      else
      {
        $this->set'.$className.'ForCulture(new '.$className.'(), $this->culture);
        $this->currentI18n[$this->culture]->set'.$culture.'($this->culture);
      }
    }

    return $this->currentI18n[$this->culture];
  }

  public function set'.$className.'ForCulture($object, $culture)
  {
    $this->currentI18n[$culture] = $object;
    $this->add'.$className.'($object);
  }
';
      }
    }
  }

  protected function addSave(&$script)
  {
    $tmp = '';
    parent::addSave($tmp);

    $dateScript = '';

    $updated = false;
    $created = false;
    foreach ($this->getTable()->getColumns() as $col)
    {
      $clo = strtolower($col->getName());

      if (!$updated && $clo == 'updated_at')
      {
        // add automatic UpdatedAt updating
        $updated = true;
        $dateScript .= "        \$this->setUpdatedAt(time());\n";
      }
      elseif (!$updated && $clo == 'updated_on')
      {
        // add automatic UpdatedOn updating
        $updated = true;
        $dateScript .= "        \$this->setUpdatedOn(time());\n";
      }
      elseif (!$created && $clo == 'created_at')
      {
        // add automatic CreatedAt updating
        $created = true;
        $dateScript .= "
    if (\$this->isNew() && !\$this->getCreatedAt())
    {
      \$this->setCreatedAt(time());
    }
";
      }
      elseif (!$created && $clo == 'created_on')
      {
        // add automatic CreatedOn updating
        $created = true;
        $dateScript .= "
    if (\$this->isNew() && !\$this->getCreatedOn())
    {
      \$this->setCreatedOn(time());
    }
";
      }
    }

    $tmp = preg_replace('/{/', '{'.$dateScript, $tmp, 1);
    $script .= $tmp;
  }
}

?>