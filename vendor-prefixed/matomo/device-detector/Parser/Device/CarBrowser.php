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

namespace Blucube\SWSTATS\DeviceDetector\Parser\Device;

/**
 * Class CarBrowser
 *
 * Device parser for car browser detection
 */
class CarBrowser extends AbstractDeviceParser
{
    /**
     * @var string
     */
    protected $fixtureFile = 'regexes/device/car_browsers.yml';

    /**
     * @var string
     */
    protected $parserName = 'car browser';

    /**
     * @inheritdoc
     */
    public function parse(): ?array
    {
        if (!$this->preMatchOverall()) {
            return null;
        }

        return parent::parse();
    }
}
