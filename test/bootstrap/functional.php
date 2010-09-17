<?php

// setup constants
$_SERVER['SYMFONY'] = dirname(__FILE__).'/../../../../lib/vendor/symfony/';
define('SF_DIR', $_SERVER['SYMFONY']);


// we need SQLite for functional tests
if (!extension_loaded('SQLite') && !extension_loaded('pdo_SQLite'))
{
  echo "SQLite extension is required to run unit tests\n";
  return false;
}


// create context instance
if (!isset($root_dir))
{
  $root_dir = realpath(dirname(__FILE__).sprintf('/../%s/fixtures', isset($type) ? $type : 'functional'));
}
if (!isset($app))
{
  $app = 'frontend';
}
require_once $root_dir.'/config/ProjectConfiguration.class.php';
$configuration = ProjectConfiguration::getApplicationConfiguration($app, 'test', isset($debug) ? $debug : true);
sfContext::createInstance($configuration);


// set cleanup/shutdown
function ncPropelChangeLogBehaviorPlugin_cleanup()
{
  sfToolkit::clearDirectory(dirname(__FILE__).'/../fixtures/project/cache');
  sfToolkit::clearDirectory(dirname(__FILE__).'/../fixtures/project/log');
}
ncPropelChangeLogBehaviorPlugin_shutdown();
register_shutdown_function('ncPropelChangeLogBehaviorPlugin_shutdown');

function ncPropelChangeLogBehaviorPlugin_shutdown()
{
  try
  {
    ncPropelChangeLogBehaviorPlugin_cleanup();
  }
  catch (Exception $x)
  {
    // http://bugs.php.net/bug.php?id=33598
    echo $x.PHP_EOL;
  }
}


// run propel generator tasks if necessary
$configuration->initializePropel($app);
if (isset($fixtures))
{
  $configuration->loadFixtures($fixtures);
}