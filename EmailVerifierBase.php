<?php

class EmailVerifierBase {
    protected $api_key;

    const EMAIL_VERIFY_NO = -1;
    const EMAIL_VERIFY_YES = 1;
    const EMAIL_VERIFY_FLAG = 0;
    const EMAIL_VERIFY_PROBLEM = -2;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    protected function curlpost($url, $data) {
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
			$resp = json_decode($result, true);
			curl_close($ch);
			return ($resp);
		}
		catch (Exception $e) {
			curl_close($ch);
		}
		return FALSE;
	}

    protected function curlget($url, $data) {
		$ch = curl_init();
		$opts = array(
			CURLOPT_URL => $url . '?' . http_build_query($data),
			CURLOPT_RETURNTRANSFER => TRUE,
		);
		curl_setopt_array($ch, $opts);
		try {
			$result = curl_exec($ch);
			$resp = json_decode($result, true);
			curl_close($ch);
			return ($resp);
		}
		catch (Exception $e) {
			curl_close($ch);
		}
		return FALSE;
	}
}