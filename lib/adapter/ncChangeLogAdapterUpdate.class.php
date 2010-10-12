<?php

class ncChangeLogAdapterUpdate extends ncChangeLogAdapter
{

  protected function createChangeLog()
  {
    $changeLog = array();
    
    foreach ($this->getChanges() as $field_name => $value)
    {
      $changeLog[$field_name] = new ncChangeLogUpdateChange($value['field'], $value['old'], $value['new'], $this);
    }
    
    $this->exchangeArray($changeLog); 
  }


  /**
   * Retrieves the HTML representation of the changes
   *
   * @return String HTML representation of the changes.
   */
  public function render()
  {
    return $this->getFormatter()->formatUpdate($this);
  }


  /**
   * Retrieves the HTML representation
   * to be shown in a ncChangeLogEntry listing
   */
  public function renderList($url = null)
  {
    return $this->getFormatter()->formatListUpdate($this, $url);
  }

}
