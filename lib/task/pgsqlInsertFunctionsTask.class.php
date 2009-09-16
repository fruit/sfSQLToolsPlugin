<?php

/**
 * Task to execute functions from symfony cli
 *
 * @author Ilya Sabelnikov <fruit.dev@gmail.com>
 */

class pgsqlInsertFunctionsTask extends sfDoctrineBaseTask
{
	protected function configure()
  {
		$this->namespace = 'pgsql';
		$this->name = 'functions';
		$this->briefDescription = 'Executes PgSQL functions';
		$this->detailedDescription = <<<EOF
The [pgsql:functions|INFO] task does things.
Call it with:

  [php symfony pgsql:functions|INFO]

EOF;
		
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'The environement', 'dev'),
    ));
	}

	protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);

		$file = sfConfig::get ( 'sf_data_dir' ) . '/sql/functions.sql';

    if (! is_file ( $file ))
    {
      $this->logSection ( 'pgsql:functions', 'skipping executing procedures');
		}

		$this->logSection ( 'pgsql:functions', 'start' );

    foreach (explode('~', file_get_contents($file)) as $query)
    {
      $query = trim(str_replace("\n", ' ', $query));
      
      $this->logSection('pgsql:execute', mb_substr($query, 0, 60) . '…');

      Doctrine_Manager::connection()->execute($query);
    }
    
		$this->logSection ( 'pgsql:functions', 'end' );
	}
}
