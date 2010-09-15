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
  
}
