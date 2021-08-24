<?php

namespace creode\magiclogintests\fixtures;

use craft\records\User;
use craft\test\fixtures\elements\UserFixture;

/**
 * Fixture class for seeding in an auth model.
 */
class UserRecordFixture extends UserFixture
{
	public $dataFile = __DIR__ . '/../_data/magiclogin_userrecord.php';
}
