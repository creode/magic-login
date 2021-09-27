<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

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
