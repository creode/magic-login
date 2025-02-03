<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use FunctionalTester;
use craft\elements\User;
use creode\magiclogin\MagicLogin;
use creode\magiclogin\records\AuthRecord;
use creode\magiclogintests\fixtures\AuthRecordFixture;
use creode\magiclogintests\fixtures\UserRecordFixture;
use creode\magiclogintests\acceptance\BaseFunctionalTest;

class LinkExpiryTest extends BaseFunctionalTest
{
    /**
     * Test email address used throughout this class.
     *
     * @var string
     */
    protected $test_user_email = 'magic-login-expiry@example.com';

    /**
     * FunctionalTester instance.
     *
     * @var FunctionalTester
     */
    protected $tester;
    
    public function _before() {
        // Copy the config file to the right folder
        copy(
            codecept_data_dir() . 'config/multiple-link.php',
            Craft::$app->getConfig()->configDir . '/magic-login.php'
        );
    }

    public function _after() {
        // Copy the config file to the right folder
        copy(
            codecept_data_dir() . 'config/single-link.php',
            Craft::$app->getConfig()->configDir . '/magic-login.php'
        );
    }

    /**
     * Sets up any fixtures used in this class.
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'user_records' => [
                'class' => UserRecordFixture::class,
                // fixture data located in tests/_data/magiclogin_authrecord.php
                'dataFile' => codecept_data_dir() . 'magiclogin_userrecord.php'
            ],
            'auth_records' => [
                'class' => AuthRecordFixture::class,
                'dataFile' => codecept_data_dir() . 'magiclogin_authrecord.php'
            ],
        ];
    }

    /**
     * Test that a link can only be used a certain number of times before it expires.
     *
     * @return void
     */
    public function testALinkCanHaveAConfigurableNumberOfTimesBeforeItExpires() {
        $user = User::findOne(['email' => $this->test_user_email]);
        $authRecord = AuthRecord::findOne(['userId' => $user->id]);
        $link = $this->generateValidMagicLink($authRecord);

        $linkAccessLimit = MagicLogin::$plugin->getSettings()->linkAccessLimit;

        for ($i = 0; $i < 3; $i++) {
            $this->tester->amOnPage($link);
            $this->tester->seeCurrentUrlEquals($authRecord->redirectUrl);
            // Logout user.
            $this->tester->amOnPage('/logout');
        }

        // Ensure that we are redirected to the login page.
        $this->tester->amOnPage($link);
        $this->tester->seeCurrentUrlEquals('/index.php?p=login');
    }

    // TODO: Test that the link expires after a certain amount of time.

    // TODO: Test that the link can be used multiple times before expiry.

    // TODO: Test the link expiry overall.
}
