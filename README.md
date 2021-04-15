# Magic Login plugin for Craft CMS 3.x

A Magic Link plugin which sits on top of the existing user sign in and registration process.

![Plugin Logo](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS Pro 3.5.0 or later in order to take advantage of the [Template Roots](https://craftcms.com/docs/3.x/extend/template-roots.html#plugin-control-panel-templates) feature.

Also due to the User based functionality of this plugin, we require a Craft Pro License to be installed.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require creode/magic-login

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Magic Login.

## Magic Login Overview

The Magic Login plugin is a simple plugin that sits on top of the existing user login journey. The plugin provides a way for users to register and login using a unqiue one time url that is sent out to the provided email address, to verify the identity of a user.

The main aim of this plugin is to add simple, extendible functionality utilising as much of the existing login and registration journey as possible. The plugin was built with customisation in mind, allowing all frontend templates to be customised to the users liking. For those people who just want this functioning, simple styled templates have been provided as default to get you up and running fast.

## Configuring Magic Login

The Magic Login plugin comes with a few different configuration options as standard with more on the way as the plugin continues to mature. These are the following:

 - Random Password Length - (Default Value: 16, Min: 16)
 - Login Link Timeout - How long a link lasts before it expires (Default Value: 15 mins)
 - Magic Link Email Subject - (Default Value: Magic Login Link)

## Using Magic Login

Documented in the sections below are tips on using the magic login plugin within your existing project. This covers overwriting their templates, the list of routes provided by this plugin, using the existing stylesheets with your overwritten templates and how to include existing templates as partials in your project.

### Overwriting magic login templates

All of the templates on this plugin are overwritable using the [Template Roots](https://craftcms.com/docs/3.x/extend/template-roots.html#plugin-control-panel-templates) feature of Craft. The templates you can overwrite are listed below:

 - [magic-login/_login-form.twig](https://github.com/creode/magic-login/blob/1.x/src/templates/magic-login/_login-form.twig) - Main Login form rendered at /magic-login/login
 - [magic-login/_login-link_sent.twig](https://github.com/creode/magic-login/blob/1.x/src/templates/magic-login/_login-link_sent.twig) - Shows link sent form once a user attempts to login
 - [magic-login/_register-form.twig](https://github.com/creode/magic-login/blob/1.x/src/templates/magic-login/_register-form.twig) - Main Registration form rendered at /magic-login/register
 - [magic-login/emails/_login.twig](https://github.com/creode/magic-login/blob/1.x/src/templates/magic-login/emails/_login.twig) - Email template sent out to a user which contains the Magic Login Link

If you follow that folder structure inside your sites /templates file any of the above templates can be completely re-written in order to be configured or styled however you would like.

### Plugin Routes

A few routes are defined as part of this plugin:

 - magic-login/register - Basically renders the standard registration form.
 - magic-login/login - Renders a basic login form.
 - magic-login/auth/{publicKey}/{timestamp}/{signature} - Handles the authorisation process of an existing magic link.

Whilst we have the following routes this plugin aims to also implement functionality on top of the existing user registration routes so a number of the form submission places are the same as if you were trying to register a user normally.

### Include Asset Bundle

As part of this plugin we use asset bundles for styling the default templates. If you are overriding these templates but plan on including our existing styling you can do so by adding the following asset bundle into your own templates.

`{% do view.registerAssetBundle("creode\\magiclogin\\assetbundles\\magiclogin\\MagicLoginAsset") %}`

### Include Template

We are also aware with this plugin that sometimes you may want to inject the existing templates into your own template code. Craft and twig comes with a way of doing this by adding the following to your template:

`{% include 'magic-login/_login-form' %}`

## Technical Features / Under the Hood

### Password Generation

In order to get the magic link functionality to work we have had to make a few assumptions of functionality under the hood. the biggest hurdle is that by default Craft requires a user to set a password when registering a new user. In order to keep this process as streamlined as possible we handle the generation of a random password behind the scenes. The length of this password can be configured for security but can go no lower than 16 characters. We see Magic Login as more of an addon and try not to make to many assumptions on how it should be used. We therefore also allow a user to be registered using a password of their choosing if required giving you the option to use it or not.

### Magic Login User Group

In order to distinguish users which are allowed to use Magic Login functionality we create a new user group titled "Magic Login". This group was created so that we can target users using it with updates in the future in required. Another route we could have taken would be to assign a permission to a user but this felt a little too loose but maybe this is an option for the future.

## Running Magic Login Test Suite ##

From the root of this plugin ensure to install the dependencies using composer:

`composer install`

From there you can access the codecept executable with the following:

`php vendor/bin/codecept run`

## Magic Login Roadmap

Some things to do, and ideas for potential features:

* Set Email Validation Error Message to be Configurable in the CMS
* Add extra configuration and logic if a user tries to login with an email address that doesn't exist
* Once an issue around automated testing and plugin initialisation is fixed, clean up the plugin. See Craft issue [here](https://github.com/craftcms/cms/issues/7724)
* Allow you to publish the default templates to the right location within your project to get started quickly
* Potentially create a new permission used to determine if a user is allowed to perform Magic Login

Brought to you by [Creode](https://www.creode.co.uk)
