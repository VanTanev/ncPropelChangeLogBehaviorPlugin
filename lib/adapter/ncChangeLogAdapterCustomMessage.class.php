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
  
  public function renderMessage()
  {
    $data = $this->entry->getChangesDetail();
    
    return $data['message'];
  }
  
  
  public function renderList($url = null) {}

}
