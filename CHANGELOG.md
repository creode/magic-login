<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## 4.3.0 - 2024-08-20

### Features

* Allow registration process to log a user in if correct configuration has allowed for it. (cd5549)

### Bug Fixes

* Tests schema version bump (2348fe)

## 4.2.0 - 2024-07-15

### Features

* Add in functionality to allow a configurable amount of times a link can be accessed before it is expired. (65c274)
* Add migrations to count how many times a link has been accessed (fa28ee)

## 4.1.0 - 2024-06-25

### Features

* Disable settings field and display message to user if being overridden in config (1a5dbf)

## 4.0.0 - 2024-06-24

### ⚠ BREAKING CHANGES

* Bumps dependencies for magic login for initial craft 5 support. (4c5384)

### Bug Fixes

* Deprecated function. (823894)

## 3.1.1 - 2023-04-12

### Bug Fixes

* Add in various example configuration options available to the plugin (with defaults). (d0729d)
* Fixes an issue with email rate limiting caused by timezones. (6c91ad)

## 3.1.0 - 2023-04-12

### Features

* Adds the ability to rate limit magic login email sending. (2ae240)

### Bug Fixes

* Display the config menu in the admin panel. (b56eff)
* Update deprecated query function. (2ed201)

## 3.0.0 - 2022-09-16

### ⚠ BREAKING CHANGES

* Updates composer file to allow support for Craft 4. (b32ab2)

### Bug Fixes

* Adds type declarations to support Craft CMS 4 (5b3e3c)
* Fixes an error where tests are failing due to missing configuration variable. (97373a)
* Fixes issues from the test suite. (047764)
* Issues with test suite due to field and functionality changes in Craft Core. (48b589)
* Revert change introduced in wrong commit. (d77da5)

##### Ci

* Add missing environment variable. (c22e03)
* Allow releases to be created on new 3.x branch. (929627)
* Upgrade CI to run on newer versions of PHP. (b5cd6f)

## 2.0.2 - 2021-09-27
### Bug Fixes

* Allow service class to send email a magic login link. (43d823)
* Fixes bug left in after refactor. (845318)
* Fixes issues with template types. (dc0a2a)

## 2.0.1 - 2021-09-27
## 2.0.0 - 2021-08-24
### ⚠ BREAKING CHANGES

* Login path now uses one from craft config. (f5d74a)

### Features

* Extra test to check that backend user registration works (08c49d)
* Refactor tests and move login flow inside of registration one. (e1ac0c)

### Bug Fixes

* Coding standards (9965b0)
* Fix a bug with timezones and link expiry (4fc9e5)
* Fixes issue with failing tests. (b1743d)
* Issue caused by accidental left in code (ad8320)
* Issue with code caused when refactoring. (805ee4)
* Issue with passing object into another when string expected (7489f3)
* Issue with styles on login and register forms (40c0ad)
* Move some code around and add in extra check for events (6e13e2)
* Redirection now uses separate post variable. (8512de)
* Remove conventional commit patch since it is no longer required (d65434)
* Updates changelog to make it look nicer on craft plugin store. (6528ca)

## 1.0.5 - 2021-07-28
### Bug Fixes

* Broken release script issue (8ea570)

## 1.0.4 - 2021-07-28


### Bug Fixes

* Fixes changelog to match correct format (29e395)
* Patches in extra changelog functionality to conform to craft (38319c)

## 1.0.3 - 2021-07-26


### Bug Fixes

* Fixes changelog formatting ([fd1667](https://github.com/creode/magic-login/commit/fd16673682a133abb3d15ed11b5795cf7cff119b))

## 1.0.2 - 2021-07-26


### Bug Fixes

* Remove version from composer file as it caused tests to fail. ([89c491](https://github.com/creode/magic-login/commit/89c4915accc77d9164a370417883b6c87d651545))

## 1.0.1 - 2021-07-26


### Fixes

* Fixes a testing error with yii2 php 8 bug ([ec6696](git@github.com:creode/magic-login/commit/ec669692d7234f27f72c4e34df5e7abfc8882315))
* Make sure pr's and pushes for tests run on all branches. ([597e49](git@github.com:creode/magic-login/commit/597e490aaf87aa7f999f259cb74593d18a3ebade))
* Issue with PHP compatibility in latest craft version. ([4dd10f](git@github.com:creode/magic-login/commit/4dd10f8304091e746318187ac31ae9421d8baa7e))
* Makes test suite more more dynamic and removed markup dependency. ([cf72f4](git@github.com:creode/magic-login/commit/cf72f42038f55a111ce4651365a15dab067506ac))

## 1.0.0 - 2021-04-06


### Added

* Initial release
