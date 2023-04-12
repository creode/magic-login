<?php

namespace creode\magiclogin\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230411_132215_add_email_timeout_field migration.
 */
class m230411_132215_add_email_timeout_field extends Migration
{
	/**
	 * @var string The database driver to use
	 */
	public $tableSchema;

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn('{{%magiclogin_authrecord}}', 'nextEmailSend', $this->dateTime()->null()->defaultValue(null));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%magiclogin_authrecord}}', 'nextEmailSend');
        return true;
    }
}
