<?php

declare(strict_types=1);

namespace Platine\Test\Config;

use Platine\Config\FileLoader;
use org\bovigo\vfs\vfsStream;
use Platine\Dev\PlatineTestCase;

/**
 * FileLoader class tests
 *
 * @group core
 * @group config
 */
class FileLoaderTest extends PlatineTestCase
{

    protected $vfsRoot;
    protected $vfsFilesPath;

    protected function setUp(): void
    {
        parent::setUp();

        //need setup for each test
        $this->vfsRoot = vfsStream::setup();
        $this->vfsFilesPath = vfsStream::newDirectory('test_files')->at($this->vfsRoot);
    }

    public function testConstructor(): void
    {
        $path = $this->vfsFilesPath->url();
        $fl = new FileLoader($path);
        $this->assertEquals($path . DIRECTORY_SEPARATOR, $fl->getPath());
    }

    public function testSetGetPath(): void
    {
        $path = $this->vfsFilesPath->url();

        $fl = new FileLoader($path);
        $this->assertEquals($path . DIRECTORY_SEPARATOR, $fl->getPath());
        $fl->setPath('foo');
        $this->assertEquals('foo' . DIRECTORY_SEPARATOR, $fl->getPath());
    }

    public function testLoad(): void
    {
        $path = $this->vfsFilesPath->url();
        $content = $this->getDefaultConfigContent();
        $file = $this->createVfsFile('app.php', $this->vfsFilesPath, $content);
        $fl = new FileLoader($path);

        $items = $fl->load('', 'app');
        $this->assertNotEmpty($items);
        $this->assertArrayHasKey('db', $items);
        $this->assertArrayHasKey('local', $items['db']);
        $this->assertArrayHasKey('host', $items['db']['local']);
        $this->assertEquals($items['db']['local']['host'], 'localhost');
    }

    private function getDefaultConfigContent()
    {
        return "
                    <?php
                    return array(
                        'db' => array(
                            'local' => array(
                                'host' => 'localhost'
                            ),
                            'shared' => array(
                                'host' => '192.168.10.1'
                            ),
                            'balancer' => array(
                                'host' => '100.111.222.2'
                            )
                        )
                    );";
    }
}
