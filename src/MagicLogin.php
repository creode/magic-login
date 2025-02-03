<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin;

use Craft;
use craft\web\View;

use yii\base\Event;
use craft\base\Plugin;
use craft\mail\Mailer;
use yii\mail\MailEvent;
use craft\elements\User;
use craft\web\UrlManager;
use yii\base\ActionEvent;
use craft\enums\CmsEdition;
use craft\models\UserGroup;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\controllers\UsersController;
use creode\magiclogin\models\Settings;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterTemplateRootsEvent;
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
    public string $schemaVersion = '2.1.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = true;
    
    /**
     * User Group Handle.
     */
    public const MAGIC_LOGIN_USER_GROUP_HANDLE = 'magicLogin';

    /**
     * @inheritdoc
     */
    public CmsEdition $minCmsEdition = CmsEdition::Pro;

    // Public Methods
    // =========================================================================
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

        // Trigger something after installation.
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            [$this, 'handleAfterPluginInstall']
        );

        $this->registerComponents();
        $this->setTemplateRoots();
        $this->registerSiteRoutes();

        // Runs function before a UserController action is executed.
        Event::on(
            UsersController::class,
            UsersController::EVENT_BEFORE_ACTION,
            function (ActionEvent $event) {
                if (! $this->request->getBodyParam('magicLoginRegistration')) {
                    return false;
                }

                if ($event->sender->action->actionMethod === 'actionSaveUser') {
                    $this->handleMagicLoginBeforeUserSave($event);
                }
            }
        );

        // Runs function after a UserController action is executed.
        Event::on(
            UsersController::class,
            UsersController::EVENT_AFTER_ACTION,
            function (ActionEvent $event) {
                if (! $this->request->getBodyParam('magicLoginRegistration')) {
                    return false;
                }

                if ($event->sender->action->actionMethod === 'actionSaveUser') {
                    $this->handleMagicLoginAfterUserSave($event);
                }
            }
        );

        // Attempt to prevent user from getting activation emails.
        Event::on(
            Mailer::class,
            Mailer::EVENT_BEFORE_SEND,
            function (MailEvent $event) {
                if (! $this->request->getBodyParam('magicLoginRegistration')) {
                    return false;
                }

                if ($event->message->key == 'account_activation') {
                    $event->isValid = false;
                }
            }
        );

        // Trigger something after uninstallation.
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_UNINSTALL_PLUGIN,
            [$this, 'handleAfterPluginUninstall']
        );

        Craft::info(
            Craft::t(
                'magic-login',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Handles any magic login functionality that is triggered before a user is saved.
     *
     * @param ActionEvent $event
     * @return void
     */
    public function handleMagicLoginBeforeUserSave(ActionEvent $event)
    {
        $event->sender->requirePostRequest();

        // If user is logged in already then don't proceed.
        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();
        if ($currentUser) {
            return;
        }

        // If we are updating an existing user then skip this.
        $userId = $this->request->getBodyParam('userId');
        if ($userId) {
            return;
        }

        // Require email.
        $email = $this->request->getRequiredBodyParam('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // TODO: Maybe set this to be configurable in future.
            $event->sender->setFailFlash(\Craft::t('magic-login', 'Please enter a valid email address.'));
            $event->isValid = false;
            return;
        }

        // If we already have a password set then we should stop function here.
        if ($this->request->getBodyParam('password')) {
            return;
        }

        // Generate a random password.
        $generator = $this->magicLoginRandomGeneratorService
            ->getMediumStrengthGenerator();
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

    /**
     * Runs some functionality after an action runs on the UserController.
     *
     * @param ActionEvent $event
     * @return void
     */
    public function handleMagicLoginAfterUserSave(ActionEvent $event)
    {
        $event->sender->requirePostRequest();
        
        // If we are updating an existing user then skip this.
        $userId = $this->request->getBodyParam('userId');
        if ($userId) {
            return;
        }

        // Require email.
        $email = $this->request->getRequiredBodyParam('email');

        // If we have not edited the existing user then we don't want to proceed.
        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();
        if ($currentUser && $currentUser->email !== $email) {
            return;
        }
        
        $user = User::find()
            ->status(null)
            ->email($email)
            ->one();

        // If we can't find user something must have happened.
        // We will stop here and allow things to run it's course.
        if (!$user) {
            return;
        }

        // Load in the Magic User Group By Handle.
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

        // Load in existing user groups and push them into array.
        // This is in the event a default group is set.
        $userGroupsToAssign = array_map(
            function ($group) {
                return $group->id;
            },
            $user->groups
        );

        // Make sure that we add the magic login group to this.
        $userGroupsToAssign[] = $magicLoginGroup->id;

        // Add Magic Login group to user.
        $addedToGroup = Craft::$app->getUsers()->assignUserToGroups(
            $user->id,
            $userGroupsToAssign
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

    /**
     * Handles functionality after a plugin is installed.
     *
     * @param PluginEvent $event
     * @return void
     */
    public function handleAfterPluginInstall(PluginEvent $event)
    {
        if ($event->plugin !== $this) {
            return;
        }

        // TODO: This will need removing once the check for beforeInstall can pass.
        if (Craft::$app->getEdition() !== CmsEdition::Pro) {
            Craft::$app->session->setError(
                Craft::t(
                    'magic-login',
                    'For this plugin to function correctly, you must have a pro license for Craft.'
                )
            );
            return;
        }

        // We were just installed
        $magicLoginUserGroup = new UserGroup();
        $magicLoginUserGroup->name = 'Magic Login';
        $magicLoginUserGroup->handle = self::MAGIC_LOGIN_USER_GROUP_HANDLE;
        $magicLoginUserGroup->description = Craft::t(
            'magic-login',
            'Users within this group were registered with magic login capabilities.'
        );

        $groupSaved = Craft::$app
            ->getUserGroups()
            ->saveGroup($magicLoginUserGroup);

        if (!$groupSaved) {
            Craft::warning(Craft::t('magic-login', 'Could not create Magic Login User group.'), __METHOD__);
        }

        Craft::info(Craft::t('magic-login', 'Created Magic Login User Group.'), __METHOD__);
    }

    /**
     * Runs functionality after a plugin is uninstalled.
     *
     * @param PluginEvent $event
     * @return void
     */
    public function handleAfterPluginUninstall(PluginEvent $event)
    {
        if ($event->plugin !== $this) {
            return;
        }

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

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        $view = Craft::$app->getView();
        $namespace = $view->getNamespace();
        $view->setNamespace('settings');
        $settingsHtml = $this->settingsHtml();
        $view->setNamespace($namespace);

        /** @var Controller $controller */
        $controller = Craft::$app->controller;

        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return $controller->renderTemplate('magic-login/settings', [
            'plugin' => $this,
            'settingsHtml' => $settingsHtml,
            'settings' => $this->getSettings(),
            'overrides' => array_keys($overrides),
        ]);
    }

    /**
     * Registers any routes the plugin might need.
     *
     * @return void
     */
    protected function registerSiteRoutes()
    {
        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['magic-login/login'] = 'magic-login/magic-login/login-form';
                $event->rules['magic-login/register'] = 'magic-login/magic-login/register-form';
                $event->rules['magic-login/login-link-sent'] = 'magic-login/magic-login/login-link-sent';
                $event->rules['magic-login/auth/<publicKey:\w+>/<timestamp:\d+>/<signature:\w+>'] = 'magic-login/magic-login/auth';
            }
        );
    }

    /**
     * Sets up any template roots required for the application.
     *
     * @return void
     */
    protected function setTemplateRoots()
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['magic-login'] = __DIR__ . '/templates/magic-login';
            }
        );
    }

    // Private Methods
    // =========================================================================

     /**
     * Set any components for this plugin.
     *
     * @return void
     */
    private function registerComponents()
    {
        $this->setComponents(
            [
                'magicLoginRandomGeneratorService' => MagicLoginRandomGeneratorService::class,
                'magicLoginAuthService' => MagicLoginAuthService::class,
            ]
        );
    }
}
