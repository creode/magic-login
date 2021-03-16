<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
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

        if ($user === null || $user->status != 'active') {
            return false;
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

        // Populate Record
        $record = new AuthRecord();
        $expiryDate = new \DateTime;
        $record->userId = $user->id;
        $record->publicKey = $publicKey;
        $record->privateKey = $privateKey;
        $record->expiryDate = $expiryDate;
        $record->save();

        $timestamp = $expiryDate->getTimestamp();
        $signature = $this->generateSignature($privateKey, $publicKey, $timestamp);

        $baseUrl = Craft::$app->getRequest()->getHostInfo();

        return $baseUrl . "/magic-login/auth/$publicKey/$timestamp/$signature";

        // TODO: Craft::$app->getUsers()->activateUser($user); - Activate the user if they aren't already.
    }

    /**
     * Takes in someones username or email address and registers
     * them as a craft user.
     *
     * @param string $userNameOrEmail Username or email address 
     * @return void
     */
    public function registerMagicLinkUser(string $email)
    {
        // Look up user
        $user = Craft::$app->users->getUserByUsernameOrEmail($email);

        // User already exists so we can't register them. Create a link instead.
        if ($user != null) {
            return $this->createMagicLogin($email);
        }

        // TODO: Attach random password to user.

        // TODO: Follow user registration flow.

        // TODO: Send out magic link?
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
     * @return \creode\magiclogin\models\AuthModel
     */
    public function getAuthorisationRecord($publicKey) : AuthModel
    {
        $model = new AuthModel();
        $record = AuthRecord::findOne(['publicKey' => $publicKey]);

        if ($record) {
            $attributes = $record->getAttributes();

            // We do this to make a static model with only public parameters exposed.
            $model->setAttributes($attributes, false);
        }

        return $model;
    }
}
