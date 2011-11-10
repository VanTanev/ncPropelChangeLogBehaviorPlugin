<?php

class ncChangeLogUtils
{
  /** @var sfEventDispatcher */
  private static $dispatcher;

  /** @var sfI18n */
  private static $I18N;

  /**
   * Generic translation function
   *
   * @param       string $string
   * @param       array $args
   * @param       string $catalogue
   * @return      string
   */
  public static function translate($string, $args = array(), $catalogue = 'nc_change_log_behavior')
  {
    if (is_null(self::$I18N) && ncChangeLogConfigHandler::isI18NActive())
    {
      self::$I18N = sfConfig::get('sf_i18n') && sfContext::hasInstance() ? sfContext::getInstance()->getI18N() : false;
    }

    return self::$I18N ? self::$I18N->__($string, $args, $catalogue) : strtr($string, (array) $args);
  }


  /**
  * Retrieves the active user's name; fallsback to config value if no context exists
  *
  * @return       string
  */
  public static function getUsername()
  {
    if (sfContext::hasInstance())
    {
      $user   = sfContext::getInstance()->getUser();
      $method = ncChangeLogConfigHandler::getUsernameMethod();

      if (method_exists($user, $method))
      {
        return $user->$method();
      }
    }

    // Use a default username.
    return ncChangeLogConfigHandler::getUsernameCli();
  }


  /**
   * Extract the value method and the required parameters for it, for given a ColumnMap's type.
   * Return an Array holding the value method as first value and its parameters as the second one.
   *
   * @param       ColumnMap $column
   * @return      array ($value_method, $params)
   */
  static public function extractValueMethod(ColumnMap $column)
  {
    $value_method = 'get' . $column->getPhpName();
    $params = null;

    if (in_array($column->getType(), array(PropelColumnTypes::BU_DATE, PropelColumnTypes::DATE)))
    {
      $params = ncChangeLogConfigHandler::getDateFormat();
    }
    elseif (in_array($column->getType(), array(PropelColumnTypes::BU_TIMESTAMP, PropelColumnTypes::TIMESTAMP)))
    {
      $params = ncChangeLogConfigHandler::getDateTimeFormat();
    }
    elseif ($column->getType() == PropelColumnTypes::TIME)
    {
      $params = ncChangeLogConfigHandler::getTimeFormat();
    }

    return array($value_method, $params);
  }


  /**
  * Normalizes primary keys regardless of type
  *
  * 123               => "123"
  * array(123)        => "123"
  * array(123, 456)   => "123-456"
  * BaseObject $o     => PK
  *
  * @param        mixed $primary_key
  * @return       string
  */
  public static function normalizePK($primary_key)
  {
    if ($primary_key instanceof BaseObject)
    {
      $primary_key = $primary_key->getPrimaryKey();
    }

    return is_array($primary_key) ? (count($primary_key) > 1 ? implode('-', $primary_key) : array_pop($primary_key)) : $primary_key;
  }


  /**
   * Tries to get the event dispatcher; returns null if not successfull
   *
   * @return sfEventDispatcher
   */
  public static function getEventDispatcher()
  {
    if (is_null(self::$dispatcher) && sfContext::hasInstance())
    {
      self::$dispatcher = sfContext::getInstance()->getEventDispatcher();
    }

    return self::$dispatcher;
  }


  /**
   * Get the route for a specific object that has a changelog
   *
   * @param mixed $object
   * @return String
   */
  public function getChangeLogRoute(BaseObject $object)
  {
    return '@nc_change_log?class='.get_class($object).'&pk='.ncChangeLogUtils::normalizePK($object);
  }



}
