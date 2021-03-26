<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\web\User;
use creode\magiclogin\MagicLogin;
use creode\magiclogin\records\AuthRecord;
use creode\magiclogintests\fixtures\AuthRecordFixture;

class MagicLoginAuthenticationTest extends \Codeception\Test\Unit
{
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    /**
     * Undocumented function
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'auth_records' => [
                'class' => AuthRecordFixture::class,
                // fixture data located in tests/_data/user.php
                'dataFile' => codecept_data_dir() . 'magiclogin_authrecord.php'
            ],
        ];
    }

    /**
     * Tests that providing an invalid key will not
     * log a user in.
     *
     * @return void
     */
    public function testInvalidMagicLinkPublicKey()
    {
        $link = $this->_getMagicLoginLinkBase();

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
        $link = $this->_getMagicLoginLinkBase($authRecord->publicKey, $date->getTimestamp());

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

        $link = $this->_getMagicLoginLinkBase(
            $authRecord->publicKey,
            $date->getTimestamp(),
            $signature
        );

        $this->tester->amOnPage($link);
        $this->tester->see('Login Link has expired, please login and try the link again.');
        $this->tester->seeCurrentUrlEquals('/magic-login/login');
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
        $link = $this->_generateValidLink($authRecord);

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
        $link = $this->_generateValidLink($authRecord);

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

    /**
     * Helper function to generate a valid magic link based on provided auth record.
     *
     * @param AuthRecord $authRecord
     *
     * @return void
     */
    private function _generateValidLink(AuthRecord $authRecord)
    {
        $date = new \DateTime($authRecord->dateCreated);
        $signature = MagicLogin::$plugin->magicLoginAuthService->generateSignature(
            $authRecord->privateKey,
            $authRecord->publicKey,
            $date->getTimestamp()
        );

        return $this->_getMagicLoginLinkBase(
            $authRecord->publicKey,
            $date->getTimestamp(),
            $signature
        );
    }

    /**
     * Helper function to get an invalid magic link base.
     *
     * @param string  $publicKey
     * @param boolean $timestamp
     * @param string  $signature
     *
     * @return void
     */
    private function _getMagicLoginLinkBase($publicKey = 'randominvalidkey', $timestamp = false, $signature = 'invalidsignature')
    {
        if (!$timestamp) {
            $timestamp = time();
        }
        return "/magic-login/auth/$publicKey/$timestamp/$signature";
    }

    /**
     * Helper function to generate Public and Private keys.
     *
     * @return void
     */
    private function _generateKeys()
    {
        $generator = MagicLogin::$plugin
            ->magicLoginRandomGeneratorService
            ->getHighStrengthGenerator();

        $publicKey = $generator->generateString(
            64,
            'abcdefghjkmnpqrstuvwxyz23456789'
        );
        $privateKey = $generator->generateString(
            128,
            'abcdefghjkmnpqrstuvwxyz23456789'
        );

        return compact(['privateKey', 'publicKey']);
    }
}