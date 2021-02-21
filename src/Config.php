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
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Config;

class Config implements \ArrayAccess
{

    /**
     * The config loader to use
     * @var LoaderInterface
     */
    protected LoaderInterface $loader;

    /**
     * The config environment to use
     * @var string
     */
    protected string $env;

    /**
     * The config items loaded
     * @var array
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
        return $this->get($key) !== null;
    }

    /**
     * Return the configuration value for the given key
     * @param  string $key     the name of the config item
     * @param  mixed $default the default value if can not find the config item
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        list($group, $item) = $this->parseKey($key);
        $this->load($group);

        return $this->getValue($this->items, $key, $default);
    }

    /**
     * Set the configuration value for the given key
     * @param  string $key     the name of the config item
     * @param  mixed $value the configuration value
     * @return void
     */
    public function set(string $key, $value): void
    {
        list($group, $item) = $this->parseKey($key);

        // We'll need to go ahead and lazy load each configuration groups even when
        // we're just setting a configuration item so that the set item does not
        // get overwritten if a different item in the group is requested later.
        $this->load($group);

        if (is_null($item)) {
            $this->items[$group] = $value;
        } else {
            $this->setValue($this->items[$group], $item, $value);
        }
    }

    /**
     * Return all the configuration items
     * @return array
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
     * @return Config
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
     * @return Config
     */
    public function setLoader(LoaderInterface $loader): self
    {
        $this->loader = $loader;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
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
     * @return array
     */
    protected function parseKey(string $key): array
    {
        if (($pos = strpos($key, '.')) === false) {
            return [$key, null];
        }
        return [substr($key, 0, $pos), substr($key, $pos + 1)];
    }

    /**
     * Get an item value using "dot" notation.
     * @param  array  $items
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    protected function getValue(array $items, ?string $key, $default)
    {
        if (is_null($key)) {
            return $items;
        }

        if (array_key_exists($key, $items)) {
            return $items[$key];
        }

        foreach (explode('.', $key) as $name) {
            if (!is_array($items) || !array_key_exists($name, $items)) {
                return $default;
            }
            /** @var mixed */
            $items = $items[$name];
        }

        return $items;
    }

    /**
     * Set an item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     * @param  array  $items
     * @param  string $key
     * @param  mixed $value
     * @return array
     */
    protected function setValue(array &$items, ?string $key, $value): array
    {
        if (is_null($key)) {
            return $items = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!array_key_exists($key, $items) || !is_array($items[$key])) {
                $items[$key] = [];
            }
            $items = & $items[$key];
        }

        $items[array_shift($keys)] = $value;

        return $items;
    }
}
