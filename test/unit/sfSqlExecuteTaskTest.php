<?php

/*
 * This file is part of the sfSQLToolsPlugin package.
 * (c) Ilya Sabelnikov <fruit.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once realpath(dirname(__FILE__) . '/../../../../test/bootstrap/unit.php');
require_once "{$configuration->getRootDir()}/plugins/sfSQLToolsPlugin/lib/task/sfSqlExecuteTask.class.php";

$sqlFolder = realpath(dirname(__FILE__) . '/../data');

$t = new lime_test(14, new lime_output_color());

$taskCommands = array(
  array('args' => array('somearg', 'someotherarg'), 'options' => array(), 'return' => false, 'incorrect' => true),
  array('args' => array(), 'options' => array('--ff="1"'), 'return' => false, 'incorrect' => true),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="' . $sqlFolder . '/example-tasks-0"'),
    'return' => true,
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="' . $sqlFolder . '/example-tasks-0"', '--delimiter="@"'),
    'return' => false, # files are delimited by ~
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="' . $sqlFolder . '"'),
    'return' => false, # folder is empty
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="' . $sqlFolder . '"', '--dir-depth="1"'),
    'return' => true, # folder has subfolders with sql files
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="plugins/sfSQLToolsPlugin"', '--dir-depth="*"'),
    'return' => true, # folder has subfolders with sql files
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="plugins/sfSQLToolsPlugin"', '--dir-depth="1"'),
    'return' => false, # folder subfolders does not have sql files
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="' . $sqlFolder . '"', '--file="10-create-tables.sql"'),
    'return' => false, # directory has no *.sql files (--dir-depth=0)
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--dir="' . $sqlFolder . '/example-tasks-0"', '--file="10-create-tables.sql"'),
    'return' => true,
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--file="' . $sqlFolder . '/10-create-tables.sql"'),
    'return' => false, # file does not exists
  ),
  array(
    'args' => array(),
    'options' => array('--env="devmysql"', '--file="' . $sqlFolder . '/example-tasks-0/10-create-tables.sql"'),
    'return' => true,
  ),
  array(
    'args' => array(),
    'options' => array(
      '--env="devmysql"',
      '--dir="' . $sqlFolder . '/example-tasks-0"',
      '--exclude="20-inserts.sql, 30-procedures.sql"'
    ),
    'return' => true,
  ),
  array(
    'args' => array(),
    'options' => array(
      '--env="devmysql"',
      '--dir="' . $sqlFolder . '/example-tasks-1"',
      '--delimiter="/*sql*/"'
    ),
    'return' => true,
  ),
);

$task = new sfSqlExecuteTask($configuration->getEventDispatcher(), new sfFormatter(80));

foreach ($taskCommands as $v)
{
  $command = './symfony sql:execute ' . implode(' ', $v['args']) . ' ' . implode(' ', $v['options']);

  try
  {
    $t->cmp_ok($task->run($v['args'], $v['options']), '===', $v['return'], $command);
  }
  catch (sfException $e)
  {
    $t->is(isset($v['incorrect']), true, 'Too many arguments or unknown options (exception triggered)');
  }
}

