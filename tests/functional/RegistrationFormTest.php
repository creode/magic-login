<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\web\View;
use FunctionalTester;
use craft\records\User;
use RandomLib\Generator;
use creode\magiclogin\MagicLogin;
use craft\elements\User as UserElement;
use craft\enums\CmsEdition;
use creode\magiclogin\records\AuthRecord;
use creode\magiclogintests\fixtures\AuthRecordFixture;
use creode\magiclogin\services\MagicLoginRandomGeneratorService;

/**
 * Tests the functionality behind the custom registration form.
 */
class RegistrationFormTest extends BaseFunctionalTest
{
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function _before()
    {
        Craft::$app->setEdition(CmsEdition::Pro);

        // Set login path to login page to ensure that the templates exist.
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->loginPath = '/magic-login/login';
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

    // Tests
    // =========================================================================

    /**
     * Test to demonstrate the registration form for Magic Links can be returned.
     *
     * @return void
     */
    public function testRegistrationFormGet()
    {
        // Test that we can see the registration form.
        $this->tester->amOnPage('/magic-login/register');
        $this->tester->seeResponseCodeIs(200);
        $this->tester->canSeeInSource('name="email"');
        $this->tester->canSeeInSource('<form');

        $view = new View();
        $registrationActionInputMarkup = $view->renderString(
            '{{ actionInput(\'magic-login/magic-login/register\') }}'
        );
        $this->tester->canSeeInSource($registrationActionInputMarkup);
    }

    // TODO: Add a test to redirect you if already logged in and posting to magic-login register action route.

    // TODO: Before saving a user if already logged in ensure magic login event is not being fired.

    // TODO: If providing userId to user save ensure magic login before event isn't being handled.

    // TODO: If email not provided before registering a user ensure our error is thrown.

    // TODO: After saving a user if already logged in check if it is working as we expect around assigning magic
    // login role etc.

    // TODO: After saving, if userId param is provided ensure that magic login role is not given.

    // TODO: After saving, test the checks around deleted users with email. (MagicLogin.php:293).

    // TODO: If we can't find the magic login group ensure that valid message is provided to user.

    // TODO: If we can't add the user to the magic login group ensure that valid message is provided to user.

    /**
     * Tests that a user is registered without requiring activation will send us to right place.
     */
    public function testRegisteringNewUserWithNoActivationGetsMagicLink()
    {
        // Get all Auth Records.
        $authRecords = AuthRecord::find()->all();

        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [
                'email' => 'no-activation@example.com'
            ],
            'submitButton'
        );

        $this->tester->canSee('Upon receiving this link, please click it in order to log in.');

        // Recollect the auth records from database.
        $updatedAuthRecords = AuthRecord::find()->all();

        // We should have a new AuthRecord added to the database.
        $this->assertEquals(count($updatedAuthRecords), count($authRecords) + 1);

        // Magic Login Email should have been sent.
        $this->tester->seeEmailIsSent(1);

        // Delete user created during this process.
        /** @var \craft\elements\User $user */
        $user = Craft::$app->users->getUserByUsernameOrEmail('no-activation@example.com');
        Craft::$app->elements->deleteElement($user);
    }

    /**
     * Tests that a user is registered when requiring activation will still send magic login link.
     */
    public function testRegisteringNewUserWithActivationEnabledGetsMagicLink()
    {
        // Enable setting to Require Email Verification on new users.
        $userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $userSettings['requireEmailVerification'] = true;
        Craft::$app->projectConfig->set('users', $userSettings);

        // Get all Auth Records.
        $authRecords = AuthRecord::find()->all();

        // Attempt to use register form.
        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [
                'email' => 'test@example.com',
            ],
            'submitButton'
        );

        // Make sure we see the login link text.
        $this->tester->canSee('Upon receiving this link, please click it in order to log in.');

        // Recollect the auth records from database.
        $updatedAuthRecords = AuthRecord::find()->all();

        // We should have a new AuthRecord added to the database.
        $this->assertEquals(count($updatedAuthRecords), count($authRecords) + 1);

        // Magic Login Email should have been sent.
        $this->tester->seeEmailIsSent(1);

        // Delete user created during this process.
        /** @var \craft\elements\User $user */
        $user = Craft::$app->users->getUserByUsernameOrEmail('test@example.com');
        Craft::$app->elements->deleteElement($user);
    }

    public function testUserVerifiedOnMagicLoginAuthorization()
    {
        // Enable setting to Require Email Verification on new users.
        $userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $userSettings['requireEmailVerification'] = true;
        Craft::$app->projectConfig->set('users', $userSettings);

        // Register a new user into Craft.
        $registrationEmail = 'creode-test-2@example.com';
        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [
                'email' => $registrationEmail,
            ],
            'submitButton'
        );

        // Load in a user so we can validate their status.
        $user = UserElement::find()
            ->email($registrationEmail)
            ->status(null)
            ->one();

        // Ensure that the user has a pending status.
        $this->assertEquals(true, $user->pending);

        // Grab an auth record.
        /** @var \creode\magiclogin\records\AuthRecord $validRecord */
        $validRecord = $this->tester->grabFixture('auth_records', 'test_user_4_auth_record');

        // Navigate to a valid magic login verify link.
        $validLink = $this->generateValidMagicLink($validRecord);
        $this->tester->amOnPage($validLink);

        $this->tester->pause();

        $user = UserElement::find()
            ->email($registrationEmail)
            ->status(null)
            ->one();
        
        // Ensure that the user is no longer pending.
        $this->assertEquals(false, $user->pending);

        // Reset Verification back to how it was before the test.
        // This ensures any tests after this are using expected parameters.
        $userSettings['requireEmailVerification'] = false;
        Craft::$app->projectConfig->set('users', $userSettings);

        // Delete user created during this process.
        /** @var \craft\elements\User $user */
        $user = Craft::$app->users->getUserByUsernameOrEmail($registrationEmail);
        Craft::$app->elements->deleteElement($user);
    }

    /**
     * Tests that the user is redirected to the postLoginRedirect config value.
     *
     * @return void
     */
    public function testGetRegistrationFormRedirectIfLoggedIn()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $validUser = UserElement::findOne();

        $this->tester->amLoggedInAs($validUser);
        $this->tester->amOnPage('/magic-login/register');
        $this->tester->seeCurrentUrlEquals($generalConfig->postLoginRedirect);
    }

    /**
     * Tests that if a user doesn't provide a password a working one will be created for them.
     *
     * @return void
     */
    public function testUsersCanRegisterWithoutAPasswordToGetARandomOne()
    {
        $registrationEmail = 'test2@example.com';
        $password = 'something-random';

        $generatorMock = $this->make(
            Generator::class,
            [
                'generateString' => function () use ($password) {
                    return $password;
                }
            ]
        );

        $magicLoginGeneratorServiceMock = $this->make(
            MagicLoginRandomGeneratorService::class,
            [
                'getMediumStrengthGenerator' => $generatorMock,
            ]
        );

        MagicLogin::$plugin->setComponents(
            [
                'magicLoginRandomGeneratorService' => function () use ($magicLoginGeneratorServiceMock) {
                    return $magicLoginGeneratorServiceMock;
                }
            ]
        );

        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [
                'email' => $registrationEmail,
            ],
            'submitButton'
        );

        // Load in a user and get the password explicitly so we can validate it.
        $user = UserElement::find()
            ->addSelect(['users.password'])
            ->email($registrationEmail)
            ->anyStatus()
            ->one();

        $this->assertTrue($user->authenticate($password));
    }

    /**
     * Tests that with the correct parameters, users can register
     * for an account with a magic link.
     *
     * @return void
     */
    public function testUserCanRegisterAndLoginWithAProvidedPassword()
    {
        $registrationEmail = 'creode@example.com';
        $password = 'something';

        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [
                'email' => $registrationEmail,
                'password' => $password
            ],
            'submitButton'
        );

        // Load in a user and get the password explicitly so we can validate it.
        $user = UserElement::find()
            ->addSelect(['users.password'])
            ->email($registrationEmail)
            ->status(null)
            ->one();

        $this->assertTrue($user->authenticate($password));
    }

    /**
     * Tests that if we don't supply a valid email address
     * a form error will be displayed back to the user.
     *
     * @return void
     */
    public function testErrorsDisplayedOnForm()
    {
        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [],
            'submitButton'
        );

        // TODO: This is likely going to editable in future.
        $this->tester->canSee('Please enter a valid email address.');
    }

    /**
     * Tests that when a validation error happens on registration
     * a user is not created.
     *
     * @return void
     */
    public function testWhenRegistrationErrorOccursUserIsNotCreated()
    {
        $userCount = count(User::find()->all());

        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#magic-login-register',
            [],
            'submitButton'
        );

        $this->assertEquals($userCount, count(User::find()->all()));
    }

    /**
     * Tests that when a user is successfully registered the magic
     * login group is attached.
     *
     * This test has been commented out due to the attached bug:
     * https://github.com/craftcms/cms/issues/7724
     *
     * This should be uncommented out once the bug has been resolved.
     *
     * @return void
     */
    // public function testMagicLoginGroupIsAttachedToAUserWhenRegistering()
    // {
    //     Craft::$app->setEdition(Craft::Pro);

    //     $userEmail = 'test-attached-group@example.com';
    //     $this->tester->amOnPage('/magic-login/register');
    //     $this->tester->submitForm(
    //         '#magic-login-register',
    //         [
    //             'email' => $userEmail,
    //         ],
    //         'submitButton'
    //     );

    //     $user = UserElement::findOne(['email' => $userEmail]);
    //     $this->assertContains('Magic Login', $user->getGroups());
    // }
}
