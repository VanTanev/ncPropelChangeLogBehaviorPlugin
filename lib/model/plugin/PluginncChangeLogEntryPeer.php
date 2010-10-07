<?php

class PluginncChangeLogEntryPeer extends BasencChangeLogEntryPeer
{
  
  /**
  * Get the changelog for an object, optionally constrained to a timeframe
  * 
  * Kept for BC
  * 
  * @param mixed $class_name
  * @param mixed $primary_key
  * @param mixed $from_date
  * @param mixed $to_date
  * @param PropelPDO $con
  */
  public static function getChangeLogOfObject($class_name, $primary_key, $from_date = null, $to_date = null, PropelPDO $con = null)
  {
    $c = new Criteria();

    $c->add(ncChangeLogEntryPeer::CREATED_AT, $from_date, Criteria::GREATER_EQUAL);
    $c->addAnd(ncChangeLogEntryPeer::CREATED_AT, $to_date, Criteria::LESS_EQUAL);
    
    return ncChangeLogEntryPeer::getChangeLogByPKandClassName($primary_key, $class_name, $c, true, $con);
  }
  
  
  public static function getChangeLogByPKandClassName($primary_key, $class_name, Criteria $criteria = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    if ($criteria instanceof Criteria)
    {
      $criteria = clone $criteria;
    }
    else
    {
      $criteria = new Criteria();
    }
    
    $criteria->add(ncChangeLogEntryPeer::CLASS_NAME, $class_name);
    $criteria->add(ncChangeLogEntryPeer::OBJECT_PK, ncChangeLogUtils::normalizePK($primary_key));

    $results = array();
    $entries = ncChangeLogEntryPeer::doSelect($criteria, $con);
    
    if ($transformToAdapters)
    {
      foreach ($entries as $entry)
      {
        $results[] = $entry->getAdapter();
      }
    }
    else
    {
      $results = $entries;
    }
    
    return $results;
  }
  
  
  
  
    
}
