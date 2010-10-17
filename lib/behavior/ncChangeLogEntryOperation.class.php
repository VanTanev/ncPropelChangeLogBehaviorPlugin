<?php

/**
* A class describing the different operation types
*/
class ncChangeLogEntryOperation
{
  // shorthands!
  const
    INSERTION       = 1,
    UPDATE          = 2,
    DELETION        = 3,
    CUSTOM_MESSAGE  = 4;

  const
    NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION       = 1,
    NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE          = 2,
    NC_CHANGE_LOG_ENTRY_OPERATION_DELETION        = 3,
    NC_CHANGE_LOG_ENTRY_OPERATION_CUSTOM_MESSAGE  = 4;

  protected static
    $_types = array(
      self::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION       => 'Insertion',
      self::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE          => 'Update',
      self::NC_CHANGE_LOG_ENTRY_OPERATION_DELETION        => 'Deletion',
      self::NC_CHANGE_LOG_ENTRY_OPERATION_CUSTOM_MESSAGE  => 'Custom Message (use $adapter->render() to view)',
    );

  public static function getTypes()
  {
    return self::$_types;
  }

  public static function getStringFor($type_index)
  {
    if (array_key_exists($type_index, self::$_types))
    {
      return self::$_types[$type_index];
    }

    return null;
  }

  public static function getI18NStringFor($type_index)
  {
    if (( $type_string = self::getStringFor($type_index) ))
    {
      return ncChangeLogUtils::translate($type_string);
    }

    return null;
  }
}
