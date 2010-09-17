<?php
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test(6);
$t->diag('Testing lib/adapter/ncChangeLogAdapter.class.php');
$t->diag('Testing lib/adapter/ncChangeLogAdapterDeletion.class.php');
$t->diag('Testing lib/adapter/ncChangeLogAdapterInsertion.class.php');
$t->diag('Testing lib/adapter/ncChangeLogAdapterUpdate.class.php');

class BaseNcChangeLogAdapter extends ncChangeLogAdapter
{
  public function renderClassName() {}
  public function render() {}
  public function renderList($url = null) {}
}

$entry = new ncChangeLogEntry();
$entry->setClassName('TestClass');
$entry->setObjectPK(12);
$entry->setOperationType(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE);
$entry->setChangesDetail(array(
  'pk'    => 12,
  'class' => 'TestClass',
  'changes' => array('g_country' => array(
    'old'   => 'ggbg',
    'new'   => 'ggggggggggggggggggggg',
    'field' => 'g_country',
    'raw'    => array(
       // the previous version used toArray for these values, but this is exactly the same
      'old'   => 'le_ggbg',
      'new'   => 'le_fffffffffffuuuuuuuu',
    )
  ))
));

$adapter = new BaseNcChangeLogAdapter($entry);


$t->diag('Testing the base adapter');
try {
  $adapter['key'] = 'value';
  $t->fail('ncChangeLogAdapter does not allow setting changelog values');
} catch (LogicException $x) {
  $t->pass('ncChangeLogAdapter does not allow setting changelog values');
}

try {
  unset($adapter['key']);
  $t->fail('ncChangeLogAdapter does not allow unsetting changelog values');
} catch (LogicException $x) {
  $t->pass('ncChangeLogAdapter does not allow unsetting changelog values');
}

try {
  echo $adapter['non-existent-key'];
  $t->fail('ncChangeLogAdapter throws exception when trying to access a non-existent key');
} catch (InvalidArgumentException $x) {
  $t->pass('ncChangeLogAdapter throws exception when trying to access a non-existent key');
}

try {
  $temp = $adapter['g_country'];
  $t->pass('ncChangeLogAdapter allows array access for existing keys');
} catch (InvalidArgumentException $x) {
  $t->fail('ncChangeLogAdapter allows array access for existing keys');
}

$t->isa_ok($adapter['g_country'], 'array',
  'ncChangeLogAdapter does not alter the changelog array from the Entry');
                                               
# Update adapter
$adapter = new ncChangeLogAdapterUpdate($entry);
$t->diag('Testing Update adapter');
$t->isa_ok($adapter['g_country'], 'ncChangeLogUpdateChange',
  'ncChangeLogAdapterUpdate converts the changelog to an array of ncChangeLogUpdateChange objects');

