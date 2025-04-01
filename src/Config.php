<?php

/**
 * Platine Config
 *
 * Platine Config is the library used to manage the application
 * configuration using differents loaders
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Config
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 *  @file Config.php
 *
 *  The Config class used to manage application configuration
 *
 *  @package    Platine\Config
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Config;

use ArrayAccess;
use Platine\Stdlib\Helper\Arr;

/**
 * Class Config
 * @package Platine\Config
 * @template T
 * @implements ArrayAccess<string, mixed>
 */
class Config implements ArrayAccess
{
    /**
     * The configuration loader to use
     * @var LoaderInterface
     */
    protected LoaderInterface $loader;

    /**
     * The configuration environment to use
     * @var string
     */
    protected string $env;

    /**
     * The configuration items loaded
     * @var array<string, mixed>
     */
    protected array $items = [];

    /**
     * Create new configuration instance
     * @param LoaderInterface $loader the loader to use
     * @param string          $env    the name of the environment
     */
    public function __construct(LoaderInterface $loader, string $env = '')
    {
        $this->loader = $loader;
        $this->env = $env;
    }

    /**
     * Check whether the configuration for given key exists
     * @param  string  $key the name of the key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Return the configuration value for the given key
     * @param  string $key the name of the configuration item
     * @param  mixed $default the default value if can not find the configuration item
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        list($group, ) = $this->parseKey($key);
        $this->load($group);

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Set the configuration value for the given key
     * @param  string $key the name of the configuration item
     * @param  mixed $value the configuration value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        list($group, $item) = $this->parseKey($key);

        // We'll need to go ahead and lazy load each configuration groups even when
        // we're just setting a configuration item so that the set item does not
        // get overwritten if a different item in the group is requested later.
        $this->load($group);

        if (is_null($item)) {
            $this->items[$group] = $value;
        } else {
            Arr::set($this->items[$group], $item, $value);
        }
    }

    /**
     * Return all the configuration items
     * @return array<string, mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Return the configuration current environment
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->env;
    }

    /**
     * Set the configuration environment
     *
     * @param string $env
     * @return $this
     */
    public function setEnvironment(string $env): self
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Return the configuration current loader
     * @return LoaderInterface
     */
    public function getLoader(): LoaderInterface
    {
        return $this->loader;
    }

    /**
     * Set the configuration loader
     *
     * @param LoaderInterface $loader
     * @return $this
     */
    public function setLoader(LoaderInterface $loader): self
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $key): void
    {
        $this->set($key, null);
    }

    /**
     * Load the configuration group for the key.
     * @param  string $group the name of group to load
     * @return void
     */
    protected function load(string $group): void
    {
        // If we've already loaded this collection, we will just bail out since we do
        // not want to load it again. Once items are loaded a first time they will
        // stay kept in memory within this class and not loaded from disk again.
        if (isset($this->items[$group])) {
            return;
        }
        $loaded = $this->loader->load($this->env, $group);

        if (!empty($loaded)) {
            $this->items[$group] = $loaded;
        }
    }

    /**
     * Parse the configuration key
     * @param  string $key the name of the key
     * @return array<int, mixed>
     */
    protected function parseKey(string $key): array
    {
        if (($pos = strpos($key, '.')) === false) {
            return [$key, null];
        }
        return [substr($key, 0, $pos), substr($key, $pos + 1)];
    }
}
