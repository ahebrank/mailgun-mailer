<?php

class MailgunMailerLogger {
  
  var $log_table = 'mailgun_submissions';
  var $enabled = TRUE;
  
  function __construct() {
    // check / create log table
    $this->checktable();
  }
  
  private function checktable() {
    if (!ee()->db->table_exists($this->log_table)) {
      ee()->load->dbforge();
      $fields = array(
        'url' => array(
          'type' => 'VARCHAR',
          'constraint' => '255'
        ),
        'success' => array(
          'type' => 'TINYINT',
          'default' => 0,
        ),
        'submitted_at' => array(
          'type' => 'INT',
        ),
        'data' => array(
          'type' => 'TEXT',
        ),
      );
      ee()->dbforge->add_field('id');
      ee()->dbforge->add_field($fields);
      ee()->dbforge->create_table($this->log_table);
    }
  }
  
  function log($data, $url = null, $success = TRUE) {
    if (!$this->enabled) return;
    
    if (is_null($url)) {
      $url = $_SERVER['REQUEST_URI'];
    }
    
    $logdata = array(
      'url' => $url,
      'submitted_at' => time(),
      'data' => json_encode($data),
      'success' => (int)$success,
    );
    
    return ee()->db->insert($this->log_table, $logdata);
  }
  
  function disable() {
    $this->enabled = FALSE;
  }
  function enable() {
    $this->enabled = TRUE;
  }
  
}
