<?php

namespace creode\magiclogin\tokens;

interface SettingsTokenInterface
{
    /**
     * Gets the keys token used on the page.
     *
     * @return string
     */
    public function getTokenKey(): string;

    /**
     * Gets the descriptive function of the token e.g. Output the site hostname.
     *
     * @return string
     */
    public function getTokenDescription(): string;

    /**
     * Takes in a string and formats it based on the parser.
     *
     * @param string $text
     * @return string
     */
    public function replaceToken(string $text): string;
}
