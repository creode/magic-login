<?php

$date = new DateTime('now', new DateTimeZone('UTC'));

return [
	'valid_auth_record' => [
		'userId' => 1,
		'publicKey' => '7mqpkpkts5uazn9equzq2bxy98vmyzux5ybwk37m28d88zgbj8e6p4tcc5axmc33',
		'privateKey' => 'vgpcgc8yh8q2f7m9p7xxj8b8zd9jnp3m58ydp5w4a4dukk3ubcqwjp6zbef5582nkc5eew5ann86emmtk9kf8mkvg9yj4zfhbzz9ur5juw5h7d92zk55wnurx3q4twmd',
		'redirectUrl' => '/',
		'dateCreated' => $date->format('Y-m-d H:i:s'),
		'dateUpdated' => $date->format('Y-m-d H:i:s'),
		'uid' => '1fc4a27f-7615-4d7a-9248-760b1099711b',
		'nextEmailSend' => null,
	],
	'expired_auth_record' => [
		'userId' => 1,
		'publicKey' => 'randomstring',
		'privateKey' => 'randomstring',
		'redirectUrl' => '',
		'dateCreated' => '2020-01-01 00:00:00',
		'dateUpdated' => '2020-01-01 00:00:00',
		'nextEmailSend' => null,
	],
	'test_user_4_auth_record' => [
		'userId' => 7,
		'publicKey' => 'nq37y47rn9qq1v753q85daa96oh35zpx0okrpcnn9806pzhy18guyytr3mdhtg5x',
		'privateKey' => 'vgpcgc8yh8q2f7m9p7xxj8b8zd9jnp3m58ydp5w4a4dukk3ubcqwjp6zbef5582nkc5eew5ann86emmtk9kf8mkvg9yj4zfhbzz9ur5juw5h7d92zk55wnurx3q4twmd',
		'redirectUrl' => '/',
		'dateCreated' => $date->format('Y-m-d H:i:s'),
		'dateUpdated' => $date->format('Y-m-d H:i:s'),
		'uid' => '1fc4a27f-7615-4d7a-9248-760b1099711b',
		'nextEmailSend' => null,
	],
	'test_login_expiry' => [
		'userId' => 4,
		'publicKey' => 'kwnyvg7fxbbyg5v2tdesyahryu3u73ykxhad4z2u732239u2q4e995vjdjxsj3yz',
		'privateKey' => 'mgcewe6nnxm9g798kys3fdy9cprv4dpsx9xqymcwq2595gzx7wqvp2a6z4k3p7nzn4kn7x3zycqw3neybx98x3ezgrqba357j2hb7bcqsbwnjqeyxmpyvua23a4cuxnu',
		'redirectUrl' => '/',
		'dateCreated' => $date->format('Y-m-d H:i:s'),
		'dateUpdated' => $date->format('Y-m-d H:i:s'),
		'uid' => 'f46424ab-ffcf-47da-9a2c-f6fae94fd202',
		'nextEmailSend' => null,
	],
];
