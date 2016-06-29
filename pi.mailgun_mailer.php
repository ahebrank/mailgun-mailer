<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
require 'lib/mailgun-php/vendor/autoload.php';
use Mailgun\Mailgun;

$plugin_info = array (
	'pi_name' => 'Mailgun Mailer',
	'pi_version' => '0.4',
	'pi_author' => 'TJ Draper, Andy Hebrank',
	'pi_author_url' => 'https://insidenewcity.com',
	'pi_description' => 'Send emails via mailgun (based on MandrillMailer by TJ Draper)',
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
		$this->jsonReturn = ($jsonReturn == 'yes');
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
		$this->privateMessage = ($privateMessage == 'yes');
		$this->anchor = ee()->TMPL->fetch_param('anchor');
		$this->outputTemplate = ee()->TMPL->fetch_param('output_template', false);
		$recaptcha = ee()->TMPL->fetch_param('recaptcha', false);
		$this->recaptcha = ($recaptcha == 'yes');
		$this->honeypot = ee()->TMPL->fetch_param('honeypot', false);

		// If there was an error posting, fill in the form values from the post
		$this->variables = array();
		foreach ($this->allowed as $allowed) {
			$this->variables[0][$allowed] = ee()->input->post($allowed);
		}
	}

	public function form() {
		// Make sure allowed param is set
		if (empty($this->allowed[0])) {
			return "Allowed parameter is required.";
		}

		// Detect whether this is a submission or not
		if ($_POST) {
			$returnData = $this->_postForm();
			if ($returnData === FALSE) {
				$returnData = '<div class="error">Error in form submission</div>' 
					. $this->_setForm();
			}
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
				return FALSE;
			}
		}

		$domain = ee()->config->item('mailgun_domain');
		$mailer = new Mailgun(ee()->config->item('mailgun_key', null));

		// Set up message and set the reply to as the sender
		$message = array(
			'o:tracking' => false,
		);

		// Set the reply-to to the from email
		// this is set from the first of the following that isn't blank:
		//   1. the "from" parameter on the form tag
		//   2. the "from-email" input
		//   3. the webmaster_email config item
		if (!empty($this->from)) {
			$message['h:reply-to'] = $this->from;
		} 
		elseif (!empty($this->post['from-email'])) {
			$message['h:reply-to'] = $this->post['from-email'];
		}
		else {
			$message['h:reply-to'] = ee()->config->item('webmaster_email');
		}

		// Set the MIME from email to match mailgun's SMTP sender for best deliverability
		$sender = ee()->config->item('mailgun_sender');
		if ($sender === FALSE) {
			$sender = ee()->config->item('webmaster_email');
		}
		$message['from'] = $sender;

		// Set the "from" name if it exists
		if (!empty($this->fromName)) {
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

		if ($this->outputTemplate) {
			// use a template to make the email
			$htmlContent = $this->_processTemplate($this->post, $this->outputTemplate);
			$textContent = $this->_stripTags($htmlContent);
		}
		else {
			// email is key: value for every key
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

		// strip out disallowed inputs
		$this->post = array();
		foreach ($_POST as $postKey => $postValue) {
			if ($postKey !== 'submission') {
				$key = ee()->security->xss_clean($postKey);
				if (in_array($key, $this->allowed)) {
					$value = ee()->security->xss_clean($postValue);
					$this->post[$key] = $value;
				}
			}
		}

		// captcha?
		if ($this->recaptcha) {
			if (!isset($_POST['g-recaptcha-response']) || !$this->_checkCaptcha($_POST['g-recaptcha-response'])) {
				$errors = true;
				$this->variables[0]['error:recaptcha'] = true;
			}
		}

		// honeypot?
		if ($this->honeypot !== false) {
			if (isset($_POST[$this->honeypot]) && !empty($_POST[$this->honeypot])) {
				$errors = true;
				$this->variables[0]['error:honeypot'] = true;
			}
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

		$protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$url = $protocol . $_SERVER['HTTP_HOST'] . '/' . ee()->uri->uri_string();
		if ($this->anchor) {
			$url .= '#' . $this->anchor;
		}

		$form_opts = array(
				'action'          => $url,
		    'name'            => 'mailgun_mailer',
		    'secure'          => TRUE
		);

		if ($this->formClass) {
			$form_opts['class'] = $this->formClass;
		}

		if ($this->formId) {
			$form_opts['id'] = $this->formId;
		}

		$form = ee()->functions->form_declaration($form_opts)
			. $this->tagContents
			. '</form>';

		// check for a recaptcha request
		if (strpos($this->tagContents, '{recaptcha}') !== FALSE) {
			if ($this->recaptcha) {
				$this->variables[0]['recaptcha'] = '<div class="g-recaptcha" data-sitekey="' 
					. ee()->config->item('mailgun_recaptcha_site_key') . '"></div>';
				$form .= '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
			}
			else {
				// hide the tag
				$this->variables[0]['recaptcha'] = (ee()->config->item('template_debugging') == 'y')? '<em>Recaptcha is disabled on this form.</em>' : '';
			}
		}

		return ee()->TMPL->parse_variables($form, $this->variables);
	}

	private function _in_message($key) {
		return in_array($key, $this->message);
	}

	private function _processTemplate($form, $template) {
		// get the template
		list($template_group, $template_file) = explode('/', $template);
		$tmpl = ee()->TMPL->fetch_template($template_group, $template_file, false);

		// filter the $form variables to make sure they're supposed to be in the message
		$allowed_keys = array_filter(array_keys($form), array($this, '_in_message'));
		$form = array_intersect_key($form, array_flip($allowed_keys));

		$parsed = ee()->TMPL->parse_variables($tmpl, array($form));

		// remove unmatched template tags
		$parsed = preg_replace('/{[\w-_]*}/', '', $parsed);

		return $parsed;
	}

	/**
	 * check recaptcha 
	 */
	private function _checkCaptcha($formval) {
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$data = array(
			'secret' => ee()->config->item('mailgun_recaptcha_secret'),
			'response' => $formval
		);
		$ch = curl_init();
		$opts = array(
			CURLOPT_URL => $url, 
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_RETURNTRANSFER => TRUE,
		);
		curl_setopt_array($ch, $opts);
		try {
			$result = curl_exec($ch);
			$resp = json_decode($result);
			curl_close($ch);
			return ($resp->success);
		}
		catch (Exception $e) {
			curl_close($ch);
		}
		return FALSE;
	}

	private function _stripTags($html) {
		// simple html -> newline converter
		$nl_tags = array('</p>','<br />','<br>','<hr />','<hr>','</h1>','</h2>','</h3>','</h4>','</h5>','</h6>');
		return strip_tags(str_replace($nl_tags, "\n", $html));
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