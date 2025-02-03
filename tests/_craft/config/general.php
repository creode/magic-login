<?php

use craft\helpers\App;

return [
	'devMode' => true,
	'postLoginRedirect' => '/',
	'requireUserAgentAndIpForSession' => false,
	'securityKey' => App::env('SECURITY_KEY'),
	'enableCsrfProtection' => false,
];
