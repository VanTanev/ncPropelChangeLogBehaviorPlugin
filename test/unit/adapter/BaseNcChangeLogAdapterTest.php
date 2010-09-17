<?php
require_once(dirname(__FILE__).'/../../bootstrap/unit.php');

$t = new lime_test();
$t->diag('Testing lib/adapter/ncChangeLogAdapter.class.php');

class BaseNcChangeLogAdapter extends ncChangeLogAdapter
{
  public function renderClassName();
  public function render();
  public function renderList($url = null);
}

$test_entry = new ncChangeLogEntry();


$t->diag();