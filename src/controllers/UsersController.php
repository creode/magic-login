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

use creode\magiclogin\MagicLogin;
use craft\controllers\UsersController as CraftUsersController;

/**
 * Overwritten user controller class to help with our functionality.
 * 
 * @package MagicLogin
 * @author  Creode
 * @since   1.0.0
 */
class UsersController extends CraftUsersController
{
    /**
     * Overwrite the existing save action with some custom code.
     * 
     * @return \yii\web\Response|null 
     * @throws \yii\web\NotFoundHttpException if the requested user cannot be found
     * @throws \yii\web\BadRequestHttpException if attempting to create a client account, and one already exists
     * @throws \yii\web\ForbiddenHttpException if attempting public registration but public registration is not allowed
     */
    public function actionSaveUser()
    {
        // Require email.
        $this->requirePostRequest();
        
        $email = $this->request->getRequiredBodyParam('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // TODO: Set this to be configurable.
            $this->setFailFlash(\Craft::t('magic-login', 'Please enter a valid email address.'));
            return;
        }

        // TODO: What do we do if already registered?

        // TODO: Find a way to assign a magic link group to a user.

        // Generate a random password.
        $generator = MagicLogin::$plugin
            ->magicLoginRandomGeneratorService
            ->getMediumStrengthGenerator();

        // TODO: Make the length configurable.
        $password = $generator->generateString(16);
        $this->request->setBodyParams(
            array_merge(
                $this->request->getBodyParams(),
                [
                    'password' => $password,
                ]
            )
        );

        return parent::actionSaveUser();
    }
}