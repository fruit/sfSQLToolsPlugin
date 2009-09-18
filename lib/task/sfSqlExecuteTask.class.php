<?php

# useful when project is under Propel not to trigger fatal error
require_once sfConfig::get('sf_symfony_lib_dir') . DIRECTORY_SEPARATOR .
  'plugins' . DIRECTORY_SEPARATOR . 'sfDoctrinePlugin' . DIRECTORY_SEPARATOR .
  'lib' . DIRECTORY_SEPARATOR . 'task' . DIRECTORY_SEPARATOR .
  'sfDoctrineBaseTask.class.php';

/**
 * Executes all SQL files
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Ilya Sabelnikov <fruit.dev@gmail.com>
 */
class sfSqlExecuteTask extends sfDoctrineBaseTask
{
  protected function configure()
  {
    # setup
    $this->defaultOptionDir = 'data' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'tasks';
    $this->defaultOptionDelimiter = '~';

    # task props
    $this->name = 'execute';
    $this->namespace = 'sql';

    $this->briefDescription = 'Executes each by one SQL file queries from specific directory';
    $this->detailedDescription = <<<EOF
The [{$this->namespace}:{$this->name}|INFO] task reads *.sql files in search directory and then runs them in order
Call it with:

  [./symfony {$this->namespace}:{$this->name}|INFO]

To work in certain environment run this command with [--env|COMMENT] option

  [./symfony {$this->namespace}:{$this->name} --env=prod|INFO]

To use certain application`s database settings use [--application|COMMENT] option

  [./symfony {$this->namespace}:{$this->name} --application=frontend|INFO]

If you need to customize the [*.sql|COMMENT] location dirname (default is [{$this->defaultOptionDir}|COMMENT]),
you can pass a [--dir|COMMENT] option:

  [./symfony {$this->namespace}:{$this->name} --dir=data/my/folder|INFO]

To exclude one or more files from [--dir|COMMENT] folder use [--exclude|COMMENT] option
In order to exclude "00-misc.sql" file from "data/my/folder" directory use:

  [./symfony {$this->namespace}:{$this->name} --dir=data/my/folder --exclude="00-misc.sql"|INFO]

In order to exclude many files from "data/my/folder" directory, separate is by commas:

  [./symfony {$this->namespace}:{$this->name} --dir=data/my/folder --exclude="00-misc.sql, 10-triggers.sql, 20-events.sql"|INFO]

Or you can use [glob|COMMENT] patterns (exclude all filename which contains words: "old" and "backup"):

  [./symfony {$this->namespace}:{$this->name} --dir=data/my/folder --exclude="*old*,*backup*"|INFO]

To run only one specific sql file use [--file|COMMENT]:

  [./symfony {$this->namespace}:{$this->name} --file=data/sql/tasks_1/alter-tables.sql|INFO]
or
  [./symfony {$this->namespace}:{$this->name} --dir=data/sql/tasks_1 --file=alter-tables.sql|INFO]

To setup your custom delimiter to separete SQL queries use [--delimiter|COMMENT] option:

  [./symfony {$this->namespace}:{$this->name} --delimiter="@"|INFO]

To search for *.sql file until sub certain-folder level use [--dir-depth|COMMENT] option:

  [./symfony {$this->namespace}:{$this->name} --dir-depth=5|INFO]

To search for *.sql file recursively pass "*" to [--dir-depth|COMMENT] option:

  [./symfony {$this->namespace}:{$this->name} --dir-depth=*|INFO]

EOF;

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'The environement', 'dev'),
      new sfCommandOption('dir', null, sfCommandOption::PARAMETER_OPTIONAL, 'The directory where to look for *.sql file', $this->defaultOptionDir),
      new sfCommandOption('dir-depth', null, sfCommandOption::PARAMETER_OPTIONAL, 'Search directory depth', 0),
      new sfCommandOption('file', null, sfCommandOption::PARAMETER_OPTIONAL, 'One file to be executed'),
      new sfCommandOption('exclude', null, sfCommandOption::PARAMETER_OPTIONAL, 'Exclude file pattern or file list separated by commas'),
      new sfCommandOption('delimiter', null, sfCommandOption::PARAMETER_OPTIONAL, 'Query delimiter', $this->defaultOptionDelimiter),

    ));
  }

  protected function execute($arguments = array(), $options = array())
  {
    # init db connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = Doctrine_Manager::connection();

    # check to only one certain file should be executed
    if (isset($options['file']))
    {
      $specificFile = $options['file'];

      if (! is_file($specificFile))
      {
        $specificFile = $options['dir'] . DIRECTORY_SEPARATOR . $specificFile;

        if (! is_file($specificFile))
        {
          $this->logSection("{$this->namespace}:{$this->name}", "file \"{$options['file']}\" does not exists");
          return false;
        }
      }

      $files = array($specificFile);
    }
    elseif (is_dir($options['dir']))
    {
      /* @var $finder sfFinder */
      $finder = sfFinder::type('file')->name('*.sql');

      # if exclude defined add exclude patterns to sfFinder
      if (isset($options['exclude']))
      {
        $finderCallable = new sfCallable(array($finder, 'not_name'));

        $clearedExcludedFilenames = array();

        # process commas to delimit filename/globs to separet argumnets
        foreach (explode(',', $options['exclude']) as $filename)
        {
          $filename = trim($filename);
          $clearedExcludedFilenames[$filename] = $filename;
        }

        $finderCallable->call($clearedExcludedFilenames);
      }

      $depth = $options['dir-depth'];

      if ($depth === '*')
      {
        # recursively
      }
      elseif (0 <= $depth)
      {
        $finder->maxdepth((int) $depth);
      }
      else
      {
        $this->logSection("{$this->namespace}:{$this->name}", "incorrect value passed to 'dir-depth' - \"{$options['dir-depth']}\"");
        
        return false;
      }

      # sort files by name to be executed in order
      $files = $finder->sort_by_name()->in($options['dir']);
    }
    else
    {
      $this->logSection("{$this->namespace}:{$this->name}", "directory \"{$options['dir']}\" does not exists");
      return false;
    }
    
    $this->logSection("{$this->namespace}:{$this->name}", 'start');

    if (0 < count($files))
    {
      foreach ($files as $file)
      {
        $fileBasename = basename($file, PATHINFO_BASENAME);
        try
        {
          # delimit sql file content on separate queries
          foreach (explode($options['delimiter'], file_get_contents($file)) as $query)
          {
            $query = trim($query);

            if (0 == strlen($query))
            {
              continue;
            }

            $queryOutput = preg_replace('/\s+/', ' ', str_replace("\n", ' ', $query));

            $this->logSection("{$this->namespace}:{$this->name}", "[{$fileBasename}] {$queryOutput}", 80);

            Doctrine_Manager::connection()->execute($query);
          }
        }
        catch (Exception $e)
        {
          $queryOutput = mb_substr($queryOutput, 0, 15, 'utf8');

          $this->logSection("{$this->namespace}:{$this->name}", "[{$fileBasename}] \"{$queryOutput}\" marked to be skipped.", null, 'ERROR');

          $this->logSection("{$this->namespace}:{$this->name}", "[{$fileBasename}] \"{$queryOutput}\" reason: {$e->getMessage()}", 1000, 'ERROR');

          return false;
        }
      }
    }
    else
    {
      $this->logSection("{$this->namespace}:{$this->name}", 'no sql files found');
      
      return false;
    }

    $this->logSection("{$this->namespace}:{$this->name}", 'end');

    return true;
  }
}
