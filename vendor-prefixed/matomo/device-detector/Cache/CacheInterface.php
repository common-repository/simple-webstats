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

namespace Blucube\SWSTATS\DeviceDetector\Cache;

interface CacheInterface
{
    /**
     * @param string $id
     *
     * @return mixed
     */
    public function fetch(string $id);

    /**
     * @param string $id
     *
     * @return bool
     */
    public function contains(string $id): bool;

    /**
     * @param string $id
     * @param mixed  $data
     * @param int    $lifeTime
     *
     * @return bool
     */
    public function save(string $id, $data, int $lifeTime = 0): bool;

    /**
     * @param string $id
     *
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * @return bool
     */
    public function flushAll(): bool;
}
