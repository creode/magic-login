<?php

namespace creode\magiclogin\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240615_120115_add_access_count_field migration.
 */
class m240615_120115_add_access_count_field extends Migration
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
        $this->addColumn('{{%magiclogin_authrecord}}', 'accessCount', $this->integer()->defaultValue(0));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn('{{%magiclogin_authrecord}}', 'accessCount');
        return true;
    }
}
