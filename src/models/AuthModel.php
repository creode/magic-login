<?php
/**
 * Magic Login plugin for Craft CMS 3.x
 *
 * A plugin which sits on top of the existing 
 *
 * @link      https://www.creode.co.uk
 * @copyright Copyright (c) 2021 Creode
 */

namespace creode\magiclogin\models;

use creode\magiclogin\MagicLogin;

use Craft;
use craft\base\Model;

/**
 * AuthModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Creode
 * @package   MagicLogin
 * @since     1.0.0
 */
class AuthModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some model attribute
     *
     * @var string
     */
    public $someAttribute = 'Some Default';

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
        return [
            ['someAttribute', 'string'],
            ['someAttribute', 'default', 'value' => 'Some Default'],
        ];
    }
}
