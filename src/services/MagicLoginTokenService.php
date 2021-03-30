<?php

namespace creode\magiclogin\services;

use craft\base\Component;
use creode\magiclogin\events\RegisterTokensEvent;

class MagicLoginTokenService extends Component
{
    const EVENT_REGISTER_TOKENS = 'register-tokens';

    /**
     * List of available Settings Tokens.
     *
     * @var string[]
     */
    private $tokens = [];

    public function __construct()
    {
        $event = new RegisterTokensEvent([
            'tokens' => [],
        ]);

        $this->trigger(self::EVENT_REGISTER_TOKENS, $event);
        $this->tokens = $event->tokens;
    }

    /**
     * Registers a token to the tokens array.
     *
     * @param string $tokenClassName
     * @return void
     */
    public function registerToken(string $tokenClassName)
    {
        $this->tokens[] = $tokenClassName;
    }
    
    /**
     * Gets a list of available token replacement classes.
     *
     * @return string[]
     */
    public function getAvailableTokens()
    {
        return $this->tokens;
    }

    /**
     * Gets a list of tokens as an object so that properties can be accessed on them.
     *
     * @return \creode\magiclogin\helpers\settings\SettingsTokenInterface[]
     */
    public function getAvailableTokenObjects()
    {
        $availableTokens = $this->getAvailableTokens();
        return array_map(function ($tokenClass) {
            return new $tokenClass;
        }, $availableTokens);
    }

    /**
     * Runs a token through a defined set of helper classes in order
     * to implement replacements.
     *
     * @param string $value
     * @return string
     */
    public function replaceTokens($value)
    {
        foreach ($this->getAvailableTokenObjects() as $tokenClass) {
            $value = $tokenClass->replaceToken($value);
        }

        return $value;
    }
}
