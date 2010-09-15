<?php

class ncChangeLogEntry extends BasencChangeLogEntry
{
  /**
   * Answer whether this entry's opertion_type attribute equals $type_index.
   * @see ncChangeLogEntryOperation
   * 
   * @param integer $type_index
   * @return Boolean
   */
  public function isOperation($type_index)
  {
    return $this->getOperationType() === $type_index;
  }

  
  public function __toString()
  {
    return $this->getOperationString()." at ".$this->getCreatedAt();
  }

  
  /**
   * Answer a string representing this entry's operation.
   *
   * @return String
   */
  public function getOperationString()
  {
    return ncChangeLogEntryOperation::getStringFor($this->getOperationType());
  }
  

  /**
   * Set the object PK (it will be stringified if necessary)
   * 
   * @param     integer|array $v
   */
  public function setObjectPK($v)
  {
    $v = is_array($v) ? implode('-', $v) : $v;
    return parent::setObjectPK($v);
  }
  
  
  /**
   * Get the object PK, as it was originally saved
   * 
   * @return    integer|array
   */
  public function getObjectPk()
  {
    $pk = parent::getObjectPk();
    return false !== strpos($pk, '-') ? explode('-', $pk) : $pk;
  }
  
  
  /**
   * Try to retrieve this entry's related object.
   *
   * @return mixed
   */
  public function getObject()
  {
    $peer_class = $this->getObjectPeerClassName();
    
    if (class_exists($peer_class))
    {
      return call_user_func_array(array($peer_class, 'retrieveByPK'), is_array($this->getObjectPk()) ? $this->getObjectPk() : array($this->getObjectPk()));
    }

    return null;
  }

  
  /**
   * Returns the array of changes
   */
  public function getChangesDetailArray()
  {
    return unserialize(base64_decode($this->getChangesDetail()));
  }

  
  /**
   * Retrieves the changed class name
   */
  public function getObjectClassName()
  {
    $changeLog = $this->getChangesDetailArray();
    return $changeLog['class'];
  }

  
  /**
   * Retrieves the changed peer class name
   */
  public function getObjectPeerClassName()
  {
    return constant($this->getObjectClassName().'::PEER');
  }

  
  /**
   * Retrieves the changed table name
   */
  public function getObjectTableName()
  {
    return constant($this->getObjectPeerClassName().'::TABLE_NAME');
  }

  
  /**
   * Retrieved the primary key of the changed object
   */
  public function getObjectPrimaryKey()
  {
    $changeLog = $this->getChangesDetailArray();
    return $changeLog['pk'];
  }

  
  /**
   * Retrieve the list of changes as an array with each value equal to an array of ('old' => string, 'new' => string, 'field' => string, 'raw' => array())
   */
  public function getObjectChanges()
  {
    if ($this->isOperation(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE))
    {
      $changeLog = $this->getChangesDetailArray();
      return $changeLog['changes'];
    }
    else
    {
      return array();
    }
  }

  
  /**
   * Retrieve an array with the following structure
   *  array(
   *    'related_column_name' => array ( ncChangeLogEntries...)
   *    );
   * @return mixed An array of the ncChangeLogEntries of the related columns.
   */
  public function getRelatedTablesChangeLogEntries()
  {
    return ncChangeLogEntryPeer::getRelatedTablesChangeLog($this->getObject());
  }

  
  /**
   * Retrieves an adapter that represents an insertion/update/deletion 
   * operation in a uniform way.
   *
   * @return ncChangeLogAdapter
   */
  public function getAdapter()
  {
    switch ($this->getOperationType())
    {
      case ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE:
        return new ncChangeLogAdapterUpdate($this);
        break;
      case ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION:
        return new ncChangeLogAdapterInsertion($this);
        break;
      case ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_DELETION:
        return new ncChangeLogAdapterDeletion($this);
        break;
      default:
        return null;
    }
  }
}
