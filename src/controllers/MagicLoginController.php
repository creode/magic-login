<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin\controllers;

use Craft;
use DateTime;
use craft\elements\User;
use craft\web\Controller;
use creode\magiclogin\MagicLogin;
use yii\web\NotFoundHttpException;

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
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = [
        'login',
        'login-form',
        'register',
        'register-form',
        'auth',
        'login-link-sent',
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
        if (Craft::$app->getUser()->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->redirect($generalConfig->postLoginRedirect);
        }

        return $this->renderTemplate('magic-login/_login-form');
    }

    /**
     * Handles posted data and logs a user in.
     *
     * @return string
     */
    public function actionLogin()
    {
        $this->requirePostRequest();

        $loginUrl = MagicLogin::$plugin
            ->magicLoginAuthService
            ->getLoginPath();

        if (Craft::$app->getUser()->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->setSuccessFlash(\Craft::t('magic-login', 'You are already logged in.'));
            return $this->redirect($generalConfig->postLoginRedirect);
        }

        $email = Craft::$app
            ->getRequest()
            ->getRequiredParam('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // TODO: Maybe set this to be configurable in future.
            $this->setFailFlash(\Craft::t('magic-login', 'Please enter a valid email address.'));
            return;
        }

        $link = MagicLogin::$plugin
            ->magicLoginAuthService
            ->createMagicLogin($email);

        if (!$link) {
            // If we couldn't generate a link (likely because a user doesn't exist).
            // This request is actually unsuccessful however to a user on the frontend
            // we are making it look successful to prevent someone validating if email
            // address' exist on the website.

            // TODO: To assist the user in the process we could alternatively
            // send off an email to tell that they need to register first.
            // Although this might be considered spam since we could email
            // random people without consent. Perhaps add this as a
            // configurable option for the system.
            return $this->renderTemplate('magic-login/_login-link-sent');
        }
        
        $email_sent = MagicLogin::$plugin->magicLoginAuthService->sendMagicLoginLink($link, $email);
        if (!$email_sent) {
            $this->setFailFlash(
                \Craft::t(
                    'magic-login',
                    'Magic link could not be sent.'
                )
            );

            return $this->redirect($loginUrl);
        }

        return $this->renderTemplate('magic-login/_login-link-sent');
    }

    /**
     * Renders a template stating that the magic login link has been sent.
     *
     * @return string
     */
    public function actionLoginLinkSent()
    {
        if (Craft::$app->getUser()->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->redirect($generalConfig->postLoginRedirect);
        }

        return $this->renderTemplate('magic-login/_login-link-sent');
    }

    /**
     * Renders the Register form.
     *
     * @return string
     */
    public function actionRegisterForm()
    {
        if (Craft::$app->getUser()->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->redirect($generalConfig->postLoginRedirect);
        }

        $userConfig = Craft::$app->getProjectConfig()->get('users') ?? [];
        if (! $userConfig['allowPublicRegistration']) {
            throw new NotFoundHttpException();
        }

        return $this->renderTemplate('magic-login/_register-form');
    }

    /**
     * Register or login user.
     *
     * @return string
     */
    public function actionRegister()
    {
        $this->requirePostRequest();

        $this->request->setBodyParams(
            array_merge(
                $this->request->getBodyParams(),
                ['magicLoginRegistration' => true]
            )
        );

        $userSettings = Craft::$app->getProjectConfig()->get('users');
        if (!$userSettings['allowPublicRegistration']) {
            throw new NotFoundHttpException();
        }

        if (Craft::$app->getUser()->getIdentity()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $this->setSuccessFlash(\Craft::t('magic-login', 'You are already logged in.'));
            return $this->redirect($generalConfig->postLoginRedirect);
        }

        $email = Craft::$app
            ->getRequest()
            ->getRequiredParam('email');

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (! $generalConfig->useEmailAsUsername) {
            $this->setFailFlash(\Craft::t('magic-login', 'Please enter a valid username.'));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // TODO: Maybe set this to be configurable in future.
            $this->setFailFlash(\Craft::t('magic-login', 'Please enter a valid email address.'));
            return;
        }

        // Lookup email address - do we have a user?
        $user = User::findOne(['email' => $email]);
        if ($user) {
            return Craft::$app->runAction('magic-login/magic-login/login');
        }

        // Save the user
        Craft::$app->runAction('users/save-user');

        // Handle users which can be logged in automatically.
        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser) {
            return $this->redirectToPostedUrl(null, 'magic-login/login-link-sent');
        }

        // Send the new user a magic login link email.
        Craft::$app->runAction('magic-login/magic-login/login');

        // Render the login_link_sent template.
        return $this->redirectToPostedUrl(null, 'magic-login/login-link-sent');
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
        // Get Authorization Record by Public Key.
        $authRecord = MagicLogin::$plugin
            ->magicLoginAuthService
            ->getAuthorisationRecord($publicKey);

        $loginUrl = MagicLogin::$plugin
            ->magicLoginAuthService
            ->getLoginPath();
        
        // If we can't find record trigger a failure.
        if (!$authRecord) {
            // Throw an error.
            $this->setFailFlash(Craft::t('magic-login', 'Invalid login link provided.'));

            return $this->redirect($loginUrl);
        }

        // If we are logged in then redirect and delete the record.
        if (Craft::$app->getUser()->getIdentity()) {
            Craft::$app->session->setNotice(Craft::t('magic-login', 'You are already logged in.'));
            $authRecord->delete();
            return $this->redirect($authRecord->redirectUrl);
        }

        // Get the user and magic link group.
        $user = User::find()
            ->id($authRecord->userId)
            ->status(null)
            ->one();
        
        // If we can't find record trigger a failure.
        if (!$user) {
            // Throw an error.
            $this->setFailFlash(Craft::t('magic-login', 'Invalid login link provided.'));
            return $this->redirect($loginUrl);
        }

        $magicLoginGroup = Craft::$app
            ->getUserGroups()
            ->getGroupByHandle(MagicLogin::MAGIC_LOGIN_USER_GROUP_HANDLE);

        // If we have the magic login group and a user isn't in it then mark it as disabled.
        if ($magicLoginGroup && !$user->isInGroup($magicLoginGroup)) {
            $this->setFailFlash(
                Craft::t(
                    'magic-login',
                    'Magic login is disabled, please contact an admin if you feel this is in error.'
                )
            );
            return $this->redirect($loginUrl);
        }

        // Check the signature.
        $generatedSignature = MagicLogin::$plugin
            ->magicLoginAuthService
            ->generateSignature($authRecord->privateKey, $publicKey, $timestamp);

        if ($generatedSignature !== $signature) {
            // Signatures don't match. Throw an error.
            $this->setFailFlash(Craft::t('magic-login', 'Invalid login link provided.'));
            Craft::warning('User attempted to login with invalid signature.', __METHOD__);
            return $this->redirect($loginUrl);
        }

        // Check if timestamp is within bounds set by plugin configuration
        $linkExpiryAmount = MagicLogin::getInstance()->getSettings()->linkExpiry;
        $dateCreatedObject = new DateTime($authRecord->dateCreated, new \DateTimeZone('UTC'));
        $expiryTimestamp = $dateCreatedObject->getTimestamp() + ($linkExpiryAmount * 60);
        if (time() > $expiryTimestamp) {
            // Link expired, throw an error.
            $this->setFailFlash(
                Craft::t(
                    'magic-login',
                    'Login Link has expired, please login and try the link again.'
                )
            );
            return $this->redirect($loginUrl);
        }

        $userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $requireEmailVerification = $userSettings['requireEmailVerification'] ?? true;

        // If we require verification and user is not verified, then verify them.
        if ($requireEmailVerification && !$user->suspended) {
            $userService = Craft::$app->users;
            $userService->activateUser($user);
        }

        // Attempt to login the user.
        $loggedIn = Craft::$app->getUser()->loginByUserId($user->id);

        // If we can't login there was an error in Craft.
        if (!$loggedIn) {
            Craft::warning(
                'An error occured when trying to login user with user id: ' . $authRecord->userId,
                __METHOD__
            );
            $this->setFailFlash(Craft::t('magic-login', 'Unable to login. Please try again later.'));
            return $this->redirect($loginUrl);
        }

        // Increment the access count.
        $authRecord->accessCount++;
        $authRecord->save();

        if ($authRecord->hasExpired()) {
            // Remove the auth record since we are logged in now.
            $authRecord->delete();
        }

        // Redirect user to the url provided by the login page.
        return $this->redirect($authRecord->redirectUrl);
    }
}
