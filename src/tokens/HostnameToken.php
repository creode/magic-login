<?php

namespace creode\magiclogin\tokens;

use Craft;

class HostnameToken implements SettingsTokenInterface
{
    /**
     * @inheritdoc
     */
    public function getTokenKey(): string
    {
        return '[[hostName]]';
    }

    /**
     * @inheritdoc
     */
    public function getTokenDescription(): string
    {
        return 'This token is replaced by the hostname of the current website.';
    }

    /**
     * @inheritdoc
     */
    public function replaceToken($value): string
    {
        return str_replace($this->getTokenKey(), Craft::$app->getRequest()->getHostInfo(), $value);
    }
}
