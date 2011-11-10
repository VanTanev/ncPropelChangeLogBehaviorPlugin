<?php

// Register behavior's method
sfPropelBehavior::registerMethods('changelog', array(
  array('ncPropelChangeLogBehavior', 'getChangeLog'),
  array('ncPropelChangeLogBehavior', 'get1NRelatedChangeLog'),
  array('ncPropelChangeLogBehavior', 'getNNRelatedChangeLog'),
  array('ncPropelChangeLogBehavior', 'getRelatedChangeLog'),
  array('ncPropelChangeLogBehavior', 'getLatestChangeLogEntry'),
  array('ncPropelChangeLogBehavior', 'setCustomChangeMessage')
));

// Register behavior's hooks
sfPropelBehavior::registerHooks('changelog', array(
  ':save:pre'     => array('ncPropelChangeLogBehavior', 'preSave'),
  ':save:post'    => array('ncPropelChangeLogBehavior', 'postSave'),
  ':delete:post'  => array('ncPropelChangeLogBehavior', 'postDelete'),
));

if (sfConfig::get('app_nc_change_log_behavior_register_routes', true) && in_array('ncChangeLogEntry', sfConfig::get('sf_enabled_modules', array())))
{
  // setup the ncChangeLogEntry routes
  $this->dispatcher->connect('routing.load_configuration', array('ncChangeLogRouting', 'listenToRoutingLoadConfigurationEvent'));
}
