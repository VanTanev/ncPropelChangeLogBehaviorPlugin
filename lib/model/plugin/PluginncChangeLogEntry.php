<?php

class PluginncChangeLogEntry extends BasencChangeLogEntry
{
  
  /** @var BaseObject The object for which this ChangeLog applies */
  protected $object;
  
  /** @var TableMap The table map for the object related to this Changelog */
  protected $tableMap;
  
  
  public function __toString()
  {
    return $this->getOperationString()." at ".$this->getCreatedAt();
  }
  
  
  /**
   * Create a new ncChangeLogEntry;
   * optionally set the related object
   * 
   * @param     mixed $object
   * @return    ncChangeLogEntry
   */
  public function __construct($object = null)
  {
    if (!is_null($object))
    {
      $this->setObject($object);
    }
    
    return parent::__construct(); // just in case we decide to set defaults in the future
  }
  

  /**
   * Answer whether this entry's opertion_type attribute equals $type_index.
   * 
   * @param     integer $type_index
   * @return    boolean
   * 
   * @see       PluginncChangeLogEntryOperation
   */
  public function isOperation($type_index)
  {
    return $this->getOperationType() === $type_index;
  }

  
  /**
   * Answer a string representing this entry's operation.
   *
   * @return    string
   */
  public function getOperationString()
  {
    return ncChangeLogEntryOperation::getStringFor($this->getOperationType());
  }
  

  /**
   * Set the object PK (it will be stringified if necessary)
   * 
   * @param     integer|array $v
   * 
   * @see       PluginncChangeLogEntry::setRawObjectPK()
   */
  public function setObjectPK($v)
  {
    return $this->setRawObjectPK(ncChangeLogUtils::normalizePK($v));
  }
  
  
  /**
   * A method to set the RAW object PK value
   * 
   * @param     mixed $v
   * @return    ncChangeLogEntry
   */
  public function setRawObjectPK($v)
  {
    return parent::setObjectPK($v);
  }
  
  
  /**
   * Get the object PK, as it was originally saved
   * 
   * @return    integer|array
   * 
   * @see       PluginncChangeLogEntry::getRawObjectPK()
   */
  public function getObjectPK()
  {
    $pk = $this->getRawObjectPK();
    return false !== strpos($pk, '-') ? explode('-', $pk) : $pk;
  }
  
  
  /**
   * A method to get the RAW object PK value
   * 
   * @return    string
   */
  public function getRawObjectPK()
  {
    return parent::getObjectPK();
  }
  
  
  /**
   * Set the object for which this change log applies
   * 
   * @param     BaseObject $object
   * @return    ncChangeLogEntry
   */
  public function setObject(BaseObject $object)
  {
    $this->object = $object;
    
    $this->setClassName(get_class($object));
    if (!$object->isNew())
    {
      $this->setObjectPK($object->getPrimaryKey());
    }
    
    return $this;
  }
  
  
  /**
   * Try to retrieve this entry's related object.
   * 
   * The object is stored as a property, to avoid having to retrieve it again
   * on subsequent calls to this method
   *
   * @return    BaseObject|null
   */
  public function getObject()
  {
    if (is_null($this->object))
    {
      $peer_class = $this->getObjectPeerClassName();
      
      if (class_exists($peer_class))
      {
        $this->object = call_user_func_array(array($peer_class, 'retrieveByPK'), is_array($this->getObjectPk()) ? $this->getObjectPk() : array($this->getObjectPk()));
      }
    }
    
    return $this->object;
  }
  
  
  /**
  * Manually set the table map for the encapsulated object
  * 
  * @param TableMap $map
  *
  * @return ncChangeLogEntry
  */
  public function setTableMap(TableMap $map)
  {
    $this->tableMap = $map;
    
    return $this;
  }
  
  
  /**
  * Get the table map for the encapsulated object
  * 
  * @return TableMap
  */
  public function getTableMap()
  {
    if (is_null($this->tableMap))
    {
      if (class_exists($this->getObjectPeerClassName()))
      {
        $this->tableMap = call_user_func(array($this->getObjectPeerClassName(), 'getTableMap'));
      }
    }
    
    return $this->tableMap;
  }
  
  
  /**
   * Sets the local object property to null; Does not modify the DB
   * 
   * @return    ncChangeLogEntry
   */
  public function clearObject()
  {
    $this->object = null;
    
    return $this;
  }

  
  /**
   * A method to set the RAW change detail value
   * 
   * @param     mixed $v
   * @return    ncChangeLogEntry
   */
  public function setRawChangesDetail($v)
  {
    return parent::setChangesDetail($v);
  }
  
  
  /**
   * A method to get the RAW change detail value
   */
  public function getRawChangesDetail()
  {
    return parent::getChangesDetail();
  }
  
  
  /**
   * Returns the changes detail, in the original format it was set
   * 
   * @return    array
   */
  public function getChangesDetail()
  {
    return unserialize(base64_decode($this->getRawChangesDetail()));
  }
  
  
  /**
   * Sets the changes dtetal, converting them to base64 encoded string for the DB
   * 
   * @param     array $v
   * @return    ncChangeLogEntry
   */
  public function setChangesDetail($v)
  {
    return $this->setRawChangesDetail(base64_encode(serialize($v)));
  }

  
  /**
   * Retrieves the changed class name
   * 
   * kept for BC
   */
  public function getObjectClassName()
  {
    return $this->getClassName();
  }

  
  /**
   * Retrieves the changed peer class name
   */
  public function getObjectPeerClassName()
  {
    $const = $this->getObjectClassName() . '::PEER';
    
    return defined($const) ? constant($const) : null;
  }

  
  /**
   * Retrieves the changed table name
   */
  public function getObjectTableName()
  {
    $const = $this->getObjectPeerClassName() . '::TABLE_NAME';
    
    return defined($const) ? constant($const) : null;
  }

  
  /**
   * Retrieved the primary key of the changed object
   * 
   * kept for BC
   */
  public function getObjectPrimaryKey()
  {
    return $this->getObjectPK();
  }

  
  /**
   * Retrieve the list of changes as an array with each value equal to an array of ('old' => string, 'new' => string, 'field' => string, 'raw' => array('old'/'new))
   * 
   * @return   array
   */
  public function getObjectChanges()
  {
    if ($this->isOperation(ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE))
    {
      $changeLog = $this->getChangesDetail();
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
   * 
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

      case ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION:
        return new ncChangeLogAdapterInsertion($this);

      case ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_DELETION:
        return new ncChangeLogAdapterDeletion($this);
        
      case ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_CUSTOM_MESSAGE:
        return new ncChangeLogAdapterCustomMessage($this);        

      default:
        return null;
    }
  }

}
