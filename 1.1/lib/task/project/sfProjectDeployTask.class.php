<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Deploys a project to another server.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfProjectDeployTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('server', sfCommandArgument::REQUIRED, 'The server name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('go', null, sfCommandOption::PARAMETER_NONE, 'Do the deployment'),
      new sfCommandOption('rsync-dir', null, sfCommandOption::PARAMETER_REQUIRED, 'The directory where to look for rsync*.txt files', 'config'),
      new sfCommandOption('rsync-options', null, sfCommandOption::PARAMETER_OPTIONAL, 'To options to pass to the rsync executable', 'azC'),
    ));

    $this->aliases = array('sync');
    $this->namespace = 'project';
    $this->name = 'deploy';
    $this->briefDescription = 'Deploys a project to another server';

    $this->detailedDescription = <<<EOF
The [project:deploy|INFO] task deploys a project on a server:

  [./symfony project:deploy production|INFO]

The server must be configured in [config/properties.ini|COMMENT]:

  [[production]
    host=www.example.com
    port=22
    user=fabien
    dir=/var/www/sfblog/
    type=rsync|INFO]

To automate the deployment, the task uses rsync over SSH.
You must configure SSH access with a key or configure the password
in [config/properties.ini|COMMENT].

By default, the task is in dry-mode. To do a real deployment, you
must pass the [--go|COMMENT] option:

  [./symfony project:deploy --go production|INFO]

Files and directories configured in [config/rsync_exclude.txt|COMMENT] are
not deployed:

  [.svn
  /web/uploads/*
  /cache/*
  /log/*|INFO]

You can also create a [rsync.txt|COMMENT] and [rsync_include.txt|COMMENT] files.

If you need to customize the [rsync*.txt|COMMENT] files based on the server,
you can pass a [rsync-dir|COMMENT] option:

  [./symfony project:deploy --go --rsync-dir=config/production production|INFO]

Last, you can specify the options passed to the rsync executable, using the 
[rsync-options|INFO] option (defaults are [-azC|INFO]):

  [./symfony project:deploy --go --rsync-options=avz|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $env = $arguments['server'];

    $sf_config_dir_name = sfConfig::get('sf_config_dir_name');

    $ini = sfConfig::get('sf_config_dir').DIRECTORY_SEPARATOR.'properties.ini';
    if (!file_exists($ini))
    {
      throw new sfCommandException(sprintf('You must create a config%sproperties.ini file', DIRECTORY_SEPARATOR));
    }

    $properties = parse_ini_file($ini, true);

    if (!isset($properties[$env]))
    {
      throw new sfCommandException(sprintf('You must define the configuration for server "%s" in config%sproperties.ini', $env, DIRECTORY_SEPARATOR));
    }

    $properties = $properties[$env];

    if (!isset($properties['host']))
    {
      throw new sfCommandException('You must define a "host" entry.');
    }

    if (!isset($properties['dir']))
    {
      throw new sfCommandException('You must define a "dir" entry.');
    }

    $host = $properties['host'];
    $dir  = $properties['dir'];
    $user = isset($properties['user']) ? $properties['user'].'@' : '';

    if (substr($dir, -1) != '/')
    {
      $dir .= '/';
    }

    $ssh = 'ssh';

    if (isset($properties['port']))
    {
      $port = $properties['port'];
      $ssh = '"ssh -p'.$port.'"';
    }

    if (isset($properties['parameters']))
    {
      $parameters = $properties['parameters'];
    }
    else
    {
      $parameters = sprintf('-%s --force --delete', $options['rsync-options']);
      if (file_exists($options['rsync-dir'].'/rsync_exclude.txt'))
      {
        $parameters .= sprintf(' --exclude-from=%s/rsync_exclude.txt', $options['rsync-dir']);
      }

      if (file_exists($options['rsync-dir'].'/rsync_include.txt'))
      {
        $parameters .= ' --include-from='.$sf_config_dir_name.DIRECTORY_SEPARATOR.'rsync_include.txt';
      }

      if (file_exists($options['rsync-dir'].'/rsync.txt'))
      {
        $parameters .= sprintf(' --files-from=%s/rsync.txt', $options['rsync-dir']);
      }
    }

    $dryRun = $options['go'] ? '' : '--dry-run';

    $this->log($this->getFilesystem()->sh("rsync --progress $dryRun $parameters -e $ssh ./ $user$host:$dir"));
  }
}
