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
  
# ---- HOOKS  
  
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

  
# ---- PUBLIC API
  
  
  /**
   * Get $object's ChangeLog and return it as an array of ncChangeLogAdapters.
   * If no entry is found, answer an empty Array.
   * 
   * @param BaseObject $object
   * @param Criteria $criteria
   * @param PropelPDO $con
   * @return Array of ncChangeLogEntry
   */
  public function getChangeLog(BaseObject $object, Criteria $criteria = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    return ncChangeLogEntryPeer::getChangeLogByPKandClassName($object->getPrimaryKey(), get_class($object), $criteria, $transformToAdapters, $con);
  }
  
  
  /**
   * Returns a combination of the 1N and NN related changelogs, if present.
   * 
   * @param BaseObject $object
   * @param mixed $from_date
   * @param boolean $transformToAdapters
   * @param PropelPDO $con
   * 
   * @return Array of ncChangeLogEntry
   * 
   * @see ncPreopelChangelogBehavior::get1NRelatedChangeLog()
   * @see ncPreopelChangelogBehavior::getNNRelatedChangeLog()
   */
  public function getRelatedChangeLog(BaseObject $object, $from_date = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    $changelog_1N = self::get1NRelatedChangeLog($object, $from_date, $transformToAdapters, $con);

    try {
      $changelog_NN = self::getNNRelatedChangeLog($object, $from_date, $transformToAdapters, $con);
    } catch (Exception $x) {
      // we are not in Propel 1.5
      $changelog_NN = array();
    }
    
    return array_merge($changelog_1N, $changelog_NN);
  }

  
  /**
   * Get $object's Related ChangeLog and return it as an array of ncChangeLogAdapters.
   * If no entry is found, answer an empty Array.
   *
   * This methods inspects the columns of the object's table and if one of them is a foreign key,
   * it returns the change log of the referenced object.
   *
   * @param BaseObject $object
   * @param mixed $from_date
   * @param boolean $transformToAdapters
   * @param PropelPDO $con
   *
   * @return Array of ncChangeLogEntry
   */
  public function get1NRelatedChangeLog(BaseObject $object, $from_date = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    /** @var ColumnMap */ $col;
    /** @var TableMap  */ $tableMap;

    $relatedChangeLog = array();

    $class      = get_class($object);
    $peer       = constant($class . '::PEER');
    $tableMap   = call_user_func(array($peer , 'getTableMap'));
    // we need to build the relations, otherwize the related TableMap object might not have been autoloaded
    $tableMap->buildRelations();
    
    $criteria   = new Criteria();
    if (!is_null($from_date))
    {
      $criteria->add(ncChangeLogEntryPeer::CREATED_AT, $from_date, Criteria::GREATER_THAN);
    }    

    foreach ($tableMap->getColumns() as $col)
    {
      if ($col->isForeignKey())
      {
        $method       = 'get' . $col->getPhpName();
        $relatedClass = $col->getRelatedTable()->getClassname();
        
        $changelog = ncChangeLogEntryPeer::getChangeLogByPKandClassName($object->$method(), $relatedClass, $criteria, $transformToAdapters, $con);
        
        if (!empty($changelog))
        {
          $relatedChangeLog[$col->getRelatedTable()->getPhpName()] = $changelog;
        }
      }
    }

    return $relatedChangeLog;
  }

  
  /**
   * This methods inspects the columns of the object's table and if one of them if a foreign key,
   * it returns the change log of the referenced object IF it points to the specified object (parameter).
   * 
   * This method works only with Propel 1.5 or higher
   *
   * @param mixed $object
   * @param date $from_date
   * @param transformToAdapters
   *
   * @return Array of ncChangeLogEntry
   */
  public function getNNRelatedChangeLog(BaseObject $object, $from_date = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    if (!defined("RelationMap::MANY_TO_MANY"))
    {
      throw new Exception("ncPropelChangeLogBehavior cannot handle M:M relationships unless you are using Propel 1.5 or higher");
    }
    
    
    /** @var ColumnMap */  $col;
    /** @var TableMap  */  $tableMap;
    /** @var RelationMap*/ $rel;

    $relatedChangeLog = array();
    $criteria         = new Criteria();
    
    $class            = get_class($object);
    $peer             = constant($class . '::PEER');
    $tableMap         = call_user_func(array($peer , 'getTableMap'));

    
    foreach ($tableMap->getRelations() as $rel)
    {
      // first we find our M:M relationship
      if (RelationMap::MANY_TO_MANY == $rel->getType())
      {
        $relatedTableMap         = $rel->getLocalTable(); // yeah, it says local table... propel is strange like that ;)
        $relatedTableObjectClass = $relatedTableMap->getClassname();
        $relatedTablePeerClass   = constant($relatedTableObjectClass . '::PEER');
        $relatedTableName        = $relatedTableMap->getPhpName();

        foreach ($tableMap->getRelations() as $relCrossRef)
        {
          // next we find the relationship that points to the CrossRef table
          if (RelationMap::ONE_TO_MANY == $relCrossRef->getType() && false !== strpos($relCrossRef->getName(), $rel->getName()))
          {
            /** @var TableMap */
            $crossRefTableMap    = $relCrossRef->getLocalTable();
            $crossRefTableName   = $crossRefTableMap->getName();
            $crossRefObjectClass = $crossRefTableMap->getClassname();
            $crossRefPeerClass   = constant($crossRefObjectClass . '::PEER');
            
            $localColumns = $relCrossRef->getLocalColumns();
            $foreignColumns = $crossRefTableMap->getRelation($rel->getName())->getLocalColumns();
            
            // For now, we won't handle composite relations... it's too much of a pain in the ass
            if (!$relCrossRef->isComposite())
            { 
              /** @var ColumnMap */
              $crossRefColumnLocal   = array_pop($localColumns);
              /** @var ColumnMap */
              $crossRefColumnForeign = array_pop($foreignColumns);
              
              $criteria->clear();
              $criteria->add($crossRefColumnLocal->getFullyQualifiedName(), ncChangeLogUtils::normalizePK($object));
              $criteria->addSelectColumn($crossRefColumnForeign->getFullyQualifiedName());
              $relatedTablePKs = call_user_func(array($crossRefPeerClass, 'doSelectStmt'), $criteria)->fetchAll(PDO::FETCH_COLUMN, 0);
              
              $criteria->clear();
              $criteria->add($crossRefColumnForeign->getRelatedName(), $relatedTablePKs, Criteria::IN);
              $relatedObjects = call_user_func(array($relatedTablePeerClass, 'doSelect'), $criteria);
              
              $criteria->clear();
              if (!is_null($from_date))
              {
                $criteria->add(ncChangeLogEntryPeer::CREATED_AT, $from_date, Criteria::GREATER_THAN);
              }
              
              foreach ($relatedObjects as $relatedObject)
              {
                if (method_exists($relatedObject, '__toString'))
                {
                  $changelogKey = $relatedObject->__toString();
                }
                else
                {
                  $changelogKey = ncChangeLogUtils::normalizePK($relatedObject);
                }
                
                $changelog = ncChangeLogEntryPeer::getChangeLogByPKandClassName($relatedObject->getPrimaryKey(), $relatedTableObjectClass, $criteria, $transformToAdapters, $con);
                
                if (!empty($changelog))
                {
                  $relatedChangeLog[$relatedTableName][$changelogKey] = $changelog;
                }
              }
              
            } // if ($crossRefRel ! isComposite)
          } // if ($crossRefRel is ONE_TO_MANY)
        } // foreach ($relCrossRef)
      } // if ($rel is MANY_TO_MANY)
    } // foreach ($relations)

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
    return '@nc_change_log?class='.get_class($object).'&pk='.ncChangeLogUtils::normalizePK($object);
  }


  /**
  * Retrieve the latest change log entry for this object
  * 
  * @param BaseObject $object
  * @param boolean $transformToAdapter
  * @param PropelPDO $con
  * 
  * @return ncChangeLogEntry|ncChangeLogAdapter
  */
  public function getLatestChangeLogEntry(BaseObject $object, $transformToAdapter = true, PropelPDO $con = null)
  {
    return ncChangeLogEntryPeer::getLatestChangeLogEntryForObject($object, $transformToAdapter, $con);
  }
  
  
# ---- UTILITY METHODS


  /** @var sfEventDispatcher */
  protected static $dispatcher;
  
  /**
   * Inspect the changes made to $object since its last version (the one stored in the database).
   * Update $entry's changes_detail to reflect the changes made.
   *
   * @param mixed $object
   * @param ncChangeLogEntry $entry
   */
  protected static function _update_changes(BaseObject $object)
  {
    $objectPeerClass = constant(get_class($object) . '::PEER');
    
    // hack: remove $object from it's Peer's instance pool before diff is computed
    call_user_func(array($objectPeerClass, 'removeInstanceFromPool'), $object);

    $stored_object = call_user_func_array(array($objectPeerClass, 'retrieveByPK'), is_array($object->getPrimaryKey()) ? $object->getPrimaryKey() : array($object->getPrimaryKey()));
 
    if (!$stored_object || !$object->isModified())
    {
      // Unable to retrieve object from database: do nothing
      $object->changelogEntry = null;
      return false;
    }

    $ignored_fields = ncChangeLogConfigHandler::getIgnoreFields(get_class($object));
    $tableMap = Propel::getDatabaseMap()->getTable(constant($objectPeerClass.'::TABLE_NAME'));

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
            'old'   => $stored_object->$value_method(),
            'new'   => $object->$value_method(),
          )
        );
      }
    }
    
    // Filter the changes event; can be used to add custom fields or whatever
    
    if (!is_null(self::getEventDispatcher()))
    {
      $event = new sfEvent($object, $tableMap->getName() . '.nc_filter_changes');
      self::getEventDispatcher()->filter($event, $diff);
      
      $diff = $event->getReturnValue();
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
  
  
  /**
   * Tries to get the event dispatcher; returns null if not successfull
   * 
   * @return sfEventDispatcher
   */
  protected static function getEventDispatcher()
  {
    if (is_null(self::$dispatcher) && sfContext::hasInstance())
    {
      self::$dispatcher = sfContext::getInstance()->getEventDispatcher();
    }
    
    return self::$dispatcher;
  }

  
}
