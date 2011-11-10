<?php

class ncChangeLogRouting
{
  /**
   * Listens to the routing.load_configuration event.
   *
   * @param sfEvent An sfEvent instance
   */
  static public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $r = $event->getSubject();

    // preprend our routes
    $r->prependRoute('nc_change_log_detail', new sfRoute('/change_log/show/:id', array('module' => 'ncChangeLogEntry', 'action' => 'show')));
    $r->prependRoute('nc_change_log', new sfRoute('/change_log/:class/:pk', array('module' => 'ncChangeLogEntry', 'action' => 'index')));
  }
}