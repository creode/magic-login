<?php

namespace creode\magiclogintests\acceptance;

use Craft;
use craft\records\UserGroup;
use creode\magiclogin\MagicLogin;
use creode\magiclogin\records\AuthRecord;
use Codeception\Test\Unit;

class BaseFunctionalTest extends Unit
{
	/**
	 * Helper function to generate a valid magic link based on provided auth record.
	 *
	 * @param AuthRecord $authRecord
	 *
	 * @return string
	 */
	public function generateValidMagicLink(AuthRecord $authRecord)
	{
		$date = new \DateTime($authRecord->dateCreated);
		$signature = MagicLogin::$plugin->magicLoginAuthService->generateSignature(
			$authRecord->privateKey,
			$authRecord->publicKey,
			$date->getTimestamp()
		);

		return $this->getMagicLoginLinkBase(
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
	public function getMagicLoginLinkBase($publicKey = 'randominvalidkey', $timestamp = false, $signature = 'invalidsignature')
	{
		if (!$timestamp) {
			$timestamp = time();
		}
		return "/magic-login/auth/$publicKey/$timestamp/$signature";
	}

	/**
	 * Helper function to generate Public and Private keys.
	 *
	 * @return array
	 */
	public function generateMagicLoginLinkKeys()
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

	/**
	 * Gets a magic login group object if exists, creates one if not.
	 *
	 * @return craft\records\UserGroup
	 */
	public function getMagicLoginGroup()
	{
		$magicLoginGroup = Craft::$app
			->getUserGroups()
			->getGroupByHandle(MagicLogin::MAGIC_LOGIN_USER_GROUP_HANDLE);

		if (!$magicLoginGroup) {
			// Make the magic login group. (Ideally we should have this already with plugin
			// install but due to a craft issue we need to manually create it in order for
			// tests to pass.
			$magicLoginGroup = new UserGroup();
			$magicLoginGroup->name = 'Magic Login';
			$magicLoginGroup->handle = MagicLogin::MAGIC_LOGIN_USER_GROUP_HANDLE;
			$magicLoginGroup->description = 'Allows a user to login via magic link';
			$magicLoginGroup->save();
		}

		return $magicLoginGroup;
	}
}
