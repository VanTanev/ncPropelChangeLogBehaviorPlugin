<?php

/**
 * This class is the base class for the adapters for each 
 * type of operation.
 *
 * These classes allows to access each type operation in a uniform way.
 */
abstract class ncChangeLogAdapter extends ArrayObject
{
  /** @var ArrayIterator */
  protected $iterator;
    
  /** @var ncChangeLogEntry  */
  protected $entry;  

  /** @var ncChangeLogEntryFormatter */
  protected $formatter;
  /**
   * Constructor
   *
   * @param ncChangeLogEntry The entry to adapt.
   */
  public function __construct(ncChangeLogEntry $entry)
  {
    $this->entry    = $entry;
    $this->exchangeArray($this->getChangeLog());
  }
  
  
  public function __toString()
  {
    return $this->render();
  }

  
  /**
  * Overrides ArrayObject::getIterator() to return always the same iterator object
  * instead of a new instance for each call
  */
  public function getIterator()
  {
    if (null === $this->iterator) {
      $this->iterator = new ArrayIterator($this);
    }
    return $this->iterator;
  }
    

  /*********************************
   * ArrayAccess interface Methods *
   ********************************/
  public function offsetGet($index)
  {
    if (!$this->offsetExists($index))
    {
      throw new InvalidArgumentException(sprintf('Change "%s" does not exist.', $index));
    }

    return parent::offsetGet($index);
  }

  public function offsetSet($index, $newval)
  {
    throw new LogicException('Cannot update changes.');
  }

  public function offsetUnset($index)
  {
    throw new LogicException('Cannot unset changes.');
  }


  /************************
   *      Own methods     *
   ***********************/

  /**
   * Retrieves the changes
   *
   * @return array The changes
   */
  public function getChangeLog()
  {
    if (0 == count($this))
    {
      $this->createChangeLog();
    }
    
    return $this->getArrayCopy();
  }


  protected function createChangeLog()
  {
    $this->exchangeArray($this->getChanges()); 
  }
  

  /**
   * Retrieves the affected class name.
   *
   * @return String The affected class name
   */
  public function getClassName()
  {
    return $this->entry->getObjectClassName();
  }
  
  
  public function getPeerClassName()
  {
    return $this->entry->getObjectPeerClassName();
  }

  /**
   * Retrieves the affected table name.
   *
   * @return String The affected table name
   */
  public function getTableName()
  {
    return $this->entry->getObjectTableName();
  }

  /**
   * Retrieves the affected object's primary key.
   *
   * @return String The affected object's primary key.
   */
  public function getPrimaryKey()
  {
    return $this->entry->getObjectPrimaryKey();
  }

  /**
   * Retrieves the changes
   *
   * @return Array The affected changes
   */
  protected function getChanges()
  {
    return $this->entry->getObjectChanges();
  }

  /**
   * Return the related entry
   * 
   * @return ncChangeLogEntry
   */
  public function getEntry()
  {
    return $this->entry;
  }


  /**
   * Return the related entry
   * 
   * @return BaseObject
   */
  public function getObject()
  {
    return $this->entry->getObject();
  }
  
  
  /**
  * Return the table map for the encapsulated object in the entry
  * 
  * @return TableMap
  */
  public function getTableMap()
  {
    return $this->entry->getTableMap();
  }
 

  /**************************
   *        Format!         *
   **************************/
   
  /**
   * Returns a new instance of the formatter class
   * 
   * @return ncChangeLogEntryFormatter
   */
  public function getFormatter()
  {
    if (is_null($this->formatter))
    {
      $formatterClass = ncChangeLogConfigHandler::getFormatterClass();
      $this->formatter = new $formatterClass();
    }
    
    return $this->formatter;
  }
  

  /**
   * Retrieves the HTML representation of the class name.
   * It may transform the class name values (eg. translation)
   *
   * @return String HTML representation of the className.
   */
  public function renderClassName()
  {
    return $this->translate($this->getClassName());
  }


  /**
   * Retrieves the formatted date of the ChangeLogEntry
   * It may transform the created at value (eg. translation)
   *
   * @param string The format for the date; defaults to datetime format
   * 
   * @return String HTML representation of the date
   */
  public function renderCreatedAt($format = null)
  {
    return $this->entry->getCreatedAt(is_null($format) ? ncChangeLogConfigHandler::getDateTimeFormat() : $format);
  }

  
  /**
   * Retrieves the formatted username of the ChangeLogEntry
   *
   * @return String HTML representation of the username
   */
  public function renderUsername()
  {
    return $this->entry->getUsername();
  }


  /**
   * Retrieves the formatted operation name of the ChangeLogEntry
   *
   * @return String HTML representation of operation name
   */
  public function renderOperationType()
  {
    return ncChangeLogUtils::translate(ncChangeLogEntryOperation::getStringFor($this->entry->getOperationType()));
  }
  

  /**
   * Retrieves the HTML representation of the changes
   *
   * @return String HTML representation of the changes.
   */
  abstract public function render();
  

  /**
   * Retrieves the HTML representation
   * to be shown in a ncChangeLogEntry listing
   * 
   * @return String HTML representation of the listing.
   */
  abstract public function renderList($url = null);

  
  /*************************
   *      Translation      *
   ************************/

  /**
   * Retrieves a translated string using the table's catalogue.
   *
   * @param String $tableName The table's name. (The catalogue name)
   * @param String $string The string to translate
   *
   * @return String
   */
  public function tableTranslate($tableName, $string)
  {
    return ncChangeLogUtils::translate($string, null, 'tables/' . $tableName);
  }


  /**
   * Retrieves a translated string usign the table's catalogue but
   * using the className.
   *
   * @param String $className The className used to obtain the table's name. (The catalogue name)
   * @param String $string The string to translate
   *
   * @return String
   */
  public function classTranslate($className, $string)
  {
    $peerClass = constant($className.'::PEER');
    $tableName = constant($className.'::TABLE_NAME');
    return $this->tableTranslate($tableName, $string);
  }


  /**
   * Translates strings using this adapter's catalogue.
   *
   * @param $string String String to translate
   *
   * @return String
   */
  public function translate($string)
  {
    return $this->tableTranslate($this->getTableName(), $string);
  }

}
