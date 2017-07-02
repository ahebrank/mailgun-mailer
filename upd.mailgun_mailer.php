<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mailgun_mailer_upd { 

    public $version = 0.4;

	function install() {
        $mod_data = array(
			'module_name'        => 'Mailgun_mailer',
			'module_version'     => $this->version,
			'has_cp_backend'     => "y",
			'has_publish_fields' => 'n'
		);
		
		ee()->db->insert('modules', $mod_data);
		
        return TRUE;
	}

	function uninstall() { 
        ee()->db->where('module_name', 'mailgun_mailer')
                ->delete('modules');
        return TRUE;
	}

	function update($current = '') {
		return TRUE;
	}
	

}