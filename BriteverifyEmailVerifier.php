<?php

require_once __DIR__ . '/MailgunMailerEmailVerificationInterface.php';
require_once __DIR__ . '/EmailVerifierBase.php';

class BriteverifyEmailVerifier extends EmailVerifierBase implements MailgunMailerEmailVerificationInterface {

    public function verify($email) {
        $data = array(
            'apikey' => $this->api_key,
            'address' => $email,
        );
        $url = 'https://bpi.briteverify.com/emails.json';
        $json = $this->curlget($url, $data);
        if ($json === FALSE || isset($json['errors'])) {
            return self::EMAIL_VERIFY_PROBLEM;
        }
        if (isset($json['status'])) {
			if ($json['status'] == 'valid') {
				return self::EMAIL_VERIFY_YES;
			}
			if ($json['status'] == 'unknown' || $json['status'] == 'accept_all') {
				return self::EMAIL_VERIFY_FLAG;
			}
		}
        return self::EMAIL_VERIFY_NO;
    }

}