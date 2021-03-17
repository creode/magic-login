<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
 *
 * @copyright 2021 Creode
 * @link      https://www.creode.co.uk
 */

namespace creode\magiclogin\models;

use creode\magiclogin\MagicLogin;

use Craft;
use craft\base\Model;
use craft\validators\DateTimeValidator;

/**
 * AuthModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @package MagicLogin
 * @author  Creode <contact@creode.co.uk>
 * @since   1.0.0
 */
class AuthModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * ID of the user to authenticate.
     *
     * @var string
     */
    public $userId;

    /**
     * Public Key used in Authorisation.
     *
     * @var string
     */
    public $publicKey;

    /**
     * Private Key used in Authorisation.
     *
     * @var string
     */
    public $privateKey;

    /**
     * Timestamp used to determine if the link is still valid.
     *
     * @var int
     */
    public $expriyDate;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['publicKey', 'privateKey'], 'string'];
        $rules[] = [['userId'], 'number'];
        $rules[] = [['expiryDate'], DateTimeValidator::class];
        
        return $rules;
    }
}
