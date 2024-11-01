<?php

/**
 * Device Detector - The Universal Device Detection library for parsing User Agents
 *
 * @link https://matomo.org
 *
 * @license http://www.gnu.org/licenses/lgpl.html LGPL v3 or later
 *
 * Modified by __root__ on 28-March-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace Blucube\SWSTATS\DeviceDetector\Parser\Client\Hints;

use Blucube\SWSTATS\DeviceDetector\Parser\AbstractParser;

class BrowserHints extends AbstractParser
{
    /**
     * @var string
     */
    protected $fixtureFile = 'regexes/client/hints/browsers.yml';

    /**
     * @var string
     */
    protected $parserName = 'BrowserHints';

    /**
     * Get browser name if is in collection
     *
     * @return array|null
     */
    public function parse(): ?array
    {
        if (null === $this->clientHints) {
            return null;
        }

        $appId = $this->clientHints->getApp();
        $name  = $this->getRegexes()[$appId] ?? null;

        if ('' === (string) $name) {
            return null;
        }

        return [
            'name' => $name,
        ];
    }
}
