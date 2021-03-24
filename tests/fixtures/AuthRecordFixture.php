<?php

namespace creode\magiclogintests\fixtures;

use creode\magiclogin\records\AuthRecord;
use yii\test\ActiveFixture;

/**
 * Fixture class for seeding in an auth model.
 */
class AuthRecordFixture extends ActiveFixture
{
    public $modelClass = AuthRecord::class;
    // public $depends = [
    //     UserFixture::class
    // ]
}
