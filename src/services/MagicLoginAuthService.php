<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin\services;

use Craft;

use craft\base\Component;
use creode\magiclogin\MagicLogin;
use creode\magiclogin\models\AuthModel;
use creode\magiclogin\records\AuthRecord;

/**
 * MagicLoginAuthService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @package MagicLogin
 * @author  Creode <contact@creode.co.uk>
 * @since   1.0.0
 */
class MagicLoginAuthService extends Component
{
	// Public Methods
	// =========================================================================

	/**
	 * Accepts in the username or email of a user
	 * and generates a magic login link for them.
	 *
	 * @param string $userNameOrEmail of user to create link for.
	 * 
	 * @return string|bool
	 *  The login url that the user can click on, false if user cannot be found.
	 */
	public function createMagicLogin(string $userNameOrEmail)
	{
		// Look up user
		$user = Craft::$app->users->getUserByUsernameOrEmail($userNameOrEmail);
		if ($user === null) {
			return false;
		}

		// If the link has not expired for this user then just issue it again.
		$existingAuthRecord = AuthRecord::findOne(['userId' => $user->id]);
		if (!$this->linkHasExpired($existingAuthRecord)) {
			$dateTimeCreatedObject = new \DateTime($existingAuthRecord->dateCreated, new \DateTimeZone('UTC'));
			$dateCreatedTimestamp = $dateTimeCreatedObject->getTimestamp();

			$signature = $this->generateSignature(
				$existingAuthRecord->privateKey,
				$existingAuthRecord->publicKey,
				$dateCreatedTimestamp
			);

			return $this->createMagicLoginUrl($existingAuthRecord->publicKey, $dateCreatedTimestamp, $signature);
		}

		// If we have an existing auth record but got to this point then it has expired and needs removing.
		if ($existingAuthRecord) {
			$existingAuthRecord->delete();
		}

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

		$generalConfig = Craft::$app->getConfig()->getGeneral();

		// Populate Record
		$record = new AuthRecord();
		$record->userId = $user->id;
		$record->publicKey = $publicKey;
		$record->privateKey = $privateKey;
		$record->redirectUrl = Craft::$app
			->getRequest()
			->getValidatedBodyParam('redirect') ?? $generalConfig->postLoginRedirect;
		$record->save();

		// Generate Datetime for current dateCreated and use it's timestamp.
		$dateTimeCreatedObject = new \DateTime($record->dateCreated, new \DateTimeZone('UTC'));
		$dateCreatedTimestamp = $dateTimeCreatedObject->getTimestamp();

		// Build up a signature for validation and sent the link back to the user.
		$signature = $this->generateSignature($privateKey, $publicKey, $dateCreatedTimestamp);
		return $this->createMagicLoginUrl($publicKey, $dateCreatedTimestamp, $signature);
	}

	/**
	 * Takes private key, public key and timestamp to build a sha1 hash.
	 *
	 * @param string $privateKey Private key to use in hash.
	 * @param string $publicKey  Public key to use in hash.
	 * @param int    $timestamp  Timestamp to use with the hash.
	 * 
	 * @return string
	 *  The Signature of the public key, private key and timestamp.
	 */
	public function generateSignature($privateKey, $publicKey, $timestamp) : string
	{
		$stringToHash = implode('-', array($publicKey, $timestamp));
		$signature = hash_hmac('sha1', $stringToHash, $privateKey);

		return $signature;
	}

	/**
	 * Uses the public key to lookup the authorisation record in the database.
	 *
	 * @param string $publicKey
	 * @return \creode\magiclogin\records\AuthRecord
	 */
	public function getAuthorisationRecord($publicKey) : ?AuthRecord
	{
		return AuthRecord::findOne(['publicKey' => $publicKey]) ?? null;
	}

	/**
	 * Generate a full magic login link with the base url.
	 *
	 * @param string $publicKey
	 * @param integer $timestamp
	 * @param string $signature
	 * @return string
	 */
	private function createMagicLoginUrl(string $publicKey, int $timestamp, string $signature)
	{
		$baseUrl = Craft::$app->getRequest()->getHostInfo();

		return $baseUrl . "/magic-login/auth/$publicKey/$timestamp/$signature";
	}

	/**
	 * Quick check to determine if a magic link has expired.
	 *
	 * @param AuthRecord $authRecord
	 * @return boolean
	 */
	private function linkHasExpired(?AuthRecord $authRecord)
	{
		if (!$authRecord) {
			return true;
		}

		$authModel = new AuthModel($authRecord->getAttributes([
			'publicKey',
			'privateKey',
		]));

		$authModel->dateCreated = new \DateTime($authRecord->dateCreated, new \DateTimeZone('UTC'));

		return $authModel->isExpired();
	}
}
