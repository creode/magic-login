<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin\models;

use creode\magiclogin\MagicLogin;

use Craft;
use craft\base\Model;
use craft\validators\DateTimeValidator;

/**
 * AuthModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @package MagicLogin
 * @author  Creode <contact@creode.co.uk>
 * @since   1.0.0
 */
class AuthModel extends Model
{
	// Public Properties
	// =========================================================================

	/**
	 * ID of the user to authenticate.
	 *
	 * @var string
	 */
	public $userId;

	/**
	 * Public Key used in Authorisation.
	 *
	 * @var string
	 */
	public $publicKey;

	/**
	 * Private Key used in Authorisation.
	 *
	 * @var string
	 */
	public $privateKey;

	/**
	 * Redirection url when logged in.
	 *
	 * @var string
	 */
	public $redirectUrl;

	/**
	 * Amount of times the link has been accessed.
	 *
	 * @var integer
	 */
	public $accessCount = 0;

	/**
	 * Creation date of the record.
	 *
	 * @var \DateTime
	 */
	public $dateCreated;

	/**
	 * Timestamp for when next email can send.
	 */
	public ?\DateTime $nextEmailSend;

	// Public Methods
	// =========================================================================

	/**
	 * Checks the expiry date of the model to see if it has expired.
	 *
	 * @return boolean
	 */
	public function isExpired()
	{
		// Access count.
		$linkAccessLimit = MagicLogin::getInstance()->getSettings()->linkAccessLimit;
		if ($linkAccessLimit !== null && $this->accessCount >= $linkAccessLimit) {
			return true;
		}

		// Check if timestamp is within bounds set by plugin configuration
		$linkExpiryAmount = MagicLogin::getInstance()->getSettings()->linkExpiry;
		$dateCreated = new \DateTime($this->dateCreated->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
		$expiryTimestamp = $dateCreated->getTimestamp() + ($linkExpiryAmount * 60);
		return $expiryTimestamp < time();
	}

	/**
	 * Checks if model has hit the email rate limit.
	 *
	 * @return boolean
	 */
	public function hasHitEmailRateLimit()
	{
		if (is_null($this->nextEmailSend)) {
			return false;
		}

		$nextEmailSend = new \DateTime($this->nextEmailSend->format('Y-m-d H:i:s'));
		$now = new \DateTime();
		$now->getTimestamp();
		return $nextEmailSend->getTimestamp() > $now->getTimestamp();
	}

	/**
	 * Returns the validation rules for attributes.
	 *
	 * Validation rules are used by [[validate()]] to check if attribute values are valid.
	 * Child classes may override this method to declare different validation rules.
	 *
	 * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = parent::rules();
		$rules[] = [['publicKey', 'privateKey', 'redirectUrl'], 'string'];
		$rules[] = [['userId', 'accessCount'], 'number'];
		$rules[] = [['dateCreated', 'nextEmailSend'], DateTimeValidator::class];
		
		return $rules;
	}
}
