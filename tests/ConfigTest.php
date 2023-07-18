<?php

declare(strict_types=1);

namespace Platine\Test\Config;

use org\bovigo\vfs\vfsStream;
use Platine\Config\Config;
use Platine\Config\FileLoader;
use Platine\Dev\PlatineTestCase;

/**
 * Config class tests
 *
 * @group core
 * @group config
 */
class ConfigTest extends PlatineTestCase
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
        $env = '';
        $c = new Config($fl, $env);
        $this->assertEquals($env, $c->getEnvironment());
        $this->assertEquals($fl, $c->getLoader());
        $this->assertEmpty($c->getEnvironment());
    }

    public function testSetGetEnvironment(): void
    {
        $fl = $this->getMockBuilder(FileLoader::class)
                ->disableOriginalConstructor()
                ->getMock();
        $env = '';
        $c = new Config($fl, $env);
        $this->assertEmpty($c->getEnvironment());
        $c->setEnvironment('dev');
        $this->assertEquals('dev', $c->getEnvironment());
    }

    public function testSetGetLoader(): void
    {
        $fl = $this->getMockBuilder(FileLoader::class)
                ->disableOriginalConstructor()
                ->getMock();
        $env = '';
        $c = new Config($fl, $env);
        $this->assertEquals($fl, $c->getLoader());

        $newLoader = $this->getMockBuilder(FileLoader::class)
                ->disableOriginalConstructor()
                ->getMock();
        $c->setLoader($newLoader);
        $this->assertEquals($newLoader, $c->getLoader());
    }

    public function testHas(): void
    {
        $path = $this->vfsFilesPath->url();
        $fl = new FileLoader($path);

        //Return false
        $env = '';
        $c = new Config($fl, $env);
        $this->assertFalse($c->has('foo_key_does_not_exists'));

        //Return true
        $file = $this->createVfsFile(
            'app.php',
            $this->vfsFilesPath,
            '<?php return array("name" => "foo");'
        );
        $this->assertTrue($c->has('app.name'));

        $file = $this->createVfsFile(
            'logger.php',
            $this->vfsFilesPath,
            '<?php return '
                . 'array("logger" => '
                . 'array("channel" => array("one" => "two")));'
        );
        $this->assertTrue($c->has('app.name')); // success because the item already cached
        $this->assertTrue($c->has('logger.logger.channel'));
    }

    public function testGet(): void
    {
        $path = $this->vfsFilesPath->url();
        $fl = new FileLoader($path);

        $env = '';
        $c = new Config($fl, $env);
        $this->assertEmpty($c->get('foo_key_does_not_exists'));

        //Using simple key
        $file = $this->createVfsFile('app.php', $this->vfsFilesPath, '<?php return array("name" => "foo");');
        $this->assertEmpty($c->get(''));

        $items = $c->get('app');
        $this->assertArrayHasKey('name', $items);

        //Using dot notation
        $content = $this->getDefaultConfigContent();
        $file = $this->createVfsFile('application.php', $this->vfsFilesPath, $content);
        $this->assertEquals('localhost', $c->get('application.db.local.host'));

        //Using default value
        $this->assertEquals(100, $c->get('foo_key_does_not_exists', 100));

        //Using custom env
        $file = $this->createVfsFile('app.php', $this->vfsFilesPath, '<?php return array("name" => "foo");');
        $dir = $this->createVfsDirectory('dev', $this->vfsFilesPath);
        $file = $this->createVfsFile('app.php', $dir, '<?php return array("name" => "baz");');
        $env = 'dev';
        $c = new Config($fl, $env);
        $this->assertEquals('baz', $c->get('app.name'));
    }

    public function testSet(): void
    {
        $path = $this->vfsFilesPath->url();
        $fl = new FileLoader($path);

        $env = '';

        $content = $this->getDefaultConfigContent();
        $file = $this->createVfsFile('application.php', $this->vfsFilesPath, $content);
        $c = new Config($fl, $env);
        $this->assertEquals('localhost', $c->get('application.db.local.host'));

        //Using simple key
        $c->set('debug', true);
        $this->assertTrue($c->get('debug'));

        //Using dot notation
        $c->set('application.db.staging', array('host' => 'test.foo.server'));
        $this->assertEquals('test.foo.server', $c->get('application.db.staging.host'));

        $c->set('application.db.debug', false);
        $this->assertFalse($c->get('application.db.debug'));
    }

    public function testGetItems(): void
    {
        $path = $this->vfsFilesPath->url();
        $file = $this->createVfsFile('app.php', $this->vfsFilesPath, '<?php return array("name" => "foo");');
        $fl = new FileLoader($path);


        $env = '';
        $c = new Config($fl, $env);

        $this->assertEquals('foo', $c->get('app.name'));
        $items = $c->getItems();
        $this->assertIsArray($items);
        $this->assertArrayHasKey('app', $items);
        $this->assertArrayHasKey('name', $items['app']);
    }

    public function testArrayAccessImplementedMethod(): void
    {
        $path = $this->vfsFilesPath->url();
        $file = $this->createVfsFile('app.php', $this->vfsFilesPath, '<?php return array("name" => "foo");');
        $fl = new FileLoader($path);


        $env = '';
        $c = new Config($fl, $env);

        $this->assertEquals('foo', $c->get('app.name'));

        $this->assertTrue(isset($c['app.name']));
        $this->assertArrayHasKey('app', $c);
        $this->assertArrayHasKey('name', $c['app']);
        $this->assertEquals('foo', $c['app.name']);
        unset($c['app.name']);
        $this->assertNull($c['app.name']);

        $c['app.name'] = 'baz';
        $this->assertEquals('baz', $c->get('app.name'));
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
