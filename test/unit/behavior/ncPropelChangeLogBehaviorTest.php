<?php

$fixtures = 'fixtures/fixtures.yml';  

require_once(dirname(__FILE__).'/../../bootstrap/functional.php');

$t = new lime_test();
$t->diag('Testing lib/behavior/ncPropelChangeLogBehavior.class.php');

try{

$book = BookPeer::doSelectOne(new Criteria());

$book->setName("The hitchhiker's guide to the galaxy");
$book->save();

$book->getChangeLog();

$article = ArticlePeer::doSelectOne(new Criteria());



$article->get1NRelatedChangeLog();

$article->getNNRelatedChangeLog();

  $t->pass('No exceptions... maybe everything is fine?');
} catch (Exception $e) {
  $t->fail('Failed with exception :'.$e->getMessage());
}