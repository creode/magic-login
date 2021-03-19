<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
 *
 * @link      https://www.creode.co.uk
 * @copyright Copyright (c) 2021 Creode
 */

namespace creode\magiclogintests\unit;

use Codeception\Test\Unit;
use UnitTester;
use Craft;
use creode\magiclogin\MagicLogin;

/**
 * MagicLoginAuthServiceTest
 *
 * @package MagicLogin
 * @author  Creode <contact@creode.co.uk>
 * @since   1.0.0
 */
class PluginInitialisationTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * Ensure that the plugin is of the correct type.
     * 
     * @return void
     */
    public function testPluginInstance()
    {
        $this->assertInstanceOf(
            MagicLogin::class,
            MagicLogin::$plugin
        );
    }

    /**
     * Ensure we are using Pro version of Craft since this the users
     * features require it.
     * 
     * @return void
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }
}