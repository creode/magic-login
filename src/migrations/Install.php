<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin\migrations;

use Craft;
use craft\db\Connection;
use craft\db\Migration;

/**
 * Magic Login Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Creode
 * @package   MagicLogin
 * @since     1.0.0
 */
class Install extends Migration
{
	// Public Properties
	// =========================================================================

	/**
	 * @var string The database driver to use
	 */
	public $driver;

	// Public Methods
	// =========================================================================

	/**
	 * This method contains the logic to be executed when applying this migration.
	 * This method differs from [[up()]] in that the DB logic implemented here will
	 * be enclosed within a DB transaction.
	 * Child classes may implement this method instead of [[up()]] if the DB logic
	 * needs to be within a transaction.
	 *
	 * @return boolean return a false value to indicate the migration fails
	 * and should not proceed further. All other return values mean the migration succeeds.
	 */
	public function safeUp()
	{
		$this->driver = Craft::$app->getConfig()->getDb()->driver;
		if ($this->createTables()) {
			$this->createIndexes();
			$this->addForeignKeys();
			// Refresh the db schema caches
			Craft::$app->db->schema->refresh();
			$this->insertDefaultData();
		}

		return true;
	}

	/**
	 * This method contains the logic to be executed when removing this migration.
	 * This method differs from [[down()]] in that the DB logic implemented here will
	 * be enclosed within a DB transaction.
	 * Child classes may implement this method instead of [[down()]] if the DB logic
	 * needs to be within a transaction.
	 *
	 * @return boolean return a false value to indicate the migration fails
	 * and should not proceed further. All other return values mean the migration succeeds.
	 */
	public function safeDown()
	{
		$this->driver = Craft::$app->getConfig()->getDb()->driver;
		$this->removeTables();

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Creates the tables needed for the Records used by the plugin
	 *
	 * @return bool
	 */
	protected function createTables()
	{
		$tablesCreated = false;

		// magiclogin_authrecord table
		$tableSchema = Craft::$app
			->db
			->schema
			->getTableSchema('{{%magiclogin_authrecord}}');
		
		if ($tableSchema === null) {
			$tablesCreated = true;
			$this->createTable(
				'{{%magiclogin_authrecord}}',
				[
					'id' => $this->primaryKey(),
					'userId' => $this->integer()->notNull(),
					'publicKey' => $this->string()->notNull(),
					'privateKey' => $this->string()->notNull(),
					'redirectUrl' => $this->string()->null(),
					'accessCount' => $this->integer()->notNull()->defaultValue(0),
					'dateCreated' => $this->dateTime()->notNull(),
					'dateUpdated' => $this->dateTime()->notNull(),
					// 'siteId' => $this->integer()->notNull(), - I don't think this is required right now but may be in future.
					'uid' => $this->uid(),
					'nextEmailSend' => $this->dateTime()->null()->defaultValue(null),
				]
			);
		}

		return $tablesCreated;
	}

	/**
	 * Creates the indexes needed for the Records used by the plugin
	 *
	 * @return void
	 */
	protected function createIndexes()
	{
		// magiclogin_authrecord table
		$this->createIndex(
			null,
			'{{%magiclogin_authrecord}}',
			'publicKey',
		);

		// Additional commands depending on the db driver
		// switch ($this->driver) {
		//     case Connection::DRIVER_MYSQL:
		//         break;
		//     case Connection::DRIVER_PGSQL:
		//         break;
		// }
	}

	/**
	 * Creates the foreign keys needed for the Records used by the plugin
	 *
	 * @return void
	 */
	protected function addForeignKeys()
	{
		// magiclogin_authrecord table
		$this->addForeignKey(
			$this->db->getForeignKeyName('{{%magiclogin_authrecord}}', 'userId'),
			'{{%magiclogin_authrecord}}',
			'userId',
			'{{%users}}',
			'id',
			'CASCADE',
			'CASCADE'
		);
	}

	/**
	 * Populates the DB with the default data.
	 *
	 * @return void
	 */
	protected function insertDefaultData()
	{
	}

	/**
	 * Removes the tables needed for the Records used by the plugin
	 *
	 * @return void
	 */
	protected function removeTables()
	{
		 // magiclogin_authrecord table
		$this->dropTableIfExists('{{%magiclogin_authrecord}}');
	}
}
