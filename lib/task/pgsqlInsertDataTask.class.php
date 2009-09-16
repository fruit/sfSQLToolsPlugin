<?php

/**
 * Task to execute data from symfony cli
 *
 * @author Ilya Sabelnikov <fruit.dev@gmail.com>
 */

class pgsqlInsertDataTask extends sfDoctrineBaseTask
{
	protected function configure()
  {
		$this->namespace = 'pgsql';
		$this->name = 'data';
		$this->briefDescription = 'Executes PgSQL data';
		$this->detailedDescription = <<<EOF
The [pgsql:data|INFO] task does things.
Call it with:

  [php symfony pgsql:data|INFO]
EOF;
		
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'The environement', 'dev'),
    ));
	}

	protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);

		$file = sfConfig::get ( 'sf_data_dir' ) . '/sql/data.sql';

    if (! is_file ( $file ))
    {
      $this->logSection ( 'pgsql:data', 'skipping executing data');
		}

		$this->logSection ( 'pgsql:data', 'start' );

    foreach (explode('~', file_get_contents($file)) as $query)
    {
      $query = trim(str_replace("\n", ' ', $query));

      $this->logSection('pgsql:execute', mb_substr($query, 0, 60) . '…');

      Doctrine_Manager::connection()->execute($query);
    }
    
		$this->logSection ( 'pgsql:data', 'end' );
	}
}