<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\web\View;
use craft\mail\Mailer;
use craft\elements\User;
use creode\magiclogin\records\AuthRecord;

class LoginFormTest extends BaseFunctionalTest
{
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    /**
     * Tests that the login form appears.
     *
     * @return void
     */
    public function testGetLoginForm()
    {
        $this->tester->amOnPage('/magic-login/login');
        $this->tester->seeResponseCodeIs(200);
        $this->tester->canSeeInSource('name="email"');
        $this->tester->canSeeInSource('<form');

        $view = new View();
        $loginFormActionMarkup = $view->renderString(
            '{{ actionInput(\'magic-login/magic-login/login\') }}'
        );
        $this->tester->canSeeInSource($loginFormActionMarkup);
        $this->tester->canSeeInSource('<input type="hidden" name="action" value="magic-login/magic-login/login">');
    }

    // TODO: Add a test to redirect you if already logged in and posting to magic-login login action route.

    // TODO: Add a test to redirect you if already logged in and trying to visit login link sent form.

    // TODO: Add test to check you get logged in if trying to register with existing email.

    /**
     * Tests that the user is redirected to the postLoginRedirect config value.
     *
     * @return void
     */
    public function testGetLoginFormRedirectIfLoggedIn()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->postLoginRedirect = '/login-successful';
        $validUser = User::findOne();

        $this->tester->amLoggedInAs($validUser);
        $this->tester->amOnPage('/magic-login/login');
        $this->tester->seeCurrentUrlEquals($generalConfig->postLoginRedirect);
    }

    /**
     * Tests that the login form returns a validation error if no email
     * address is provided.
     *
     * @return void
     */
    public function testLoginFormValidationError()
    {
        // Get all Auth Records.
        $authRecords = AuthRecord::find()->all();

        $this->tester->amOnPage('/magic-login/login');
        $this->tester->submitForm(
            '#magic-login-form',
            [
                'email' => '',
            ],
            'submitButton'
        );

        $this->tester->canSee('Please enter a valid email address.');

        // Collect auth records after submission.
        $updatedAuthRecords = AuthRecord::find()->all();

        // Ensure no new auth records have been created in the database.
        $this->assertEquals(count($updatedAuthRecords), count($authRecords));
    }

    /**
     * Tests attempting to signup with an unregistered user.
     *
     * @return void
     */
    public function testUnregisteredUserSignup()
    {
        // Collect auth records before submission.
        $authRecords = AuthRecord::find()->all();

        $this->tester->amOnPage('/magic-login/login');
        $this->tester->submitForm(
            '#magic-login-form',
            [
                'email' => 'test@example.com',
            ],
            'submitButton'
        );

        // Collect auth records after submission.
        $updatedAuthRecords = AuthRecord::find()->all();

        // Ensure no new auth records have been created in the database since
        // user doesn't exist.
        $this->assertEquals(count($updatedAuthRecords), count($authRecords));

        // As far as user is concerned, they should see a message about this.
        $this->tester->canSee('Upon receiving this link, please click it in order to log in.');
    }

    /**
     * Tests that a message is returned to a user if a magic link
     * email cannot be sent.
     *
     * @return void
     * */
    public function testEmailNotSentMessage()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->loginPath = '/magic-login/login';

        $mailerMock = $this->make(
            Mailer::class,
            [
                'send' => function () {
                    return false;
                }
            ]
        );

        Craft::$app->setComponents(
            [
                'mailer' => $mailerMock,
            ]
        );

        // Load up a valid user since they need to be registered to login.
        $validUser = User::findOne();

        $this->tester->amOnPage($generalConfig->loginPath);
        $this->tester->submitForm(
            '#magic-login-form',
            [
                'email' => $validUser->email,
            ],
            'submitButton'
        );

        $this->tester->see('Magic link could not be sent.');
    }

    /**
     * Tests that a successful login is created.
     *
     * @return void
     */
    public function testSuccessfulLogin()
    {
        // Load up a valid user since they need to be registered to login.
        $validUser = User::findOne();

        // See test/_craft/config/magic-login.php for reference.
        $emailSubject = 'Here is your magic login link';

        // Remove all existing auth records to give the opportunity for a proper test.
        AuthRecord::deleteAll();

        // Get all Auth Records.
        $authRecords = AuthRecord::find()->all();

        $this->tester->amOnPage('/magic-login/login');
        $this->tester->submitForm(
            '#magic-login-form',
            [
                'email' => $validUser->email,
            ],
            'submitButton'
        );

        $this->tester->canSee('Upon receiving this link, please click it in order to log in.');

        // Recollect the auth records from database.
        $updatedAuthRecords = AuthRecord::find()->all();

        // We should have a new AuthRecord added to the database.
        $this->assertEquals(count($updatedAuthRecords), count($authRecords) + 1);

        $this->tester->seeEmailIsSent();

        // Validate that the subject is configured via the settings.
        $magicLoginEmail = $this->tester->grabLastSentEmail();
        $this->assertEquals($emailSubject, $magicLoginEmail->getSubject());
    }
}
