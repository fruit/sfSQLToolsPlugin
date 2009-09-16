<?php

/**
 * Task to execute triggers and functions from symfony cli
 *
 * @author Ilya Sabelnikov <fruit.dev@gmail.com>
 */

class pgsqlInsertAllTask extends sfDoctrineBaseTask
{
	protected function configure()
  {
		$this->namespace = 'pgsql';
		$this->name = 'all';
		$this->briefDescription = 'Executes PgSQL sql files misc.sql, functions.sql, triggers.sql and then data.sql';
		$this->detailedDescription = <<<EOF
The [pgsql:all|INFO] task does things.
Call it with:

  [php symfony pgsql:all|INFO]
EOF;

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_OPTIONAL, 'The environement', 'dev'),
    ));
	}

	protected function execute($arguments = array(), $options = array())
  {
    $baseOptions = $this->configuration instanceof sfApplicationConfiguration
      ? array('--application='.$this->configuration->getApplication(), '--env='.$options['env'], )
      : array();

    $insertMiscTask = new pgsqlInsertMiscTask($this->dispatcher, $this->formatter);
    $insertMiscTask->setCommandApplication($this->commandApplication);

    if ($ret = $insertMiscTask->run(array(), $baseOptions))
    {
      return $ret;
    }

    $insertFunctionsTask = new pgsqlInsertFunctionsTask($this->dispatcher, $this->formatter);
    $insertFunctionsTask->setCommandApplication($this->commandApplication);

    if ($ret = $insertFunctionsTask->run(array(), $baseOptions))
    {
      return $ret;
    }

    $insertTriggersTask = new pgsqlInsertTriggersTask($this->dispatcher, $this->formatter);
    $insertTriggersTask->setCommandApplication($this->commandApplication);

    if ($ret = $insertTriggersTask->run(array(), $baseOptions))
    {
      return $ret;
    }

    $insertDataTask = new pgsqlInsertDataTask($this->dispatcher, $this->formatter);
    $insertDataTask->setCommandApplication($this->commandApplication);

    if ($ret = $insertDataTask->run(array(), $baseOptions))
    {
      return $ret;
    }
	}
}
