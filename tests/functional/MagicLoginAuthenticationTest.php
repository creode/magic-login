<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\web\User;
use Craft\elements\User as UserElement;
use creode\magiclogin\MagicLogin;
use creode\magiclogin\records\AuthRecord;
use creode\magiclogintests\fixtures\AuthRecordFixture;

class MagicLoginAuthenticationTest extends BaseFunctionalTest
{
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    const ADMIN_USER = 1;

    /**
     * @var \craft\records\UserGroup
     */
    protected $magicLinkGroup;

    /**
     * @inheritdoc
     */
    public function _before()
    {
        // Assign default user to Magic Login group.
        $group = $this->getMagicLoginGroup();
        Craft::$app->getUsers()->assignUserToGroups(self::ADMIN_USER, [$group->id]);
    }

    /**
     * @inheritdoc
     */
    public function _after()
    {
        // Reassign user back to the default user group (unassign from magic login).
        Craft::$app->getUsers()->assignUserToGroups(self::ADMIN_USER, []);
    }

    /**
     * Sets up any fixtures used in this class.
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'auth_records' => [
                'class' => AuthRecordFixture::class,
                // fixture data located in tests/_data/magiclogin_authrecord.php
                'dataFile' => codecept_data_dir() . 'magiclogin_authrecord.php'
            ],
        ];
    }

    /**
     * If a logged in user clicks on a magic link then they should just be redirected
     * and the link should be removed.
     *
     * @return void
     */
    public function testMagicLinkClickedWhenLoggedInWillRedirectYouToRedirectPage()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'valid_auth_record');
        $link = $this->generateValidMagicLink($authRecord);

        $this->tester->amLoggedInAs(1);
        $this->tester->amOnPage($link);
        $this->tester->seeCurrentUrlEquals($authRecord->redirectUrl);

        $this->tester->dontSeeRecord(AuthRecord::class, $authRecord->getAttributes());
    }

    // TODO: Check clicking magic login link of user that doesn't exist. Ensure error is returned and
    // you are redirected back to login page.

    /**
     * When a user already has a valid magic link we should provide that in the email.
     *
     * @return void
     */
    public function testWhenAUserAlreadyHasAValidLinkThatLinkShouldBeReused()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'valid_auth_record');

        $user = UserElement::findOne(1);
        $link = MagicLogin::$plugin->magicLoginAuthService->createMagicLogin($user->email);

        $dateObject = new \DateTime($authRecord->dateCreated, new \DateTimeZone('UTC'));

        $this->assertStringContainsString($authRecord->publicKey, $link);
        $this->assertStringContainsString(strval($dateObject->getTimestamp()), $link);

        $this->tester->seeRecord(AuthRecord::class, $authRecord->getAttributes());
    }

    /**
     * If a user has an expired link in the system and we attempted to create
     * a new one the old one should be removed and a new one should be created.
     *
     * @return void
     */
    public function testWhenAUserHasAnExpiredLinkANewOneIsGenerated()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'expired_auth_record');

        // Delete everything except the expired record since we only expect there to
        // ever be one AuthRecord per user in the database.
        AuthRecord::deleteAll('id != ' . $authRecord->id);

        $user = UserElement::findOne(1);
        $link = MagicLogin::$plugin->magicLoginAuthService->createMagicLogin($user->email);

        $dateObject = new \DateTime($authRecord->dateCreated);

        $this->assertStringNotContainsString($authRecord->publicKey, $link);
        $this->assertStringNotContainsString(strval($dateObject->getTimestamp()), $link);

        $this->tester->dontSeeRecord(AuthRecord::class, $authRecord->getAttributes());
    }

    /**
     * Tests that providing an invalid key will not
     * log a user in.
     *
     * @return void
     */
    public function testInvalidMagicLinkPublicKey()
    {
        $link = $this->getMagicLoginLinkBase();

        $this->tester->amOnPage($link);

        $this->tester->seeCurrentUrlEquals('/magic-login/login');
        $this->tester->see('Invalid login link provided.');
    }

    /**
     * Tests that validation for a link signature works as expected.
     *
     * @return void
     */
    public function testInvalidMagicLinkSignature()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'valid_auth_record');

        $date = new \DateTime($authRecord->dateCreated);
        $link = $this->getMagicLoginLinkBase($authRecord->publicKey, $date->getTimestamp());

        $this->tester->amOnPage($link);
        $this->tester->seeCurrentUrlEquals('/magic-login/login');
        $this->tester->see('Invalid login link provided.');
    }

    /**
     * Tests the functionality works as exprected when a login link has expired.
     *
     * @return void
     */
    public function testLoginLinkExpired()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'expired_auth_record');
        
        $date = new \DateTime($authRecord->dateCreated);
        $signature = MagicLogin::$plugin->magicLoginAuthService->generateSignature(
            $authRecord->privateKey,
            $authRecord->publicKey,
            $date->getTimestamp()
        );

        $link = $this->getMagicLoginLinkBase(
            $authRecord->publicKey,
            $date->getTimestamp(),
            $signature
        );

        $this->tester->amOnPage($link);
        $this->tester->see('Login Link has expired, please login and try the link again.');
        $this->tester->seeCurrentUrlEquals('/magic-login/login');
    }

    /**
     * Tests that the correct error message comes back when a user outside of the
     * magic link group tries to authenticate.
     *
     * @return void
     */
    public function testErrorWhenUserIsNotInMagicLinkGroup()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $validRecord = $this->tester->grabFixture('auth_records', 'valid_auth_record');

        // Unassign from magic login group to validate test case.
        Craft::$app->getUsers()->assignUserToGroups($validRecord->userId, []);

        $link = $this->generateValidMagicLink($validRecord);
        $this->tester->amOnPage($link);

        $this->tester->seeCurrentUrlEquals('/magic-login/login');
        $this->tester->see('Magic login is disabled, please contact an admin if you feel this is in error.');
    }

    /**
     * Test that a fallback error occurs if we are unable to log a user in.
     *
     * @return void
     */
    public function testUnableToLoginUserValidation()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'valid_auth_record');
        $link = $this->generateValidMagicLink($authRecord);

        $userServiceMock = $this->make(
            User::class,
            [
                'login' => function () {
                    return false;
                }
            ]
        );

        Craft::$app->setComponents(
            [
                'user' => function () use ($userServiceMock) {
                    return $userServiceMock;
                }
            ]
        );

        $this->tester->amOnPage($link);
        $this->tester->seeCurrentUrlEquals('/magic-login/login');
        $this->tester->see('Unable to login. Please try again later.');
    }

    /**
     * Tests that when a successful magic login occurs, the auth
     * record is deleted and we are redirected to the provided location.
     *
     * @return void
     */
    public function testSuccessfulLinkDeletesAuthRecordAndRedirects()
    {
        /** @var \creode\magiclogin\records\AuthRecord $authRecord */
        $authRecord = $this->tester->grabFixture('auth_records', 'valid_auth_record');
        $link = $this->generateValidMagicLink($authRecord);

        $userServiceMock = $this->make(
            User::class,
            [
                'login' => function () {
                    return true;
                }
            ]
        );

        Craft::$app->setComponents(
            [
                'user' => function () use ($userServiceMock) {
                    return $userServiceMock;
                }
            ]
        );

        $expectedRedirectUrl = $authRecord->redirectUrl;

        $this->tester->seeRecord(AuthRecord::class, $authRecord->getAttributes());

        $this->tester->amOnPage($link);
        $this->tester->seeCurrentUrlEquals($expectedRedirectUrl);

        $this->tester->dontSeeRecord(AuthRecord::class, $authRecord->getAttributes());
    }
}
