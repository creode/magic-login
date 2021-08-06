<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\web\View;
use creode\magiclogin\MagicLogin;
use creode\magiclogin\records\AuthRecord;
use creode\magiclogintests\fixtures\AuthRecordFixture;
use craft\elements\User as UserElement;

class LoginRegistrationFormTest extends BaseFunctionalTest
{
	/**
	 * @var \FunctionalTester
	 */
	protected $tester;

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
	 * Tests that the login form appears.
	 *
	 * @return void
	 */
	public function testGetLoginRegisterForm()
	{
		$this->tester->amOnPage('/magic-login/login-or-register');
		$this->tester->seeResponseCodeIs(200);
		$this->tester->canSeeInSource('name="email"');
		$this->tester->canSeeInSource('<form');

		$view = new View();
		$loginFormActionMarkup = $view->renderString(
			'{{ actionInput(\'magic-login/magic-login/login-or-register\') }}'
		);
		$this->tester->canSeeInSource($loginFormActionMarkup);
		$this->tester->canSeeInSource(
			'<input type="hidden" name="action" value="magic-login/magic-login/login-or-register">'
		);
	}

	/**
	 * Tests that a user is registered without requiring activation will send us to right place.
	 */
	public function testRegisteringNewUserWithNoActivationGetsMagicLink()
	{
		// Remove all existing auth records to give the opportunity for a proper test.
		AuthRecord::deleteAll();

		// Get all Auth Records.
		$authRecords = AuthRecord::find()->all();

		$this->tester->amOnPage('/magic-login/login-or-register');
		$this->tester->submitForm(
			'#magic-login-form',
			[
				'email' => 'test@example.com',
			],
			'submitButton'
		);

		$this->tester->canSee('Login link has been sent to email address provided.');

		// Recollect the auth records from database.
		$updatedAuthRecords = AuthRecord::find()->all();

		// We should have a new AuthRecord added to the database.
		$this->assertEquals(count($updatedAuthRecords), count($authRecords) + 1);

		// Magic Login Email should have been sent.
		$this->tester->seeEmailIsSent(1);
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

		// Remove all existing auth records to give the opportunity for a proper test.
		AuthRecord::deleteAll();

		// Get all Auth Records.
		$authRecords = AuthRecord::find()->all();

		// Attempt to use login-or-register form.
		$this->tester->amOnPage('/magic-login/login-or-register');
		$this->tester->submitForm(
			'#magic-login-form',
			[
				'email' => 'test@example.com',
			],
			'submitButton'
		);

		// Make sure we see the login link text.
		$this->tester->canSee('Login link has been sent to email address provided.');

		// Recollect the auth records from database.
		$updatedAuthRecords = AuthRecord::find()->all();

		// We should have a new AuthRecord added to the database.
		$this->assertEquals(count($updatedAuthRecords), count($authRecords) + 1);

		// Magic Login Email should have been sent.
		$this->tester->seeEmailIsSent(1);
	}

	public function testUserVerifiedOnMagicLoginAuthorization()
	{
		// Enable setting to Require Email Verification on new users.
		$userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
		$userSettings['requireEmailVerification'] = true;
		Craft::$app->projectConfig->set('users', $userSettings);

		// Register a new user into Craft.
		$registrationEmail = 'creode-test@example.com';
		$this->tester->amOnPage('/magic-login/login-or-register');
		$this->tester->submitForm(
			'#magic-login-form',
			[
				'email' => $registrationEmail,
			],
			'submitButton'
		);

		// Load in a user and get the password explicitly so we can validate it.
		$user = UserElement::find()
			->email($registrationEmail)
			->anyStatus()
			->one();

		// Ensure that the user has a pending status.
		$this->assertEquals('1', $user->pending);

		// Grab an auth record.
		/** @var \creode\magiclogin\records\AuthRecord $validRecord */
		$validRecord = $this->tester->grabFixture('auth_records', 'test_user_3_auth_record');
		$validRecord->uid = $user->id;
		$validRecord->save();

		// Get all auth records.
		$records = AuthRecord::find()->all();

		// Navigate to a valid magic login verify link.
		$validLink = $this->generateValidMagicLink($validRecord);
		$this->tester->amOnPage($validLink);

		$user = UserElement::find()
			->email($registrationEmail)
			->anyStatus()
			->one();
		
		// Ensure that the user is no longer pending.
		$this->assertEquals('0', $user->pending);

		// Reset Verification back to how it was before the test.
		// This ensures any tests after this are using expected parameters.
		$userSettings['requireEmailVerification'] = false;
		Craft::$app->projectConfig->set('users', $userSettings);
	}
}
