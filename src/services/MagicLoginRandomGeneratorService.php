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

use RandomLib\Factory as RandomLibFactory;
use craft\base\Component;

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
class MagicLoginRandomGeneratorService extends Component
{
	protected $factory;

	/**
	 * Constructor for Random Generator Service.
	 */
	public function __construct(RandomLibFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * Gets a medium strength generator used for Keys and Salts.
	 *
	 * @return \RandomLib\Generator
	 */
	public function getMediumStrengthGenerator()
	{
		return $this->factory->getMediumStrengthGenerator();
	}

	/**
	 * Gets a high strength generator used for Cryptography.
	 *
	 * @return \RandomLib\Generator
	 */
	public function getHighStrengthGenerator()
	{
		return $this->factory->getHighStrengthGenerator();
	}
}
