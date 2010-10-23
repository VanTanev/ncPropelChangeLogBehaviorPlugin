<?php

class ncChangeLogAdapterCustomMessage extends ncChangeLogAdapter
{

  /**
   * Retrieves the HTML representation of the changes
   *
   * @return String HTML representation of the changes.
   */
  public function render()
  {
    return $this->getFormatter()->formatCustomMessage($this);
  }
  
  
  /**
  * Returns the custom message
  * 
  * @return string
  */
  public function renderMessage()
  {
    $data = $this->entry->getChangesDetail();
    
    return $data['message'];
  }
  
  
  /**
  * Returns the Custom Message; 
  * if you need the operaiton type use getOperationType
  * 
  * @return string
  */
  public function renderOperationType()
  {
    return $this->renderMessage();
  }
  
  
  /**
  * Empty method; required by the base abstract class, but makes no sense here
  * 
  */
  public function renderList($url = null) {}

}
