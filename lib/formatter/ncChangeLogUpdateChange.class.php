<?php

class ncChangeLogUpdateChange
{
  const
    TYPE_VALUE_ADD      = 'VALUE.ADDITION',
    TYPE_VALUE_REMOVE   = 'VALUE.REMOVAL',
    TYPE_VALUE_UPDATE   = 'VALUE.UPDATE',
    TYPE_BOOLEAN_SET    = 'BOOLEAN.SET',
    TYPE_BOOLEAN_UNSET  = 'BOOLEAN.UNSET';

  /** @var ncChangeLogAdapter */
  public $adapter;

  public
    $fieldName,
    $oldValue,
    $newValue,
    $columnType,
    $columnMap;

  protected $filtered_vars;


  public function __construct($fieldName, $oldValue, $newValue, $updateAdapter, $columnType = null)
  {
    $this->fieldName  = $fieldName;
    $this->oldValue   = $oldValue;
    $this->newValue   = $newValue;
    $this->adapter    = $updateAdapter;
    $this->columnType = $columnType;
  }


  public function __toString()
  {
    return $this->render();
  }


  /**
   * Returns a propel column type or null if cannot fetch it.
   * Available column types are:

      const CHAR = "CHAR";
      const VARCHAR = "VARCHAR";
      const LONGVARCHAR = "LONGVARCHAR";
      const CLOB = "CLOB";
      const NUMERIC = "NUMERIC";
      const DECIMAL = "DECIMAL";
      const TINYINT = "TINYINT";
      const SMALLINT = "SMALLINT";
      const INTEGER = "INTEGER";
      const BIGINT = "BIGINT";
      const REAL = "REAL";
      const FLOAT = "FLOAT";
      const DOUBLE = "DOUBLE";
      const BINARY = "BINARY";
      const VARBINARY = "VARBINARY";
      const LONGVARBINARY = "LONGVARBINARY";
      const BLOB = "BLOB";
      const DATE = "DATE";
      const TIME = "TIME";
      const TIMESTAMP = "TIMESTAMP";

      const BU_DATE = "BU_DATE";
      const BU_TIMESTAMP = "BU_TIMESTAMP";

      const BOOLEAN = "BOOLEAN";

   *
   */
  public function getColumnType()
  {
    if (is_null($this->columnType))
    {
      if ($columnMap = $this->getColumnMap())
      {
        $this->columnType = $columnMap->getType();
      }
    }

    return $this->columnType;
  }

  /**
  * Return the columnMap of the field that this change represents
  *
  * @return ColumnMap
  */
  public function getColumnMap()
  {
    if (is_null($this->columnMap))
    {
      $tableMap = $this->adapter->getTableMap();

      if ( $tableMap && $tableMap->containsColumn($this->getFieldName()) )
      {
        $this->columnMap = $tableMap->getColumn($this->getFieldName());
      }
    }

    return $this->columnMap;
  }

  protected function createEvent()
  {
    return new sfEvent(
      $this,
      $this->adapter->getTableName().'.render_'.$this->getFieldName(),
      array('fieldName' => $this->getFieldName(), 'tableName' => $this->adapter->getTableName(), 'fieldType' => $this->getColumnType())
    );
  }

  protected function createGlobalEvent()
  {
    return new sfEvent(
      $this,
      'ncChangeLog.render',
      array('fieldName' => $this->getFieldName(), 'tableName' => $this->adapter->getTableName(), 'fieldType' => $this->getColumnType())
    );
  }

  protected function getForeignValue($value, $method = '__toString')
  {
    if (ncChangeLogConfigHandler::getForeignValues() && $this->isForeignKey())
    {
      $tableMap = $this->adapter->getTableMap();
      $columnMap = $this->getColumnMap();

      // this will initialize the relations if they are not available yet
      $tableMap->getRelations();

      $relatedObjectClass     = $columnMap->getRelatedTable()->getClassname();
      $relatedObjectPeerClass = constant($relatedObjectClass . '::PEER');

      if (class_exists($relatedObjectPeerClass))
      {
        $object = call_user_func(array($relatedObjectPeerClass, 'retrieveByPK'), $value);
        return method_exists($object, $method) ? $object->$method() : $value;
      }
    }

    return $value;
  }

  public function isForeignKey()
  {
    if ($columnMap = $this->getColumnMap())
    {
      return $columnMap->isForeignKey();
    }

    return false;
  }

  /**
   * Retrieves the name of the field that have changed
   *
   * @return String
   */
  public function getFieldName()
  {
    return $this->fieldName;
  }

  public function getChangeType()
  {
    if (is_null($this->getOldValue()) || (strlen($this->getOldValue()) == 0))
    {
      return PropelColumnTypes::BOOLEAN == $this->getColumnType() ? self::TYPE_BOOLEAN_SET : self::TYPE_VALUE_ADD;
    }
    elseif (is_null($this->getNewValue()) || (strlen($this->getNewValue()) == 0))
    {
      return PropelColumnTypes::BOOLEAN == $this->getColumnType() ? self::TYPE_BOOLEAN_UNSET : self::TYPE_VALUE_REMOVE;
    }
    else
    {
      return self::TYPE_VALUE_UPDATE;
    }
  }


  protected function getValue($value)
  {
    $hash = md5(serialize($value));

    if ( ! isset($this->filtered_vars[$hash]))
    {
      $res   = $value;
      $event = null;

      if (ncChangeLogConfigHandler::fireFieldFormattingEvents())
      {
        $globalEvent = $this->createGlobalEvent();
        ncChangeLogUtils::getEventDispatcher()->filter($globalEvent, $value);
        $res = $globalEvent->getReturnValue();

        $event = $this->createEvent();
        ncChangeLogUtils::getEventDispatcher()->filter($event, $res);
        $res = $event->getReturnValue();
      }

      if (is_null($event) || (!$event->isProcessed() && !empty($value) && $this->isForeignKey()))
      {
        $res = $this->getForeignValue($value);
      }

      $this->filtered_vars[$hash] = $res;
    }


    return $this->filtered_vars[$hash];
  }

  /**
   * Retrieves the old value of the field that changed.
   *
   * @return String
   */
  public function getOldValue()
  {
    return $this->getValue($this->oldValue);
  }

  /**
   * Retrieves the new value of the field that changed.
   *
   * @return String
   */
  public function getNewValue()
  {
    return $this->getValue($this->newValue);
  }


  public function getClassName()
  {
    return $this->adapter->getClassName();
  }

  public function getPeerClassName()
  {
    return $this->adapter->getPeerClassName();
  }

  /**
   * Retrieves the translated name of the field that changed.
   *
   * @return String
   */
  public function renderFieldName()
  {
    $translateFieldName = array($this->getClassName(), ncChangeLogConfigHandler::getFieldNameTranslationMethod());

    if (is_callable($translateFieldName))
    {
      $translatedFieldName = call_user_func($translateFieldName, $this->getFieldName());
    }

    // in case the translateFieldName method returned the same data, we try to use i18n translation
    return $translatedFieldName == $this->getFieldName() ? $this->adapter->translate($this->getFieldName()) : $translatedFieldName;
  }

  /**
   * Uses the formatter 'formatUpdateChange' method to render this change.
   *
   * @return String
   */
  public function render()
  {
    return $this->adapter->getFormatter()->formatUpdateChange($this);
  }
}
