<?php

$date = new DateTime();

return [
    'valid_auth_record' => [
        'userId' => 1,
        'publicKey' => '7mqpkpkts5uazn9equzq2bxy98vmyzux5ybwk37m28d88zgbj8e6p4tcc5axmc33',
        'privateKey' => 'vgpcgc8yh8q2f7m9p7xxj8b8zd9jnp3m58ydp5w4a4dukk3ubcqwjp6zbef5582nkc5eew5ann86emmtk9kf8mkvg9yj4zfhbzz9ur5juw5h7d92zk55wnurx3q4twmd',
        'redirectUrl' => '/',
        'dateCreated' => $date->format('Y-m-d H:i:s'),
        'dateUpdated' => $date->format('Y-m-d H:i:s'),
        'uid' => '1fc4a27f-7615-4d7a-9248-760b1099711b'
    ],
    'expired_auth_record' => [
        'userId' => 1,
        'publicKey' => 'randomstring',
        'privateKey' => 'randomstring',
        'redirectUrl' => '',
        'dateCreated' => '2020-01-01 00:00:00',
        'dateUpdated' => '2020-01-01 00:00:00',
    ],
];