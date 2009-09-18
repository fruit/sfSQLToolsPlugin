<?php

/*
 * This file is part of the sfSQLToolsPlugin package.
 * (c) Ilya Sabelnikov <fruit.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// initializes testing framework

require_once realpath(dirname(__FILE__) . '/../../../../test/bootstrap/unit.php');
require_once "{$configuration->getRootDir()}/plugins/sfSQLToolsPlugin/lib/task/sfSqlExecuteTask.class.php";

$t = new lime_test(null, new lime_output_color());

$taskCommands = array(
  array('args' => array('somearg', 'someotherarg'), 'options' => array(), 'return' => false, 'incorrect' => true),
  array('args' => array(), 'options' => array('--ff=1'), 'return' => false, 'incorrect' => true),
  array(
    'args' => array(),
    'options' => array('--env=devmysql', '--dir=plugins/sfSQLToolsPlugin/data/sql/example-tasks'),
    'return' => true,
  ),
  array(
    'args' => array(),
    'options' => array('--env=devmysql', '--dir=plugins/sfSQLToolsPlugin/data/sql/example-tasks', '--delimiter=@'),
    'return' => false,
  ),
  array(
    'args' => array(),
    'options' => array('--env=devmysql', '--dir=plugins/sfSQLToolsPlugin/data/sql', '--file=10-create-tables.sql'),
    'return' => false,
  ),
  array(
    'args' => array(),
    'options' => array('--env=devmysql', '--dir=plugins/sfSQLToolsPlugin/data/sql/example-tasks', '--file=10-create-tables.sql'),
    'return' => true,
  ),
  array(
    'args' => array(),
    'options' => array('--env=devmysql', '--file=plugins/sfSQLToolsPlugin/data/sql/10-create-tables.sql'),
    'return' => false,
  ),
  array(
    'args' => array(),
    'options' => array('--env=devmysql', '--file=plugins/sfSQLToolsPlugin/data/sql/example-tasks/10-create-tables.sql'),
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
    $t->is(isset($v['incorrect']), true, 'Too many arguments or unknown options');
  }
}

