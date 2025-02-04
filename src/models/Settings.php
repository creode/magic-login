<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @link      https://www.creode.co.uk
 * @copyright Copyright (c) 2021 Creode
 */

namespace creode\magiclogin\models;

use craft\base\Model;

/**
 * MagicLogin Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Creode
 * @package   MagicLogin
 * @since     1.0.0
 */
class Settings extends Model
{
	// Public Properties
	// =========================================================================

	/**
	 * Default value for how many minutes a magic link is valid.
	 *
	 * @var integer
	 */
	public ?int $linkExpiry = 15;

	/**
	 * Default random password length generated with a user.
	 *
	 * @var integer
	 */
	public ?int $passwordLength = 16;

	/**
	 * Subject line on the Magic Login authentication email.
	 *
	 * @var string
	 */
	public $authenticationEmailSubject = 'Magic Login Link';

	/**
	 * Rate Limit for how frequently a magic login email can be sent (in minutes).
	 *
	 * @var integer
	 */
	public ?int $emailRateLimit = 5;

	/**
	 * How many times a login link can be accessed before it expires.
	 *
	 * @var integer
	 */
	public ?int $linkAccessLimit = 1;

	// TODO: Add a setting to say if magic login click should also verify a user.
	// Grey out the option if verification is disabled on the website.

	// Public Methods
	// =========================================================================

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
		return [
			[['linkExpiry', 'passwordLength', 'emailRateLimit', 'linkAccessLimit'], 'number'],
			[['authenticationEmailSubject'], 'string'],
		];
	}
}
