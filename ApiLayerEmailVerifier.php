<?php

require_once __DIR__ . '/MailgunMailerEmailVerificationInterface.php';
require_once __DIR__ . '/EmailVerifierBase.php';

class ApiLayerEmailVerifier implements MailgunMailerEmailVerificationInterface {
    
    public function verify($email) {
        $data = array(
            'access_key' => $this->api_key,
            'smtp' => 1,
            'format' => 1,
            'email' => $email,
        );
        $url = 'https://apilayer.net/api/check';
        $json = $this->curlget($url, $data);
        if ($json === FALSE || isset($json['error'])) {
            return 'Unable to contact verification API service';
        }
        $keys = array('format_valid', 'mx_found', 'smtp_check');
        $fail = FALSE;
        foreach ($keys as $check) {
            if (!isset($json[$check]) || !$json[$check]) {
                $fail = TRUE;
                break;
            }
        }
        if (!$fail) {
            return self::EMAIL_VERIFY_YES;
        }
        return self::EMAIL_VERIFY_NO;
    }

}