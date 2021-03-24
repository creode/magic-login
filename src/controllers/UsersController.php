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
        // If saving didn't work then redirect back since it
        // should have set a flash error message.
        $saveAction = parent::actionSaveUser();
        if ($saveAction === null) {
            return $saveAction;
        }

        return $saveAction;
    }
}
