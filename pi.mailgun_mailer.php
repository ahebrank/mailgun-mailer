<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
require 'lib/mailgun-php/vendor/autoload.php';
use Mailgun\Mailgun;

$plugin_info = array (
	'pi_name' => 'Mailgun Mailer',
	'pi_version' => '0.1',
	'pi_author' => 'TJ Draper, Andy Hebrank',
	'pi_author_url' => 'https://insidenewcity.com',
	'pi_description' => 'Send emails via mailgun (based on MandrillMailer by TJ Draper',
	'pi_usage' => Mailgun_mailer::usage()
);

class Mailgun_mailer {

	public function __construct() {
		// Fetch Parameters
		$this->tagContents = ee()->TMPL->tagdata;
		$this->formClass = ee()->TMPL->fetch_param('class');
		$this->formId = ee()->TMPL->fetch_param('id');
		$this->return = ee()->TMPL->fetch_param('return');
		$jsonReturn = ee()->TMPL->fetch_param('json');
		$this->jsonReturn = $jsonReturn == 'yes' ? true : false;
		$this->required = explode('|', ee()->TMPL->fetch_param('required'));
		$this->allowed = explode('|', ee()->TMPL->fetch_param('allowed'));
		$to = explode('|', ee()->TMPL->fetch_param('to'));
		$this->to = ! empty($to[0]) ? $to : false;
		$this->from = ee()->TMPL->fetch_param('from');
		$this->fromName = ee()->TMPL->fetch_param('from-name');
		$this->subject = ee()->TMPL->fetch_param('subject');
		$message = explode('|', ee()->TMPL->fetch_param('message'));
		$this->message = ! empty($message[0]) ? $message : false;
		$privateMessage = ee()->TMPL->fetch_param('private_message');
		$this->privateMessage = ($privateMessage == 'yes')? true : false;

		// Get form attr attributes
		$this->formAttr = array();

		foreach (ee()->TMPL->tagparams as $key => $val) {
			if (strncmp($key, 'attr:', 5) === 0) {
				$this->formAttr[substr($key, 5)] = $val;
			}
		}

		// If there was an error posting, fill in the form values from the post
		$this->variables = array();
		foreach ($this->allowed as $allowed) {
			$this->variables[0][$allowed] = ee()->input->post($allowed);
		}
	}

	public function form() {
		// Make sure allowed param is set
		if (empty($this->allowed[0])) {
			return;
		}

		// Detect whether this is a submission or not
		if ($_POST) {
			$returnData = $this->_postForm();
		} else {
			$returnData = $this->_setForm();
		}

		return $returnData;
	}

	private function _postForm() {
		// Check the form submission for errors
		$errors = $this->_checkForm();

		// Return errors if there are any
		if ($errors) {
			if ($this->jsonReturn === true) {
				$output = array(
					'success' => 0
				);

				foreach ($this->required as $required) {
					if (! empty($errors['error:' . $required])) {
						$output['errors'][] = $required;
					}
				}

				ee()->output->send_ajax_response($output);
			} else {
				return $errors;
			}
		}

		$domain = ee()->config->item('mailgun_domain');
		$mailer = new Mailgun(ee()->config->item('mailgun_key'));

		// Set up message and set the reply to as the sender
		$message = array(
			'o:tracking' => false,
		);

		// Set the reply-to to the from email
		if (! empty($this->from)) {
			$message['h:reply-to'] = $this->from;
		} else {
			$message['h:reply-to'] = $this->post['from-email'];
		}

		// Set the from email to the webmaster email for best deliverability
		$message['from'] = ee()->config->item('webmaster_email');

		// Set the "from" name if it exists
		if (! empty($this->fromName)) {
			$message['from'] = $this->fromName . " <" . $message['from'] . ">";
		} else if (ee()->input->post('from-name')) {
			$message['from'] = $this->post['from-name'] . " <" . $message['from'] . ">";
		}

		// Set the "to" email
		if (! empty($this->to)) {
			foreach ($this->to as $email) {
				$message['to'][] = $email;
			}
		} else {
			$message['to'][] = $this->post['to-email'];
		}

		// Set the subject
		if (! empty($this->subject)) {
			$message['subject'] = $this->subject;
		} else {
			$message['subject'] = addslashes($this->post['subject']);
		}

		// Content

		// If message parameter is not specified, populate the array with all
		// post values
		if ($this->message === false) {
			foreach ($this->post as $key => $value) {
				$this->message[] = $key;
			}
		}

		$htmlContent = '';
		$textContent = '';

		foreach ($this->post as $key => $value) {
			if (in_array($key, $this->message)) {
				$key = str_replace('-', ' ', $key);
				$key = ucwords($key);

				$value = $value . '

';

				$htmlContent .= '<strong>' . $key . '</strong>: ';
				$htmlContent .= nl2br(htmlentities($value, ENT_QUOTES));

				$textContent .= $key . ': ';
				$textContent .= addslashes($value);
			}
		}

		// Set the content to the $message array
		$message['html'] = $htmlContent;
		$message['text'] = $textContent;

		// Send the message
		$result = $mailer->sendMessage($domain, $message);
		$success = ($result->http_response_code == 200);

		// Set up the appropriate return
		if (! empty($this->return)) {
			// Redirect to the return paramter
			if ($success) {
				ee()->functions->redirect($this->return);
			} else {
				// Set the form up
				$form = $this->_setForm();

				// Return with appropriate variables
				return $form;
			}
		} elseif ($this->jsonReturn == true) {
			$output = array(
				'success' => ($success == true) ? 1 : 0
			);

			ee()->output->send_ajax_response($output);
		} else {
			// Set the succes variable
			$this->variables[0]['success'] = $success;

			// Clear variables on success so form doesn't repopulate
			if ($success) {
				foreach ($this->allowed as $allowed) {
					$this->variables[0][$allowed] = false;
				}
			}

			// Return the form
			return $this->_setForm();
		}
	}

	private function _checkForm() {
		// Make sure we have from and to email addresses and a subject

		if ($this->to === false AND ! in_array('to-email', $this->required)) {
			$this->required[] = 'to-email';
		}

		if ($this->from === false AND ! in_array('from-email', $this->required)) {
			$this->required[] = 'from-email';
		}

		if ($this->subject === false AND ! in_array('subject', $this->required)) {
			$this->required[] = 'subject';
		}

		// Initially set errors to false
		$errors = false;
		$notAllowed = array();

		// Check that all required fields are present
		if (! empty($this->required[0])) {
			foreach ($this->required as $required) {
				$thisContent = ee()->input->post($required);

				if (empty($thisContent)) {
					$this->variables[0]['error:' . $required] = true;
					$errors = true;
				}
			}
		}

		// Check that only allowed fields are part of the submission

		$this->post = array();

		foreach ($_POST as $postKey => $postValue) {
			if ($postKey !== 'submission') {
				$key = ee()->security->xss_clean($postKey);
				$value = ee()->security->xss_clean($postValue);
				$this->post[$key] = $value;
			}
		}

		foreach ($this->post as $postKey => $postValue) {
			if (! in_array($postKey, $this->allowed)) {
				$notAllowed[] = true;
			}
		}

		if (! empty($notAllowed)) {
			show_error('Your submission contains disallowed inputs');
			exit();
		}

		// If there are errors, set the form and return
		if ($errors) {
			$this->variables[0]['error'] = true;
			return ($this->jsonReturn) ? $this->variables[0] : $this->_setForm();
		}

		// If there are no errors, just return
		return false;
	}

	private function _setForm($parse = true) {
		$form = '<form action="" method="post"';

		if ($this->formClass) {
			$form .= ' class="' . $this->formClass . '"';
		}

		if ($this->formId) {
			$form .= ' id="' . $this->formId . '"';
		}

		if ($this->formAttr) {
			foreach ($this->formAttr as $key => $val) {
				$form .= ' ' . $key . '="' . $val . '"';
			}
		}

		$form .= '>';

		// Check for CSRF
		if (ee()->config->item('disable_csrf_protection') !== 'y') {
			$form .= '<input type="hidden" name="';
			$form .= ee()->security->get_csrf_token_name() . '" ';
			$form .= 'value="' . ee()->security->get_csrf_hash() . '">';
		}

		$form .= $this->tagContents;
		$form .= '</form>';

		return ee()->TMPL->parse_variables($form, $this->variables);
	}

	function usage() {
		ob_start();
?>

<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
}