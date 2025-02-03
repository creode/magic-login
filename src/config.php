<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A Magic Link plugin which sits on top of the existing user sign in and registration process.
 *
 * @link      https://www.creode.co.uk
 * @copyright Copyright (c) 2021 Creode
 */

/**
 * Magic Login config.php
 *
 * This file exists only as a template for the Magic Login settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'magic-login.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // How long (in minutes) until a Magic Link expires.
    'linkExpiry' => 15,
    // Users in Craft still need a password this plugin therefore generates one. You can set the length of this here.
    'passwordLength' => 16,
    // What to display on the subject line for Magic Link emails.
    'authenticationEmailSubject' => 'Magic Login Link',
    // Rate Limit for how frequently a magic login email can be sent (in minutes).
    'emailRateLimit' => 5,
    // Amount of times a link can be accessed before it expires.
    'linkAccessLimit' => 1,
];
