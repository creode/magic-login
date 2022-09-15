<?php

return [
	'devMode' => true,
	'postLoginRedirect' => '/',
	'requireUserAgentAndIpForSession' => false,
	'securityKey' => getenv('SECURITY_KEY'),
];
