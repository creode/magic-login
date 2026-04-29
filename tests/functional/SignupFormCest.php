<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use FunctionalTester;

class SignupFormCest
{
    public function _before(FunctionalTester $I)
    {
        // Ensure useEmailAsUsername is set to true.
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->useEmailAsUsername = false;
    }

    public function testSignupFormWithoutRequiredUsername(FunctionalTester $I)
    {
        $I->amOnPage('/magic-login/register');
        
        // Attempt to submit the form with only an email address
        $I->fillField('email', 'test@example.com');
        $I->click('Submit');

        // Expect error messages and no redirection
        $I->dontSeeInCurrentUrl('/confirmation');
        $I->see('Please enter a valid username'); // Adjust according to the actual error message expected
    }

    public function _after(FunctionalTester $I)
    {
        // Reset useEmailAsUsername to true
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->useEmailAsUsername = true;
    }
}
