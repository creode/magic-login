<?php

namespace creode\magiclogin\events;

use yii\base\Event;

class RegisterTokensEvent extends Event
{
    /**
     * List of available token replacement classes.
     * 
     * @var string[]
     */
    public $tokens;
}
