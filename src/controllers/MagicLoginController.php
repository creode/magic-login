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

use creode\magiclogin\MagicLogin;

use Craft;
use craft\web\Controller;

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

        return $result;
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
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
        $result = 'Hi!';
        return $result;
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/magic-login/magic-login/do-something
     *
     * @return mixed
     */
    public function actionDoSomething()
    {
        $result = 'Welcome to the MagicLoginController actionDoSomething() method';

        return $result;
    }
}
