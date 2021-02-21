<?php

/**
 * Platine Config
 *
 * Platine Config is the library used to manage the application
 * configuration based on differents loaders
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
 *  @file FileLoader.php
 *
 *  The Configuration Loader class uses by default PHP file as
 *  application managed configuration
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

class FileLoader implements LoaderInterface
{

    /**
     * The application base path to use for
     * configuration scanning
     * @var string
     */
    protected string $path;

    /**
     * Create new instance of file loader
     * @param string $path the base path to use
     */
    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $environment, string $group): array
    {
        $configPath = '';
        $items = [];

        foreach ($this->parse($environment) as $env) {
            $configPath .= $env ? $env . DIRECTORY_SEPARATOR : '';
            $file = sprintf('%s%s%s.php', $this->path, $configPath, $group);
            if (is_file($file)) {
                $items = $this->merge($items, $this->readFile($file));
            }
        }

        return $items;
    }

    /**
     * Return the file path
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the file path
     *
     * @param string $path
     * @return FileLoader
     */
    public function setPath(string $path): self
    {
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Read the content for the given file
     * @param  string $file the file path to read
     * @return array       the content of file in array
     */
    protected function readFile(string $file): array
    {
        /** @var array */
        $items = include $file;
        return is_array($items) ? $items : [];
    }

    /**
     * Split the environment at dots or slashes creating
     * an array of namespaces to look through
     *
     * @param  string $env
     * @return array
     */
    protected function parse(string $env): array
    {
        $environments = array_filter(preg_split('/(\/|\.)/', $env));
        array_unshift($environments, '');
        return $environments;
    }

    /**
     * Merge two array items
     * @param  array  $items1
     * @param  array  $items2
     * @return array
     */
    protected function merge(array $items1, array $items2): array
    {
        return array_replace_recursive($items1, $items2);
    }
}
