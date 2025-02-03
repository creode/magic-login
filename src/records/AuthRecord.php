<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @link      https://www.creode.co.uk
 * @copyright Copyright (c) 2021 Creode
 */

namespace creode\magiclogin\records;

use creode\magiclogin\MagicLogin;

use Craft;
use craft\db\ActiveRecord;

/**
 * AuthRecord Record
 *
 * ActiveRecord is the base class for classes representing relational data in terms of objects.
 *
 * Active Record implements the [Active Record design pattern](http://en.wikipedia.org/wiki/Active_record).
 * The premise behind Active Record is that an individual [[ActiveRecord]] object is associated with a specific
 * row in a database table. The object's attributes are mapped to the columns of the corresponding table.
 * Referencing an Active Record attribute is equivalent to accessing the corresponding table column for that record.
 *
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html
 *
 * @author    Creode
 * @package   MagicLogin
 * @since     1.0.0
 */
class AuthRecord extends ActiveRecord
{
	// Public Static Methods
	// =========================================================================

	 /**
	 * Declares the name of the database table associated with this AR class.
	 * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]]
	 * with prefix [[Connection::tablePrefix]]. For example if [[Connection::tablePrefix]] is `tbl_`,
	 * `Customer` becomes `tbl_customer`, and `OrderItem` becomes `tbl_order_item`. You may override this method
	 * if the table is not named after this convention.
	 *
	 * By convention, tables created by plugins should be prefixed with the plugin
	 * name and an underscore.
	 *
	 * @return string the table name
	 */
	public static function tableName()
	{
		return '{{%magiclogin_authrecord}}';
	}

	/**
	 * Determine if we have hit the access limit for the auth record.
	 *
	 * @return boolean
	 */
	public function hasHitAccessLimit() {
		$linkAccessLimit = MagicLogin::$plugin->getSettings()->linkAccessLimit;
		if ($linkAccessLimit !== null && $this->accessCount >= $linkAccessLimit) {
			return true;
		}

		return false;
	}

	public function hasExpired() {
		// Check if timestamp is within bounds set by plugin configuration
		$linkExpiryAmount = MagicLogin::getInstance()->getSettings()->linkExpiry;
		$dateCreated = new \DateTime($this->dateCreated, new \DateTimeZone('UTC'));
		$expiryTimestamp = $dateCreated->getTimestamp() + ($linkExpiryAmount * 60);
		if ($expiryTimestamp < time()) {
			return true;
		}

		if ($this->hasHitAccessLimit()) {
			return true;
		}

		return false;
	}
}
