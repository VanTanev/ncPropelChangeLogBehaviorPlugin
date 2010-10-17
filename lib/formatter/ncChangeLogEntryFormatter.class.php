<?php
/**
 * class ncChangeLogEntryFormatter.
 *
 * @author ncuesta
 */
class ncChangeLogEntryFormatter
{
  protected
    /** @var string available placeholders: %field_name% * %old_value% * %new_value% */
    $valueUpdateFormat   = "Value of field '%field_name%' changed from '%old_value%' to '%new_value%'.",
    /** @var string available placeholders: %field_name% * %new_value% */
    $valueAdditionFormat = "Value of field '%field_name%' was set to '%new_value%'. It had no value set before.",
    /** @var string available placeholders: %field_name% * %old_value% */
    $valueRemovalFormat  = "Value of field '%field_name%' was unset. It's previous value was '%old_value%'.",
    /** @var string available placeholders: %object_name% * %pk% * %date% * %username% */
    $insertionFormat     = "A new %object_name% has been created and it has been given the primary key '%pk%' at %date% by %username%.",
    /** @var string available placeholders: %object_name% * %pk% * %date% * %username% */
    $deletionFormat      = "The %object_name% with primary key '%pk%' has been deleted at %date% by %username%.",
    /** @var string available placeholders: %field_name% */
    $booleanSetFormat    = "The boolean field '%field_name%' was set.",
    /** @var string available placeholders: %field_name% */
    $booleanUnsetFormat  = "The boolean field '%field_name%' was unset.",
    /** @var string available placeholders: %operation% * %date% */
    $listEntryFormat     = "%operation% at %date%";
    

  /**
   * Format string that should be call before the
   * renderization of an ncChangeLogEntry
   *
   * @returns String formatting text
   */
  public function formatStart()
  {
    return '';
  }


  /**
   * Format string that should be call after the
   * renderization of an ncChangeLogEntry
   *
   * @returns String formatting text
   */
  public function formatEnd()
  {
    return '';
  }


  /**
   * Format an insertion operation.
   *
   * @param ncChangeLogAdapter
   * @return String HTML representation of an Insertion
   */ 
  public function formatInsertion(ncChangeLogAdapter $adapter)
  {
    return str_replace(
      array('%object_name%', '%pk%', '%date%', '%username%'),
      array($adapter->renderClassName(), $adapter->getPrimaryKey(), $adapter->renderCreatedAt(), $adapter->renderUsername()),
      ncChangeLogUtils::translate($this->insertionFormat)
    );
  }


  /**
   * Format an update operation.
   *
   * @param ncChangeLogAdapter
   * @return String HTML representation of an Update
   */
  public function formatUpdate(ncChangeLogAdapter $adapter, $separator = PHP_EOL)
  {
    return implode($separator, array_map(create_function('$ncChangeLogUpdateChange', 'return $ncChangeLogUpdateChange->render();'), $adapter->getArrayCopy()));
  }


  /**
   * Format a deletion operation.
   *
   * @param ncChangeLogAdapter
   * @return String HTML representation of a deletion
   */
  public function formatDeletion(ncChangeLogAdapter $adapter)
  {
    return str_replace(
      array('%object_name%', '%pk%', '%date%', '%username%'),
      array($adapter->renderClassName(), $adapter->getPrimaryKey(), $adapter->renderCreatedAt(), $adapter->renderUsername()),
      ncChangeLogUtils::translate($this->deletionFormat)
    );
  }


  /**
   * Formats a 'change' in an update operation
   * Return the string format representation.
   *
   * @param Array $params
   * @return String
   */
  public function formatUpdateChange(ncChangeLogUpdateChange $change)
  {
    if (is_null($change->getOldValue()) || (strlen($change->getOldValue()) == 0))
    {
      $format = PropelColumnTypes::BOOLEAN == $change->getColumnType() ? $this->booleanSetFormat : $this->valueAdditionFormat;
    }
    elseif (is_null($change->getNewValue()) || (strlen($change->getNewValue()) == 0))
    {
      $format = PropelColumnTypes::BOOLEAN == $change->getColumnType() ? $this->booleanUnsetFormat : $this->valueRemovalFormat;
    }
    else
    {
      $format = $this->valueUpdateFormat;
    }

    return str_replace(
      array('%field_name%', '%old_value%', '%new_value%'),
      array($change->renderFieldName(), $change->getOldValue(), $change->getNewValue()),
      ncChangeLogUtils::translate($format)
    );
  }


  /**
   * Used to output the starting HTML code of a list of changes
   *
   * @returns String
   */
  public function formatListStart()
  {
    return '';
  }


  /**
   * Used to output the ending HTML code of a list of changes
   *
   * @returns String
   */
  public function formatListEnd()
  {
    return '';
  }


  /**
   * Outputs the html representation of a single operation
   *
   * @param ncChangeLogAdapter
   * @return String
   */
  protected function formatList($adapter)
  {
    return str_replace(
      array('%operation%', '%date%'),
      array($adapter->renderOperationType(), $adapter->renderCreatedAt()),
      ncChangeLogUtils::translate($this->listEntryFormat)
    );
  }


  /**
   * Outputs the html representation of a single insertion operation
   * in a listing
   *
   * @param ncChangeLogAdapterInsertion $adapter
   * @param String url of the link to the 'show' action
   */
  public function formatListInsertion($adapter, $url)
  {
    return $this->formatList($adapter);
  }


  /**
   * Outputs the html representation of a single update operation
   * in a listing
   *
   * @param ncChangeLogAdapterInsertion $adapter
   * @param String url of the link to the 'show' action
   */
  public function formatListUpdate($adapter, $url)
  {
    return $this->formatList($adapter);
  }


  /**
   * Outputs the html representation of a single deletion operation
   * in a listing
   *
   * @param ncChangeLogAdapterInsertion $adapter
   * @param String url of the link to the 'show' action
   */
  public function formatListDeletion($adapter, $url)
  {
    return $this->formatList($adapter);
  }

}
