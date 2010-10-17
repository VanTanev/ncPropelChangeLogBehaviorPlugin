<?php

// Register behavior's method
sfPropelBehavior::registerMethods('changelog', array(
  array('ncPropelChangeLogBehavior', 'getChangeLog'),
  array('ncPropelChangeLogBehavior', 'get1NRelatedChangeLog'),
  array('ncPropelChangeLogBehavior', 'getNNRelatedChangeLog'),
  array('ncPropelChangeLogBehavior', 'getRelatedChangeLog'),
  array('ncPropelChangeLogBehavior', 'getChangeLogRoute'),
  array('ncPropelChangeLogBehavior', 'getLatestChangeLogEntry'),
  array('ncPropelChangeLogBehavior', 'setCustomChangeMessage')
));

// Register behavior's hooks
sfPropelBehavior::registerHooks('changelog', array(
  ':save:pre'     => array('ncPropelChangeLogBehavior', 'preSave'),
  ':save:post'    => array('ncPropelChangeLogBehavior', 'postSave'),
  ':delete:post'  => array('ncPropelChangeLogBehavior', 'postDelete'),
));
