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
 * Class Notebook
 *
 * Device parser for notebook detection in Facebook useragents
 */
class Notebook extends AbstractDeviceParser
{
    protected $fixtureFile = 'regexes/device/notebooks.yml';

    protected $parserName = 'notebook';

    /**
     * @inheritdoc
     */
    public function parse(): ?array
    {
        if (!$this->matchUserAgent('FBMD/')) {
            return null;
        }

        return parent::parse();
    }
}
