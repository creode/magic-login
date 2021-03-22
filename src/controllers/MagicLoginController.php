<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin\controllers;

use Craft;
use craft\controllers\UsersController;
use craft\web\View;
use craft\elements\User;
use craft\web\Controller;
use creode\magiclogin\MagicLogin;
use yii\web\ForbiddenHttpException;

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
        'auth',
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
        $userSession = Craft::$app->getUser();
        if ($userSession->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->redirect($generalConfig->postLoginRedirect);
        }

        return \Craft::$app->view->renderTemplate('magic-login/_login-form');
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
        // TODO: Use config variable for user logged in?
        $userSession = Craft::$app->getUser();
        if ($userSession->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->redirect($generalConfig->postLoginRedirect);
        }

        return \Craft::$app->view->renderTemplate('magic-login/_register-form');
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
