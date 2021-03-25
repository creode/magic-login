<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\records\User;
use craft\elements\User as UserElement;
use FunctionalTester;
use \Codeception\Test\Unit;

/**
 * Tests the functionality behind the custom registration form.
 */
class RegistrationFormTest extends Unit
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
        Craft::$app->setEdition(Craft::Pro);
    }

    // Public methods
    // =========================================================================

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
        $this->tester->canSeeInSource('<input id="email" name="email"');
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
     * Tests that with the correct parameters, users can register
     * for an account with a magic link.
     *
     * @return void
     */
    public function testUserCanRegister()
    {
        $this->tester->amOnPage('/magic-login/register');
        $this->tester->submitForm(
            '#register',
            [
                'email' => 'test@example.com',
                'password' => 'something'
            ],
            'submitButton'
        );

        $this->tester->canSeeRecord(User::class, ['email' => 'test@example.com']);
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
            '#register',
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
            '#register',
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
    //         '#register',
    //         [
    //             'email' => $userEmail,
    //         ],
    //         'submitButton'
    //     );

    //     $user = UserElement::findOne(['email' => $userEmail]);
    //     $this->assertContains('Magic Login', $user->getGroups());
    // }
}
