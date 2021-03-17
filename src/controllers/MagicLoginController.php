<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
 *
 * @link      https://www.creode.co.uk
 * @copyright Copyright (c) 2021 Creode
 */

namespace creode\magiclogin\controllers;

use Craft;

use craft\web\View;
use craft\elements\User;
use craft\web\Controller;
use creode\magiclogin\MagicLogin;
use yii\web\ForbiddenHttpException;
use RandomLib\Factory as RandomLibFactory;

/**
 * MagicLogin Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Creode
 * @package   MagicLogin
 * @since     1.0.0
 */
class MagicLoginController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * Allow the following routes to be anonymous.
     * 
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [
        'login',
        'login-form',
        'register',
        'register-form',
        'auth'
    ];

    // Public Methods
    // =========================================================================

    /**
     * Render the login form.
     *
     * @return string
     */
    public function actionLoginForm()
    {
        // TODO: If already logged in maybe redirect somewhere?

        return \Craft::$app->view->renderTemplate('magic-login/login-form');
    }

    /**
     * Handles posted data and logs a user in.
     *
     * @return string
     */
    public function actionLogin()
    {
        $this->requirePostRequest();

        $emailOrUsername = Craft::$app
            ->getRequest()
            ->getRequiredParam('emailOrUsername');
    
        $result = MagicLogin::$plugin
            ->magicLoginAuthService
            ->createMagicLogin($emailOrUsername);

        if (!$result) {
            // TODO: Trigger some kind of error or say if email exists then email has 
            // been sent (this is based on configuration).
        }

        return $result;
    }

    /**
     * Renders the Register form.
     *
     * @return void
     */
    public function actionRegisterForm()
    {
        // TODO: If already logged in maybe redirect somewhere?

        return \Craft::$app->view->renderTemplate('magic-login/login-form');
    }

    /**
     * Triggers the rendering.
     *
     * @return void
     */
    public function actionRegister()
    {
        $this->requirePostRequest();

        $userSession = Craft::$app->getUser();
        $userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $currentUser = $userSession->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($currentUser) {
            // TODO: What should I do if we are already logged in.
            throw new \Exception('Already logged in...');
        }

        $email = Craft::$app
            ->getRequest()
            ->getRequiredParam('email');

        // Check public registration is allowed within Craft.
        $allowPublicRegistration = $userSettings['allowPublicRegistration'] ?? false;
        if (!$allowPublicRegistration) {
            throw new ForbiddenHttpException('Public registration is not allowed');
        }

        // TODO: Check user doesn't already exist.

        // Setup the new user.
        $user = new User();
        $user->suspended = true;
        $user->pending = true;
        $user->email = $email;

        // If the user should be suspended by default then allow them to be.
        if ($userSettings['suspendByDefault'] ?? false) {
            $user->suspended = true;
        }

        // If we have a username body param then use it. If not use email.
        $user->username = $this->request->getBodyParam(
            'username',
            ($user->username ?: $user->email)
        );
        
        $generator = MagicLogin::$plugin
            ->magicLoginRandomGeneratorService
            ->getHighStrengthGenerator();

        // TODO: Make the length configurable via options.
        $user->password = $generator->generateString(16);

        // Manually validate the user so we can pass $clearErrors=false
        // TODO: Improve error handling here.
        if (!$user->validate(null, false)
            || !Craft::$app->getElements()->saveElement($user, false)
        ) {
            Craft::info('User not saved due to validation error.', __METHOD__);
        }

        // Assign them to the default user group.
        Craft::$app->getUsers()->assignUserToDefaultGroup($user);
        
        // TODO: Assign them to the Magic Login group.
    }

    /**
     * Handle any authentication check needed within the application.
     * e.g.: actions/magic-login/magic-login/auth
     * 
     * @param $publicKey Public key associated with the request.
     * @param $timestamp Timestamp when the request was created.
     * @param $signature Signature of the keys.
     *
     * @return mixed
     */
    public function actionAuth($publicKey, $timestamp, $signature)
    {
        // TODO: Validate user login details.

        // TODO: Check the expiry

        // TODO: Log the user in by their uid taken from database.

        // TODO: Activate the user if not already.
        // Craft::$app->getUsers()->activateUser($user);
    }
}
