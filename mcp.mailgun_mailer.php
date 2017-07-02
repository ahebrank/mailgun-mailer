<?php

require_once('MailgunMailerLogger.php');

/**
 * Mailgun Mailer MCP Class
 *
 * Mailgun Mailer Module Control Panel class to handle all CP requests
 *
 */
class Mailgun_mailer_mcp {
	
	var $version = '0.4';
	var $module_name = "Mailgun Mailer";
	
    var $log, $url, $path;
	
	function __construct() {
        $this->log = new MailgunMailerLogger();
        $this->url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailgun_mailer';
        $this->path = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailgun_mailer';
	}
	
	/*
	
	CONTROLLER FUNCTIONS
	
	*/
	
	function index() {

		if (ee()->session->userdata('group_id') != 1) {
			exit('Not a Superadmin');
		}
		
		// Set page title
		ee()->view->cp_page_title = "Mailgun Mailer";
		
		// Load helpers
		ee()->load->library('table');
		ee()->load->helper('form');
		
		$cols = $this->log->getcols();

		ee()->table->set_base_url($this->path);
		ee()->table->set_columns($cols);

        $data = array(
            'form_action' => $this->url,
        );

		// Get order by and sort preferences for our initial state
		$order_by = 'submitted_at';
        $sort = 'desc';

		$state = array(
			'sort'	=> array($order_by => $sort)
		);

		$params = array(
			'perpage'	=> 50,
		);

		$table = ee()->table->datasource('_get_messages', $state, $params);
        $data = array_merge($data, $table);

		// Load view
		return ee()->load->view('log', $data, TRUE);
	}

    function _get_messages($state, $params) {
        $perpage = ee()->input->get_post('perpage');
        $perpage = $perpage ? $perpage : $params['perpage'];
        $offset = $state['offset'];
		$sort = $state['sort'];

        $rows = $this->log->getmessages($sort, $offset, $perpage);

        return array(
			'rows' => $rows,
			'no_results' => '<p>No messages found.</p>',
			'pagination' => array(
				'per_page' => $perpage,
				'total_rows' => $this->log->gettotalmessages()
			),
		);
    }

}

/* End of file mcp.ajw_datagrab.php */