<?php

class ncChangeLogAdapterDeletion extends ncChangeLogAdapter
{

  /**
   * Retrieves the HTML representation of the changes
   *
   * @return String HTML representation of the changes.
   */
  public function render()
  {
    return $this->getFormatter()->formatDeletion($this);
  }


  /**
   * Retrieves the HTML representation
   * to be shown in a ncChangeLogEntry listing
   */
  public function renderList($url = null)
  {
    return $this->getFormatter()->formatListDeletion($this, $url);
  }

}
