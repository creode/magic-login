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

use RandomLib\Factory as RandomLibFactory;
use craft\base\Component;
use creode\magiclogin\MagicLogin;
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
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     MagicLogin::$plugin->MagicLoginAuthService->exampleService()
     *
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (MagicLogin::$plugin->getSettings()->someAttribute) {
        }

        return $result;
    }

    /**
     * Accepts in the username or email of a user
     * and generates a magic login link for them.
     *
     * @param string $userNameOrEmail of user to create link for.
     * 
     * @return string
     *  The login url that the user can click on.
     */
    public function createMagicLogin($userNameOrEmail)
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
        $timestamp = time();
        $record->userId = $user->id;
        $record->publicKey = $publicKey;
        $record->privateKey = $privateKey;
        $record->timestamp = $timestamp;
        $record->save();

        $signature = $this->generateSignature($privateKey, $publicKey, $timestamp);
        $magicLogin = Craft::$app->getSiteUrl() . "magic-login/auth/$publicKey/$timestamp/$signature";

        return $magicLogin;
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
    public function generateSignature($privateKey, $publicKey, $timestamp)
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
    public function getAuthorisationRecord($publicKey) 
    {
        $record = AuthRecord::findOne(['publicKey' => $publicKey]);
    }
}
