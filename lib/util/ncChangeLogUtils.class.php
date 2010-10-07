<?php
  
class ncChangeLogUtils
{
  
  /**
   * Generic translation function
   * 
   * @param       string $string
   * @param       array $params
   * @param       string $catalogue
   * @return      string
   */
  public static function translate($string, $params = array(), $catalogue = 'nc_change_log_behavior')
  {
    if (ncChangeLogConfigHandler::isI18NActive() && sfContext::hasInstance())
    {
      sfContext::getInstance()->getConfiguration()->loadHelpers('I18N');
      return __($string, $params, $catalogue);
    }

    return $string;
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
  * 
  * @param        mixed $primary_key
  * @return       string
  */
  public static function normalizePK($primary_key)
  {
    return is_array($primary_key) ? (count($primary_key) > 1 ? implode('-', $primary_key) : array_pop($primary_key)) : $primary_key;
  }
  
}
