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
  * 
  * @see PluginncChangeLogEntryPeer::getChangeLogByPKandClassName()
  */
  public static function getChangeLogOfObject($class_name, $primary_key, $from_date = null, $to_date = null, PropelPDO $con = null)
  {
    $c = new Criteria();

    $c->add(ncChangeLogEntryPeer::CREATED_AT, $from_date, Criteria::GREATER_EQUAL);
    $c->addAnd(ncChangeLogEntryPeer::CREATED_AT, $to_date, Criteria::LESS_EQUAL);
    
    return ncChangeLogEntryPeer::getChangeLogByPKandClassName($primary_key, $class_name, $c, true, $con);
  }
  
  
  /**
  * Retrieve all changes for a particular Propel Object by its PK and Class name
  * 
  * You can filter the results by criteria; Result is array of ncChangeLogAdapter-s, 
  * set $transformToAdapters to false to receive array of ncChangeLofEntry objects
  * 
  * @param mixed $primary_key Integer, array or a Propel object
  * @param mixed $class_name The class name of the object
  * @param Criteria $criteria
  * @param boolean $transformToAdapters
  * @param PropelPDO $con
  * 
  * @return ncChangeLogEntry|ncChangeLogAdapter
  */
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
  
  
  /**
  * Retrieves the latest change log entry for a particular object
  * 
  * @param BaseObject $object
  * @param boolean $transformToAdapter
  * @param PropelPDO $con
  * 
  * @return ncChangeLogEntry|ncChangeLogAdapter
  */
  public static function getLatestChangeLogEntryForObject(BaseObject $object, $transformToAdapter, PropelPDO $con = null)
  {
    $c = new Criteria();
    $c->add(self::CLASS_NAME, get_class($object));
    $c->add(self::OBJECT_PK,  ncChangeLogUtils::normalizePK($object));
    $c->addDescendingOrderByColumn(self::CREATED_AT);
    
    $entry = ncChangeLogEntryPeer::doSelectOne($c, $con);
    
    return $entry ? ($transformToAdapter ? $entry->getAdapter() : $entry) : null;
  }
    
}
