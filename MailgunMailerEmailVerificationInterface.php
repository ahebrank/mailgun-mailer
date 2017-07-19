<?php

interface MailgunMailerEmailVerificationInterface {
    public function __construct($api_key);
    public function verify($email);
}