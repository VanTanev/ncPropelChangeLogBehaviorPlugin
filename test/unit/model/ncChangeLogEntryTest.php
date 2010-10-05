<?php

$fixtures = 'fixtures/fixtures.yml';  

require_once(dirname(__FILE__).'/../../bootstrap/functional.php');

$t = new lime_test();
$t->diag('Testing lib/model/plugin/PluginncChangeLogEntry.php');


$entry = new ncChangeLogEntry();
$entry->setClassName('TestClass');
$entry->setObjectPK(array(12, 17));
$entry->setOperationType(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE);
$entry->setCreatedAt($created_at_time = time());
$entry->setChangesDetail(array(
  'changes' => array('g_country' => array(
    'old'   => 'ggbg',
    'new'   => 'ggggggggggggggggggggg',
    'field' => 'g_country',
    'raw'    => array(
      'old'   => 'le_ggbg',
      'new'   => 'le_fffffffffffuuuuuuuu',
    )
  ))
));



$t->ok($entry->isOperation(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE),
  '->isOperation() retuns the expected result');
$t->ok(! $entry->isOperation(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION),
  '->isOperation() retuns the expected result');

  
$t->is((string) $entry, ncChangeLogEntryOperation::getStringFor(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE) . ' at ' . date('Y-m-d H:i:s', $created_at_time),
  '->__toString returns the expected result');
  

$t->is($entry->getOperationString(), ncChangeLogEntryOperation::getStringFor(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE),
  '->getOperationString() returns the expected result');


$entry->setObjectPK(array(12, 17));
$t->is($entry->getRawObjectPK(), '12-17',
  '->setObjectPK() processes arrays');
$t->is($entry->getObjectPK(), array(12, 17),
  '->getObjectPK() returns arrays for composite keys');
  

$entry->setObjectPK(array(20));
$t->is($entry->getRawObjectPK(), 20,
  '->setObjectPK() processes single value arrays');
  
$entry->setObjectPK(20);
$t->is($entry->getRawObjectPK(), 20,
  '->setObjectPK() processes scalar values');
$t->is($entry->getObjectPK(), 20,
  '->getObjectPK() handles scalar values');

  
/**
* Test the "get/set/clearObject" methods 
*/
$entry = new ncChangeLogEntry();
$t->isa_ok($entry->getObject(), 'NULL', 
  '->getObject() returns null on new entry');
  
$entry->setObject(BookPeer::doSelectOne(new Criteria()));
$t->isa_ok($entry->getObject(), 'Book', 
  '->getObject() retrieves the object set through ->setObject()');

$entry->clearObject();
$entry->setClassName('');
$entry->setPrimaryKey(null);
$t->isa_ok($entry->getObject(), 'NULL', 
  '->clearObject() removes the instance saved in the entry');



$entry->setClassName('Book');
$entry->setObjectPK(BookPeer::doSelectOne(new Criteria())->getId());
$t->isa_ok($entry->getObject(), 'Book', 
  '->getObject() can retrive the object from the DB based on a normal PK');


$a_a = AuthorArticlePeer::doSelectOne(new Criteria());  
$entry = new ncChangeLogEntry();
$entry->setObject($a_a);
$t->isa_ok($entry->getObject(), 'AuthorArticle',
  '->getObject() can retrive the object from the DB based on a composite PK');



