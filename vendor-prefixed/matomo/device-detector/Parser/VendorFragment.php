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

namespace Blucube\SWSTATS\DeviceDetector\Parser;

/**
 * Class VendorFragments
 *
 * Device parser for vendor fragment detection
 */
class VendorFragment extends AbstractParser
{
    /**
     * @var string
     */
    protected $fixtureFile = 'regexes/vendorfragments.yml';

    /**
     * @var string
     */
    protected $parserName = 'vendorfragments';

    /**
     * @var string
     */
    protected $matchedRegex = null;

    /**
     * @inheritdoc
     */
    public function parse(): ?array
    {
        foreach ($this->getRegexes() as $brand => $regexes) {
            foreach ($regexes as $regex) {
                if ($this->matchUserAgent($regex . '[^a-z0-9]+')) {
                    $this->matchedRegex = $regex;

                    return ['brand' => $brand];
                }
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getMatchedRegex(): ?string
    {
        return $this->matchedRegex;
    }
}
