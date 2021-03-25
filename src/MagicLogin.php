<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin;

use Craft;
use craft\web\View;

use yii\base\Event;
use craft\base\Plugin;
use craft\web\UrlManager;
use yii\base\ActionEvent;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\controllers\UsersController;
use craft\elements\User;
use creode\magiclogin\models\Settings;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\models\UserGroup;
use creode\magiclogin\services\MagicLoginAuthService;
use creode\magiclogin\services\MagicLoginRandomGeneratorService;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @package MagicLogin
 * @author  Creode
 * @since   1.0.0
 *
 * @property MagicLoginAuthService $magicLoginAuthService
 * @property MagicLoginRandomGeneratorService $magicLoginRandomGeneratorService
 * @property Settings $settings
 * @method   Settings getSettings()
 */
class MagicLogin extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * MagicLogin::$plugin
     *
     * @var MagicLogin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;
    
    public const MAGIC_LOGIN_USER_GROUP_HANDLE = 'magicLogin';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return bool
     */
    protected function beforeInstall(): bool
    {
        // This line breaks tests until https://github.com/craftcms/cms/issues/7724 is resolved.
        // if (Craft::$app->getEdition() !== Craft::Pro) {
        //     \Craft::error(
        //         Craft::t(
        //             'magic-login',
        //             'This plugin requires features from Craft Pro before in order to be installed.'
        //         )
        //     );
        //     return false;
        // }
        return true;
    }

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * MagicLogin::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents(
            [
                'magicLoginRandomGeneratorService' => MagicLoginRandomGeneratorService::class,
                'magicLoginAuthService' => MagicLoginAuthService::class,
            ]
        );

        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['magic-login'] = __DIR__ . '/templates/magic-login';
            }
        );

        Event::on(
            UsersController::class,
            UsersController::EVENT_BEFORE_ACTION,
            function (ActionEvent $event) {
                if ($event->sender->action->actionMethod !== 'actionSaveUser') {
                    return;
                }

                $event->sender->requirePostRequest();

                // If we are updating an existing user then skip this.
                $userId = $this->request->getBodyParam('userId');
                if ($userId) {
                    return;
                }

                // Require email.
                $email = $this->request->getRequiredBodyParam('email');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // TODO: Set this to be configurable.
                    $event->sender->setFailFlash(\Craft::t('magic-login', 'Please enter a valid email address.'));
                    $event->isValid = false;
                    return;
                }

                // TODO: What do we do if already registered. Do we throw an error?

                // Generate a random password.
                $generator = $this->magicLoginRandomGeneratorService
                    ->getMediumStrengthGenerator();

                // Set a random password when registering a user with magic links.
                $password = $generator->generateString(
                    $this->getSettings()->passwordLength
                );

                // Add password into the request body so that it can be set during 
                // user registration action.
                $this->request->setBodyParams(
                    array_merge(
                        $this->request->getBodyParams(),
                        [
                            'password' => $password,
                        ]
                    )
                );
            }
        );

        Event::on(
            UsersController::class,
            UsersController::EVENT_AFTER_ACTION,
            function (ActionEvent $event) {
                if ($event->sender->action->actionMethod !== 'actionSaveUser') {
                    return;
                }

                $event->sender->requirePostRequest();

                // If we are updating an existing user then skip this.
                $userId = $this->request->getBodyParam('userId');
                if ($userId) {
                    return;
                }

                // Require email.
                $email = $this->request->getRequiredBodyParam('email');
                $user = User::findOne(['email' => $email]);

                // If we can't find user something must have happened.
                // We will stop here and allow things to run it's course.
                if (!$user) {
                    return;
                }

                $magicLoginGroup = Craft::$app
                    ->getUserGroups()
                    ->getGroupByHandle(self::MAGIC_LOGIN_USER_GROUP_HANDLE);

                // Throw a warning but continue with request.
                if (!$magicLoginGroup) {
                    Craft::warning(
                        Craft::t(
                            'magic-login',
                            'Magic Login group doesn\'t appear to exist. Cannot assign user to it.'
                        ),
                        __METHOD__
                    );
                    return;
                }

                // Add Magic Login group to user.
                $addedToGroup = Craft::$app->getUsers()->assignUserToGroups(
                    $user->id,
                    [$magicLoginGroup->id]
                );

                // Throw a warning but continue with request.
                if (!$addedToGroup) {
                    Craft::warning(
                        Craft::t(
                            'magic-login',
                            'Couldn\'t add user to Magic Login group.'
                        ),
                        __METHOD__
                    );
                    return;
                }
            }
        );

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['magic-login/login'] = 'magic-login/magic-login/login-form';
                $event->rules['magic-login/register'] = 'magic-login/magic-login/register-form';
                $event->rules['magic-login/auth/<publicKey:\w+>/<timestamp:\d+>/<signature:\w+>'] = 'magic-login/magic-login/auth';
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // TODO: This will need removing once the check for beforeInstall can pass.
                    if (Craft::$app->getEdition() !== Craft::Pro) {
                        return;
                    }

                    // We were just installed
                    $magicLoginUserGroup = new UserGroup();
                    $magicLoginUserGroup->name = 'Magic Login';
                    $magicLoginUserGroup->handle = self::MAGIC_LOGIN_USER_GROUP_HANDLE;
                    $magicLoginUserGroup->description = Craft::t('magic-login', 'Users within this group were registered with magic login capabilities.');

                    $groupSaved = Craft::$app
                        ->getUserGroups()
                        ->saveGroup($magicLoginUserGroup);

                    if (!$groupSaved) {
                        Craft::warning(Craft::t('magic-login', 'Could not create Magic Login User group.'), __METHOD__);
                    }

                    Craft::info(Craft::t('magic-login', 'Created Magic Login User Group.'), __METHOD__);
                }
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_UNINSTALL_PLUGIN,
            function (PluginEvent $event) {
                $magicLoginGroup = Craft::$app
                    ->getUserGroups()
                    ->getGroupByHandle(self::MAGIC_LOGIN_USER_GROUP_HANDLE);

                if (!$magicLoginGroup) {
                    Craft::info(Craft::t('magic-login', 'User Group already appears to have been deleted.'), __METHOD__);
                    return;
                }

                $groupDeleted = Craft::$app
                    ->getUserGroups()
                    ->deleteGroup($magicLoginGroup);

                if (!$groupDeleted) {
                    // Log error.
                    Craft::warning(Craft::t('magic-login', 'Could not delete Magic Login User group.'), __METHOD__);
                    return;
                }

                Craft::info(Craft::t('magic-login', 'Deleted Magic Login User Group.'), __METHOD__);
            }
        );

        // EVENT_AFTER_UNINSTALL_PLUGIN

        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info(
            Craft::t(
                'magic-login',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'magic-login/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
