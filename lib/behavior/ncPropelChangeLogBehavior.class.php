<?php

/**
 * ncPropelChangeLogBehavior class.
 * Behavior that keeps a change log for an object.
 *
 * @author      José Nahuel CUESTA LUENGO <ncuesta@cespi.unlp.edu.ar>
 * @package     ncPropelChangeLogBehaviorPlugin
 * @subpackage  lib
 * @version     $SVN Id: $
 */
class ncPropelChangeLogBehavior
{
  /**
   * Before an $object is saved, determine the changes that have been made to it (if it had already been saved),
   * generate an ncChangeLogEntry an queue it so it can be committed *after* the object has been saved to the database.
   *   * If running from cli (as in propel:data-load or propel:build-all-load tasks), there won't be any available user to
   * get its username, so a configurable default value will be used: 'app_nc_change_log_behavior_username_cli' (defaults to 'cli').
   *   * Otherwise (if not running from cli), use 'app_nc_change_log_behavior_username_attribute' configuration value to obtain
   *       sfUser's username attribute (Defaults to 'username').
   *
   * @param     mixed $object
   * @param     PropelPDO $con
   */
  public function preSave(BaseObject $object, $con = null)
  {
    $object->changelogEntry = new ncChangeLogEntry($object);
    
    $object->changelogEntry->setUsername(ncChangeLogUtils::getUsername());

    if ($object->isNew())
    {
      $object->changelogEntry->setOperationType(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION);
      $object->changelogEntry->setCreatedAt(time());
      
      if (method_exists($object, 'setCreatedAt'))
      {
        $object->setCreatedAt($object->changelogEntry->getCreatedAt(null));
      }
    }
    else
    {
      $object->changelogEntry->setOperationType(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE);

      self::_update_changes($object);
    }
    
    return true;
  }
  

  /**
   * After an object has been saved, commit the changes to its changelog.
   * 
   * @param     mixed $object
   * @param     PropelPDO $con
   */
  public function postSave(BaseObject $object, $con = null)
  {
    if (isset($object->changelogEntry) && $object->changelogEntry instanceof ncChangeLogEntry)
    {
      if ($object->changelogEntry->isOperation(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION))
      {
        $object->changelogEntry->setObjectPk($object->getPrimaryKey());

        $changes = array(
          'raw'   => array()
        );

        $object->changelogEntry->setChangesDetail($changes);
      }

      $object->changelogEntry->save($con);
      $object->changelogEntry = null;
    }
    
    return true;
  }
  

  /**
   * After an object has been deleted, state this change in its ChangeLog.
   *   * Use 'app_nc_change_log_behavior_username_attribute' configuration value to obtain the performing action username.
   *       Defaults to 'username'.
   *
   * @param mixed $object
   * @param mixed $con
   */
  public function postDelete(BaseObject $object, $con = null)
  {
    $entry = new ncChangeLogEntry($object);

    $entry->setUsername(ncChangeLogUtils::getUsername());
    $entry->setOperationType(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_DELETION);

    $changes = array(
      'raw'   => array()
    );

    $entry->setChangesDetail($changes);
    $entry->save($con);
  }

  
  /**
   * Get $object's ChangeLog and return it as an array of ncChangeLogAdapters.
   * If no entry is found, answer an empty Array.
   * 
   * @param mixed $object
   * @param Criteria $criteria
   * @param PropelPDO $con
   * @return Array of ncChangeLogEntry
   */
  public function getChangeLog(BaseObject $object, Criteria $criteria = null, PropelPDO $con = null, $transformToAdapters = true)
  {
    return ncChangeLogEntryPeer::getChangeLogByPKandClassName($object->getPrimaryKey(), get_class($object), $criteria, $con, $transformToAdapters);
  }


  public static function getRelatedAdapters($tables)
  {
    $results  = array();

    foreach ($tables as $t => $objects)
    {
      foreach ($objects as $f => $object)
      {
        $results[$t][$f] = $object->getAdapter();
      }
    }

    return $results;
  }

  
  /**
   * Get $object's Related ChangeLog and return it as an array of ncChangeLogAdapters.
   * If no entry is found, answer an empty Array.
   *
   * This methods inspects the columns of the object's table and if one of them is a foreign key,
   * it returns the change log of the referenced object.
   *
   * @param mixed $object
   * @param date $from_date
   * @param transformToAdapters
   *
   * @return Array of ncChangeLogEntry
   */
  public function get1NRelatedChangeLog(BaseObject $object, $from_date = null, $transformToAdapters = true)
  {
    $relatedChangeLog = array();

    if (!is_null($object))
    {
      $class      = get_class($object);
      $peer       = constant($class.'::PEER');
      $tableMap   = call_user_func(array($peer , 'getTableMap'));

      foreach ($tableMap->getColumns() as $c)
      {
        if ($c->isForeignKey())
        {
          $method           = 'get'.$c->getPhpName();
          $relatedTableName = $c->getRelatedTableName();
          $relatedColName   = $c->getRelatedColumnName();
          $relatedPeerClass = ncClassFinder::getInstance()->findPeerClassName($relatedTableName);
          $relatedClass     = ncClassFinder::getInstance()->findClassName($relatedTableName, $relatedPeerClass);

          $criteria = new Criteria();
          $criteria->add(ncChangeLogEntryPeer::CLASS_NAME, $relatedClass);
          $criteria->add(ncChangeLogEntryPeer::OBJECT_PK,  $object->$method());

          if (!is_null($from_date))
            $criteria->add(ncChangeLogEntryPeer::CREATED_AT, $from_date, Criteria::GREATER_THAN);

          $relatedChangeLog[$c->getName()] = ncChangeLogEntryPeer::doSelect($criteria);
        }
      }
    }

    return $transformToAdapters ? self::getRelatedAdapters($relatedChangeLog) : $relatedChangeLog;
  }

  
  /**
   * This methods inspects the columns of the object's table and if one of them if a foreign key,
   * it returns the change log of the referenced object IF it points to the specified object (parameter).
   *
   * @param mixed $object
   * @param date $from_date
   * @param transformToAdapters
   *
   * @return Array of ncChangeLogEntry
   */
  public function getNNRelatedChangeLog(BaseObject $object, $from_date = null, $transformToAdapters = true)
  {
    $relatedChangeLog = array();
    $relatedObjects   = array();

    if (!is_null($object))
    {
      // Obtain object's information
      $object_class = get_class($object);
      $peer         = constant($object_class.'::PEER');

      // Get all tableMaps and make the queries to retrieve all object instances that reference the object!!!
      ncClassFinder::getInstance()->reloadClasses();

      foreach (ncClassFinder::getInstance()->getPeerClasses() as $class => $path)
      {
        if ($class != get_class($object) && class_exists($class) && method_exists($class, 'getTableMap'))
        {
          $criteria = new Criteria();
          $tableMap = call_user_func(array($class, 'getTableMap'));

          foreach ($tableMap->getColumns() as $c)
          {
            if ($c->isForeignKey())
            {
              $method           = 'get'.$c->getPhpName();
              $relatedTableName = $c->getRelatedTableName();
              $relatedColName   = $c->getRelatedColumnName();
              $relatedPeerClass = ncClassFinder::getInstance()->findPeerClassName($relatedTableName);
              $relatedClass     = ncClassFinder::getInstance()->findClassName($relatedTableName, $relatedPeerClass);

              // Traverse all collumns. If any has as its `relatedClass` the class of $object, make a
              // Criteria object to fetch every related object.
              if ($relatedClass == get_class($object))
              {
                $criterion = $criteria->getNewCriterion(constant($class.'::'.$c->getName()), ncChangeLogUtils::normalizePK($object->getPrimaryKey()));
                $criteria->addOr($criterion);
              }
            }
          }

          if ($criteria->size() > 0)
          {
            $relatedObjects[$class] = call_user_func(array($class, 'doSelect'), $criteria);
          }
        }
      }

      // Get every object's change log
      foreach ($relatedObjects as $tableName => $objects)
      {
        foreach ($objects as $o)
        {
          $criteria = new Criteria();

          if (!is_null($from_date))
          {
            $criteria->add(ncChangeLogEntryPeer::CREATED_AT, $from_date, Criteria::GREATER_THAN);
          }

          if (sfMixer::getCallable('Base'.get_class($o).':getChangeLog') && count($changes = $o->getChangeLog($criteria)) > 0)
          {
            if (method_exists($o, '__toString'))
            {
              $relatedChangeLog[$tableName][strval($o)] = $changes;
            }
            else
            {
              $relatedChangeLog[$tableName][ncChangeLogUtils::normalizePK($o->getPrimaryKey())] = $changes;
            }
          }
        }
      }
    }
    
    return $relatedChangeLog;
  }


  /**
   * Answer the route to $object's change log module.
   *
   * @param mixed $object
   * @return String
   */
  public function getChangeLogRoute(BaseObject $object)
  {
    return '@nc_change_log?class='.get_class($object).'&pk='.ncChangeLogUtils::normalizePK($object->getPrimaryKey());
  }

  
  /**
   * Inspect the changes made to $object since its last version (the one stored in the database).
   * Update $entry's changes_detail to reflect the changes made.
   *
   * @param mixed $object
   * @param ncChangeLogEntry $entry
   */
  protected static function _update_changes(BaseObject $object)
  {
    //hack: remove $object from it's Peer's instance pool before diff is computed
    call_user_func(array(get_class($object->getPeer()), 'removeInstanceFromPool'), $object);

    $stored_object = call_user_func_array(array(get_class($object->getPeer()), 'retrieveByPK'), is_array($object->getPrimaryKey()) ? $object->getPrimaryKey() : array($object->getPrimaryKey()));
 
    if (!$stored_object || !$object->isModified())
    {
      // Unable to retrieve object from database: do nothing
      $object->changelogEntry = null;
      return false;
    }

    $ignored_fields = ncChangeLogConfigHandler::getIgnoreFields(get_class($object));
    $tableMap = Propel::getDatabaseMap()->getTable(constant(get_class($object->getPeer()).'::TABLE_NAME'));

    $diff = array('changes' => array());
    
    foreach ($object->getModifiedColumns() as $column)
    {
      $col_fieldName = BasePeer::translateFieldname(get_class($object), $column, BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME);
      
      if (!in_array($col_fieldName, $ignored_fields))
      {
        $columnMap = $tableMap->getColumn($column);
        list ($value_method, $params) = ncChangeLogUtils::extractValueMethod($columnMap);
        
        $diff['changes'][$col_fieldName] = array(
          'old'   => $stored_object->$value_method($params),
          'new'   => $object->$value_method($params),
          'field' => $col_fieldName,
          'raw'    => array(
             // the previous version used toArray for these values, but this is exactly the same
            'old'   => $stored_values->$value_method(),
            'new'   => $object->$value_method(),
          )
        );
      }
    }

    if (empty($diff['changes']))
    {
      // it's possible that only ignored fields were modified, in which case do nothing
      $object->changelogEntry = null;
      return false;
    }

    $object->changelogEntry->setChangesDetail($diff);
    return true;
  }

}
