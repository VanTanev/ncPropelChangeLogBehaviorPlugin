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
    if ($object->changelogEntry instanceof ncChangeLogEntry)
    {
      if ($object->changelogEntry->isOperation(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION))
      {
        $object->changelogEntry->setObjectPk($object->getPrimaryKey());

        $changes = array(
          // no changes
        );

        $object->changelogEntry->setChangesDetail($changes);
      }

      $object->changelogEntry->save($con);
      $object->changelogEntry->clearObject();
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
      // no changes
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
    } catch (OldPropelException $x) {
      // Propel older than 1.5, no M:M relationship support
      $changelog_NN = array();
    }

    return array_merge($changelog_1N, $changelog_NN);
  }


  /**
   * Get $object's Related ChangeLog and return it as an array of ncChangeLogAdapters.
   * If no entry is found, answer an empty Array.
   *
   * @param BaseObject $object
   * @param mixed $filter Either a Criteria object or a datetime limit in the past
   * @param boolean $transformToAdapters
   * @param PropelPDO $con
   *
   * @return array of ncChangeLogEntry
   */
  public function get1NRelatedChangeLog(BaseObject $object, $filter = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    $relatedChangeLog = array();
    $criteria = new Criteria();

    $class      = get_class($object);
    $tableMap   = call_user_func(array(constant($class.'::PEER'), 'getTableMap'));

    if (!is_null($filter))
    {
      if ($filter instanceof Criteria )
      {
        $criteria = clone $filter;
      }
      else
      {
        $criteria->add(ncChangeLogEntryPeer::CREATED_AT, $filter, Criteria::GREATER_THAN);
      }
    }

    foreach ( $tableMap->getRelations() as $relation )
    {
      if ( !$relation->isComposite() && RelationMap::ONE_TO_MANY == $relation->getType() )
      {
        $localColumns = $relation->getLocalColumns();
        $localColumn  = array_pop($localColumns); // for non composite relations, there is only one column

        $getterMethod = 'get' . $localColumn->getPhpName();
        $relatedClass = $relation->getForeignTable()->getClassname();

        $changelog = ncChangeLogEntryPeer::getChangeLogByPKandClassName($object->$getterMethod(), $relatedClass, $criteria, $transformToAdapters, $con);

        if ( !empty($changelog) )
        {
          $relatedChangeLog[$relation->getForeignTable()->getPhpName()] = $changelog;
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
   * @param mixed $filter
   * @param boolean $transformToAdapters
   *
   * @return Array of ncChangeLogEntry
   */
  public function getNNRelatedChangeLog(BaseObject $object, $filter = null, $transformToAdapters = true, PropelPDO $con = null)
  {
    if (!defined("RelationMap::MANY_TO_MANY"))
    {
      throw new OldPropelException("ncPropelChangeLogBehavior cannot handle M:M relationships unless you are using Propel 1.5 or higher");
    }

    /** @var ColumnMap */  $col;
    /** @var TableMap  */  $tableMap;
    /** @var RelationMap*/ $rel;

    $relatedChangeLog = array();
    $criteria         = new Criteria();
    $localCriteria    = new Criteria();

    $class            = get_class($object);
    $tableMap         = call_user_func(array(constant($class . '::PEER'), 'getTableMap'));

    if (!is_null($filter))
    {
      if ($filter instanceof Criteria )
      {
        $criteria = clone $filter;
      }
      else
      {
        $criteria->add(ncChangeLogEntryPeer::CREATED_AT, $filter, Criteria::GREATER_THAN);
      }
    }

    foreach ($tableMap->getRelations() as $rel)
    {
      // first we find our M:M relationship
      if (RelationMap::MANY_TO_MANY == $rel->getType())
      {
        $relatedTableMap         = $rel->getLocalTable(); // yeah, it says local table... propel is strange like that ;)
        $relatedTableObjectClass = $relatedTableMap->getClassname();
        $relatedTablePeerClass   = $relatedTableMap->getPeerClassname();
        $relatedTableName        = $relatedTableMap->getPhpName();

        foreach ($relatedTableMap->getRelations() as $relCrossRef)
        {
          // next we find the relationship that points to the CrossRef table
          if (RelationMap::ONE_TO_MANY == $relCrossRef->getType() && false !== strpos($relCrossRef->getName(), $rel->getName()))
          {
            /** @var TableMap */
            $crossRefTableMap    = $relCrossRef->getLocalTable();
            $crossRefTableName   = $crossRefTableMap->getName();
            $crossRefObjectClass = $crossRefTableMap->getClassname();
            $crossRefPeerClass   = $crossRefTableMap->getPeerClassname();

            $localColumns = $relCrossRef->getLocalColumns();
            $foreignColumns = $crossRefTableMap->getRelation($rel->getName())->getLocalColumns();

            // For now, we won't handle composite relations... it's too much of a pain in the ass
            if (!$relCrossRef->isComposite())
            {
              /** @var ColumnMap */
              $crossRefColumnLocal   = array_pop($localColumns);
              /** @var ColumnMap */
              $crossRefColumnForeign = array_pop($foreignColumns);

              $localCriteria->clear();
              $localCriteria->add($crossRefColumnLocal->getFullyQualifiedName(), ncChangeLogUtils::normalizePK($object));
              $localCriteria->addSelectColumn($crossRefColumnForeign->getFullyQualifiedName());
              $relatedTablePKs = call_user_func(array($crossRefPeerClass, 'doSelectStmt'), $localCriteria)->fetchAll(PDO::FETCH_COLUMN, 0);

              $localCriteria->clear();
              $localCriteria->add($crossRefColumnForeign->getRelatedName(), $relatedTablePKs, Criteria::IN);
              $relatedObjects = call_user_func(array($relatedTablePeerClass, 'doSelect'), $localCriteria);

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
    if (isset($object->changelogEntry) && $object->changelogEntry instanceof ncChangeLogEntry)
    {
      return $transformToAdapter ? $object->changelogEntry->getAdapter() : $object->changelogEntry;
    }

    return ncChangeLogEntryPeer::getLatestChangeLogEntryForObject($object, $transformToAdapter, $con);
  }


  /**
  * Add a custom change to the changelog of the object.
  *
  * Using this method you can convey state changes of the object, which might not be
  * directly described by its fields (the state change may occur in another table)
  *
  * It works in a way similar to the "Object created" and "Object deleted" changelog instances
  *
  * @param BaseObject $object
  * @param string $message
  * @param string $user You can set a custom user for custom messages, or leave it to autodetect
  * @param PropelPDO $con
  *
  * @return BaseObject
  */
  public function setCustomChangeMessage(BaseObject $object, $message, $user = null, PropelPDO $con = null)
  {
    if ( !is_scalar($message) )
    {
      throw new Exception(sprintf('[changelog] Only scalar values can be set as changelog messages, submitted value was of type "%s"', 'object' == gettype($message) ? get_class($message) : gettype($message)));
    }

    $entry = new ncChangeLogEntry($object);
    $entry->setOperationType(ncChangeLogEntryOperation::CUSTOM_MESSAGE);
    $entry->setUsername(is_null($user) ? ncChangeLogUtils::getUsername() : $user);
    $entry->setChangesDetail(array(
      'message' => $message
    ));

    $entry->save($con);
    $entry->clearObject();

    $object->changelogEntry = $entry;

    return $object;
  }


# ---- UTILITY METHODS



  /**
   * Inspect the changes made to $object since its last version (the one stored in the database).
   *
   * The new ncChangeLogEntry is set as a property of the $object,
   * simply named "changelogEntry" and is publicly available
   *
   * @param mixed $object
   *
   * @return ncChangeLogEntry
   */
  public static function _update_changes(BaseObject $object)
  {
    $objectPeerClass = constant(get_class($object) . '::PEER');

    // hack: remove $object from it's Peer's instance pool before diff is computed
    call_user_func(array($objectPeerClass, 'removeInstanceFromPool'), $object);

    $storedObject = call_user_func_array(array($objectPeerClass, 'retrieveByPK'), (array) $object->getPrimaryKey());

    if ( !($storedObject && $object->isModified()) )
    {
      // There is no previously stored object and no modifications were detected: do nothing
      $object->changelogEntry = null;
      return false;
    }

    if ( !$object->changelogEntry instanceof ncChangeLogEntry )
    {
      // the object must have a changelogEntry property
      $object->changelogEntry = new ncChangeLogEntry($object);
    }

    $ignoredFields = ncChangeLogConfigHandler::getIgnoreFields(get_class($object));
    $tableMap = call_user_func(array($objectPeerClass, 'getTableMap'));

    $diff = array('changes' => array());

    foreach ($object->getModifiedColumns() as $column)
    {
      $colFieldName = BasePeer::translateFieldname(get_class($object), $column, BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME);

      if (!in_array($colFieldName, $ignoredFields))
      {
        $columnMap = $tableMap->getColumn($column);
        list ($valueMethod, $params) = ncChangeLogUtils::extractValueMethod($columnMap);

        $diff['changes'][$colFieldName] = array(
          'old'   => $storedObject->$valueMethod($params),
          'new'   => $object->$valueMethod($params),
          'field' => $colFieldName,
          'type'  => $columnMap->getType(),
          'raw'    => array(
            'old'   => $storedObject->$valueMethod(),
            'new'   => $object->$valueMethod(),
          )
        );
      }
    }

    // Filter the changes event; can be used to add custom fields or whatever
    if ( ncChangeLogUtils::getEventDispatcher() )
    {
      $event = new sfEvent($object, $tableMap->getName() . '.nc_filter_changelog');
      ncChangeLogUtils::getEventDispatcher()->filter($event, $diff);

      $diff = $event->getReturnValue();
    }

    if (empty($diff['changes']))
    {
      // it's possible that only ignored fields were modified, in which case do nothing
      $object->changelogEntry = null;
      return false;
    }

    $object->changelogEntry->setChangesDetail($diff);

    return $object->changelogEntry;
  }


}
