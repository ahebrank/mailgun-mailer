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

  function getcols() {
    $results = ee()->db->select('*')
              ->get($this->log_table);
    $overall_row = array();
    if ($results->num_rows() > 0) {
      foreach($results->result_array() as $row) {
        foreach ($row as $k => $v) {
          $row[$k] = array('html' => FALSE);
        }
        $row['data'] = array(
          'html' => TRUE,
          'sort' => FALSE,
        );
        $row = $this->fixrow($row);
        $overall_row = array_merge($overall_row, $row);
      }
    }
    return $overall_row;
  }

  function gettotalmessages() {
    return ee()->db->count_all_results($this->log_table);
  }

  function getmessages($sort, $offset, $perpage) {
    $cols = $this->getcols();
    
    $results = ee()->db->select('*')
                ->limit($perpage, $offset);
    foreach ($sort as $k => $v) {
      $results->order_by($k, $v);
    }
		$results = $results->get($this->log_table);
    $message = array();
    if ($results->num_rows() > 0) {
      foreach($results->result_array() as $row) {
        $row['submitted_at'] = date('r', $row['submitted_at']);
        $data = json_decode($row['data']);
        $row['data'] = '';
        foreach ($data as $k => $v) {
          $row['data'] .= '<strong>' . $k . ': </strong><span>' . $v . '</span><br>';
        }
        $row = $this->fixrow($row);
        foreach ($cols as $k => $v) {
          if (!isset($row[$k])) {
            $row[$k] = '';
          }
        }
        $messages[] = $row;
      }
    }
    return $messages;
  }

  function fixrow($row) {
    foreach ($row as $k => $v) {
      unset($row[$k]);
      $field = str_replace('-', '_', $k);
      $row[$field] = $v;
    }
    return $row;
  }
  
  function disable() {
    $this->enabled = FALSE;
  }
  function enable() {
    $this->enabled = TRUE;
  }
  
}
