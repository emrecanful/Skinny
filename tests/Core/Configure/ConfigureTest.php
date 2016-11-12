<?php
namespace SkinnyTest\Core\Configure;

use Skinny\Core\Configure;
use Skinny\Core\Configure\Engine\PhpConfig;
use Skinny\Core\Plugin;
use Skinny\TestSuite\TestCase;

class ConfigureTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->config = Configure::read();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        Configure::write($this->config);
    }

    /**
     * testCheckTokenKeyFail method
     *
     * @expectedException \RuntimeException
     *
     * @return void
     */
    public function testCheckTokenKeyFail()
    {
        Configure::write('Discord.token', 'insert-your-token-here');
        Configure::checkTokenKey();
    }

    /**
     * testCheckTokenKey method
     *
     * @return void
     */
    public function testCheckTokenKey()
    {
        Configure::write('Discord.token', 'valid-token');
        Configure::checkTokenKey();
    }

    /**
     * testReadOrFail method
     *
     * @return void
     */
    public function testReadOrFail()
    {
        $expected = 'ok';
        Configure::write('This.Key.Exists', $expected);
        $result = Configure::readOrFail('This.Key.Exists');
        $this->assertEquals($expected, $result);
    }

    /**
     * testReadOrFail method
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expected configuration key "This.Key.Does.Not.exist" not found
     * @return void
     */
    public function testReadOrFailThrowingException()
    {
        Configure::readOrFail('This.Key.Does.Not.exist');
    }

    /**
     * testRead method
     *
     * @return void
     */
    public function testRead()
    {
        $expected = 'ok';
        Configure::write('level1.level2.level3_1', $expected);
        Configure::write('level1.level2.level3_2', 'something_else');
        $result = Configure::read('level1.level2.level3_1');
        $this->assertEquals($expected, $result);

        $result = Configure::read('level1.level2.level3_2');
        $this->assertEquals('something_else', $result);

        $result = Configure::read('debug');
        $this->assertTrue($result >= 0);

        $result = Configure::read();
        $this->assertTrue(is_array($result));
        $this->assertTrue(isset($result['debug']));
        $this->assertTrue(isset($result['level1']));

        $result = Configure::read('something_I_just_made_up_now');
        $this->assertEquals(null, $result, 'Missing key should return null.');
    }

    /**
     * testWrite method
     *
     * @return void
     */
    public function testWrite()
    {
        $writeResult = Configure::write('SomeName.someKey', 'myvalue');
        $this->assertTrue($writeResult);
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        $writeResult = Configure::write('SomeName.someKey', null);
        $this->assertTrue($writeResult);
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals(null, $result);

        $expected = ['One' => ['Two' => ['Three' => ['Four' => ['Five' => 'cool']]]]];
        $writeResult = Configure::write('Key', $expected);
        $this->assertTrue($writeResult);

        $result = Configure::read('Key');
        $this->assertEquals($expected, $result);

        $result = Configure::read('Key.One');
        $this->assertEquals($expected['One'], $result);

        $result = Configure::read('Key.One.Two');
        $this->assertEquals($expected['One']['Two'], $result);

        $result = Configure::read('Key.One.Two.Three.Four.Five');
        $this->assertEquals('cool', $result);

        Configure::write('one.two.three.four', '4');
        $result = Configure::read('one.two.three.four');
        $this->assertEquals('4', $result);
    }

    /**
     * test setting display_errors with debug.
     *
     * @return void
     */
    public function testDebugSettingDisplayErrors()
    {
        Configure::write('debug', false);
        $result = ini_get('display_errors');
        $this->assertEquals(0, $result);

        Configure::write('debug', true);
        $result = ini_get('display_errors');
        $this->assertEquals(1, $result);
    }

    /**
     * testDelete method
     *
     * @return void
     */
    public function testDelete()
    {
        Configure::write('SomeName.someKey', 'myvalue');
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        Configure::delete('SomeName.someKey');
        $result = Configure::read('SomeName.someKey');
        $this->assertTrue($result === null);

        Configure::write('SomeName', ['someKey' => 'myvalue', 'otherKey' => 'otherValue']);

        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        $result = Configure::read('SomeName.otherKey');
        $this->assertEquals('otherValue', $result);

        Configure::delete('SomeName');

        $result = Configure::read('SomeName.someKey');
        $this->assertTrue($result === null);

        $result = Configure::read('SomeName.otherKey');
        $this->assertTrue($result === null);
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        Configure::write('ConfigureTestCase', 'value');
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertFalse(Configure::check('NotExistingConfigureTestCase'));
    }

    /**
     * testCheckingSavedEmpty method
     *
     * @return void
     */
    public function testCheckingSavedEmpty()
    {
        $this->assertTrue(Configure::write('ConfigureTestCase', 0));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', '0'));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', false));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', null));
        $this->assertFalse(Configure::check('ConfigureTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     *
     * @return void
     */
    public function testCheckKeyWithSpaces()
    {
        $this->assertTrue(Configure::write('Configure Test', "test"));
        $this->assertTrue(Configure::check('Configure Test'));
        Configure::delete('Configure Test');

        $this->assertTrue(Configure::write('Configure Test.Test Case', "test"));
        $this->assertTrue(Configure::check('Configure Test.Test Case'));
    }

    /**
     * testCheckEmpty
     *
     * @return void
     */
    public function testCheckEmpty()
    {
        $this->assertFalse(Configure::check(''));
        $this->assertFalse(Configure::check(null));
    }

    /**
     * testLoad method
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testLoadExceptionOnNonExistantFile()
    {
        Configure::config('test', new PhpConfig());
        Configure::load('non_existing_configuration_file', 'test');
    }

    /**
     * test load method for default config creation
     *
     * @return void
     */
    public function testLoadDefaultConfig()
    {
        try {
            Configure::load('non_existing_configuration_file');
        } catch (\Exception $e) {
            $result = Configure::configured('default');
            $this->assertTrue($result);
        }
    }

    /**
     * test load with merging
     *
     * @return void
     */
    public function testLoadWithMerge()
    {
        Configure::config('test', new PhpConfig(CONFIG));

        $result = Configure::load('var_test', 'test');
        $this->assertTrue($result);

        $this->assertEquals('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', true);
        $this->assertTrue($result);

        $this->assertEquals('value2', Configure::read('Read'));
        $this->assertEquals('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertEquals('buried', Configure::read('Deep.Deeper.Deepest'));
        $this->assertEquals('Overwrite', Configure::read('TestAcl.classname'));
        $this->assertEquals('one', Configure::read('TestAcl.custom'));
    }

    /**
     * test loading with overwrite
     *
     * @return void
     */
    public function testLoadNoMerge()
    {
        Configure::config('test', new PhpConfig(CONFIG));

        $result = Configure::load('var_test', 'test');
        $this->assertTrue($result);

        $this->assertEquals('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', false);
        $this->assertTrue($result);

        $this->assertEquals('value2', Configure::read('Read'));
        $this->assertEquals('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertNull(Configure::read('Deep.Deeper.Deepest'));
    }

    /**
     * Test load() replacing existing data
     *
     * @return void
     */
    public function testLoadWithExistingData()
    {
        Configure::config('test', new PhpConfig(CONFIG));
        Configure::write('my_key', 'value');

        Configure::load('var_test', 'test');
        $this->assertEquals('value', Configure::read('my_key'), 'Should not overwrite existing data.');
        $this->assertEquals('value', Configure::read('Read'), 'Should load new data.');
    }

    /**
     * Test load() merging on top of existing data
     *
     * @return void
     */
    public function testLoadMergeWithExistingData()
    {
        Configure::config('test', new PhpConfig());
        Configure::write('my_key', 'value');
        Configure::write('Read', 'old');
        Configure::write('Deep.old', 'old');
        Configure::write('TestAcl.classname', 'old');

        Configure::load('var_test', 'test', true);
        $this->assertEquals('value', Configure::read('Read'), 'Should load new data.');
        $this->assertEquals('buried', Configure::read('Deep.Deeper.Deepest'), 'Should load new data');
        $this->assertEquals('old', Configure::read('Deep.old'), 'Should not destroy old data.');
        $this->assertEquals('value', Configure::read('my_key'), 'Should not destroy data.');
        $this->assertEquals('Original', Configure::read('TestAcl.classname'), 'No arrays');
    }

    /**
     * testVersion method
     *
     * @return void
     */
    public function testVersion()
    {
        $result = Configure::version();
        $this->assertTrue(version_compare($result, '0.0.4', '>='));
    }

    /**
     * test adding new engines.
     *
     * @return void
     */
    public function testEngineSetup()
    {
        $engine = new PhpConfig();
        Configure::config('test', $engine);
        $configured = Configure::configured();

        $this->assertTrue(in_array('test', $configured));

        $this->assertTrue(Configure::configured('test'));
        $this->assertFalse(Configure::configured('fake_garbage'));

        $this->assertTrue(Configure::drop('test'));
        $this->assertFalse(Configure::drop('test'), 'dropping things that do not exist should return false.');
    }

    /**
     * Test that clear wipes all values.
     *
     * @return void
     */
    public function testClear()
    {
        Configure::write('test', 'value');
        $this->assertTrue(Configure::clear());
        $this->assertNull(Configure::read('debug'));
        $this->assertNull(Configure::read('test'));
    }

    /**
     * @expectedException \Skinny\Core\Exception\Exception
     * @return void
     */
    public function testDumpNoAdapter()
    {
        Configure::dump(TMP . 'test.php', 'does_not_exist');
    }

    /**
     * test dump integrated with the PhpConfig.
     *
     * @return void
     */
    public function testDump()
    {
        Configure::config('test_Engine', new PhpConfig(TMP));

        $result = Configure::dump('config_test', 'test_Engine');
        $this->assertTrue($result > 0);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertContains('<?php', $result);
        $this->assertContains('return ', $result);
        if (file_exists(TMP . 'config_test.php')) {
            unlink(TMP . 'config_test.php');
        }
    }

    /**
     * Test dumping only some of the data.
     *
     * @return void
     */
    public function testDumpPartial()
    {
        Configure::config('test_Engine', new PhpConfig(TMP));
        Configure::write('Error', ['test' => 'value']);

        $result = Configure::dump('config_test', 'test_Engine', ['Error']);
        $this->assertTrue($result > 0);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertContains('<?php', $result);
        $this->assertContains('return ', $result);
        $this->assertContains('Error', $result);
        $this->assertNotContains('debug', $result);

        if (file_exists(TMP . 'config_test.php')) {
            unlink(TMP . 'config_test.php');
        }
    }

    /**
     * Test the consume method.
     *
     * @return void
     */
    public function testConsume()
    {
        $this->assertNull(Configure::consume('DoesNotExist'), 'Should be null on empty value');
        Configure::write('Test', ['key' => 'value', 'key2' => 'value2']);

        $result = Configure::consume('Test.key');
        $this->assertEquals('value', $result);

        $result = Configure::read('Test.key2');
        $this->assertEquals('value2', $result, 'Other values should remain.');

        $result = Configure::consume('Test');
        $expected = ['key2' => 'value2'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testConsumeEmpty
     *
     * @return void
     */
    public function testConsumeEmpty()
    {
        Configure::write('Test', ['key' => 'value', 'key2' => 'value2']);

        $result = Configure::consume('');
        $this->assertNull($result);

        $result = Configure::consume(null);
        $this->assertNull($result);
    }
}
