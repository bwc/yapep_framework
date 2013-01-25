<?php

namespace YapepBase\Storage;

use YapepBase\Config;
use YapepBase\Storage\FileStorage;
use YapepBase\Exception\ConfigException;
use YapepBase\Exception\StorageException;
use YapepBase\Exception\ParameterException;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Test class for FileStorage.
 * Generated by PHPUnit on 2011-12-23 at 13:41:33.
 */
class FileStorageTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
		Config::getInstance()->set(array(
			'resource.storage.test1.path'            => vfsStream::url('test') . '/test1',

			'resource.storage.test2.path'            => vfsStream::url('test') . '/test2',
			'resource.storage.test2.storePlainText'  => true,
			'resource.storage.test2.fileMode'        => 0666,
			'resource.storage.test2.readOnly'        => false,

			'resource.storage.test3.path'            => vfsStream::url('test') . '/test3',
			'resource.storage.test3.filePrefix'      => 'test.',
			'resource.storage.test3.fileSuffix'      => '.test',

			'resource.storage.test4.path'            => vfsStream::url('test') . '/test4',
			'resource.storage.test4.filePrefix'      => 'test.',
			'resource.storage.test4.hashKey'         => true,

			'resource.storage.test5.none'            => '',

			'resource.storage.test6.path'            => vfsStream::url('test') . '/test6',
			'resource.storage.test6.storePlainText'  => true,
			'resource.storage.test6.readOnly'        => true,

		));
	}

	protected function tearDown() {
		Config::getInstance()->clear();
		parent::tearDown();
	}

	public function testSerialized() {
		$storage = new FileStorage('test1');
		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('test1/test'), 'Test data exists after directory creation');

		$this->assertFalse($storage->get('test'), 'Not set value does not return false');

		$storage->set('test', 'testValue', 100);
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('test1/test'), 'Test data file does not exist after setting');
		$this->assertSame('testValue', $storage->get('test'), 'Stored value does not match');

		$content = unserialize(vfsStreamWrapper::getRoot()->getChild('test1/test')->getContent());
		$this->assertEquals(100, $content['expiresAt'] - $content['createdAt'], 'Ttl does not match');

		$storage->delete('test');

		$this->assertFalse($storage->get('test'), 'Deletion failed');
		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('test1/test'), 'Test data file exists after deletion');

		$storage->set('test2', 'testValue', 0);
		$this->assertSame('testValue', $storage->get('test2'), 'Stored value does not match with 0 TTL');

		$storage->delete('test2');

	}

	public function testPlain() {
		$storage = new FileStorage('test2');
		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('test2/test'), 'Test data exists after directory creation');

		$this->assertFalse($storage->get('test'), 'Not set value does not return false');

		$storage->set('test', 'testValue');
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('test2/test'), 'Test data file does not exist after setting');
		$this->assertSame('testValue', $storage->get('test'), 'Stored value does not match');

		$this->assertEquals('testValue', vfsStreamWrapper::getRoot()->getChild('test2/test')->getContent(),
			'Stored data in file does not match');

		$storage->delete('test');

		$this->assertFalse($storage->get('test'), 'Deletion failed');
		$this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('test2/test'), 'Test data file exists after deletion');
	}

	public function testExpired() {
		$now = time();

		$storageFile = new vfsStreamFile('test');
		$storageFile->setContent(serialize(array(
			'createdAt' => $now - 100,
			'expiresAt' => $now - 10,
			'data'      => 'testValue',
			'key'       => 'test',
		)));

		$storageDir = new vfsStreamDirectory('test1');
		$storageDir->addChild($storageFile);

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild($storageDir);

		vfsStreamWrapper::setRoot($rootDir);

		$storage = new FileStorage('test1');

		$this->assertTrue($storageDir->hasChild('test'), 'Expired test data does not exist before getting');
		$this->assertFalse($storage->get('test'), 'Getting expired data does not return FALSE');
		$this->assertFalse($storageDir->hasChild('test'), 'Expired data is not cleaned up after getting');
	}

	public function testPrefixedStorage() {
		$storage = new FileStorage('test3');
		$storage->set('test', 'testValue');
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('test3/test.test.test'), 'Prefixed storage fails');
	}

	public function testHashedStorage() {
		$storage = new FileStorage('test4');
		$storage->set('test', 'testValue');
		$this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('test4/' . md5('test.test')), 'Hashed storage fails');
	}

	public function testStorageSettings() {
		$storage = new FileStorage('test1');
		$this->assertTrue($storage->isPersistent(), 'File storage is always persistent.');
		$this->assertTrue($storage->isTtlSupported(), 'File storage should support TTL if not storing in plaintext');
		$storage = new FileStorage('test2');
		$this->assertFalse($storage->isTtlSupported(), 'File storage should not support TTL if storing in plaintext');
	}

	public function testErrorHandling() {
		try {
			new FileStorage('nonexistent');
			$this->fail('No ConfigException thrown for nonexistent config option');
		} catch (ConfigException $exception) {
		}

		try {
			new FileStorage('test5');
			$this->fail('No ConfigException thrown for config without a path');
		} catch (ConfigException $exception) {
		}

		try {
			$storage = new FileStorage('test2');
			$storage->set('test', 'testValue', 100);
			$this->fail('No ParameterException thrown when using TTL with plain text storage');
		} catch (ParameterException $exception) {
		}

		try {
			$storage = new FileStorage('test1');
			$storage->set('test/test', 'testValue', 100);
			$this->fail('No StorageException thrown when using invalid characters in the key');
		} catch (StorageException $exception) {
		}
	}

	public function testPermissionErrorHandling() {
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('test', 0000));
		try {
			new FileStorage('test1');
			$this->fail('No StorageException thrown for non-writable root dir, with nonexisting storage dir');
		} catch (StorageException $exception) {
		}

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild(new vfsStreamDirectory('test1', 0000));
		vfsStreamWrapper::setRoot($rootDir);
		try {
			new FileStorage('test1');
			$this->fail('No StorageException thrown for non-writable storage dir');
		} catch (StorageException $exception) {
		}

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild(new vfsStreamFile('test1'));
		vfsStreamWrapper::setRoot($rootDir);
		try {
			new FileStorage('test1');
			$this->fail('No StorageException thrown for non-directory storage dir');
		} catch (StorageException $exception) {
		}
	}

	public function testDeserializationErrorHandling() {
		$storageFile = new vfsStreamFile('test');
		$storageFile->setContent('test');

		$storageDir = new vfsStreamDirectory('test1');
		$storageDir->addChild($storageFile);

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild($storageDir);

		vfsStreamWrapper::setRoot($rootDir);

		$storage = new FileStorage('test1');

		try {
			$storage->get('test');
			$this->fail('No StorageException thrown for invalid serialized data');
		} catch(StorageException $exception) {
		}
	}

	public function testSavePermissionErrorHandling() {
		$storageFile = new vfsStreamFile('test2');
		$storageFile->setContent('test');

		$storageDir = new vfsStreamDirectory('test2');
		$storageDir->addChild($storageFile);

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild($storageDir);

		vfsStreamWrapper::setRoot($rootDir);

		$storage = new FileStorage('test2');

		$storageDir->chmod(0000);

		try {
			$storage->set('test', 'testValue');
			$this->fail('No StorageException thrown for unsuccessful file save');
		} catch (StorageException $exception) {
		}

		try {
			$storage->delete('test2');
			$this->fail('No StorageException thrown for unsuccessful file deletion');
		} catch (StorageException $exception) {
		}
	}

	public function testReadPermissionErrorHandling() {
		$storageFile = new vfsStreamFile('test', 000);
		$storageFile->setContent('test');

		$storageDir = new vfsStreamDirectory('test2');
		$storageDir->addChild($storageFile);

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild($storageDir);

		vfsStreamWrapper::setRoot($rootDir);

		$storage = new FileStorage('test2');

		try {
			$storage->get('test');
			$this->fail('No StorageException thrown for unreadable file');
		} catch (StorageException $exception) {
		}
	}

	/**
	 * Tests the read only setting for the storage
	 *
	 * @return void
	 */
	public function testReadOnly() {
		$storageFile = new vfsStreamFile('test');
		$storageFile->setContent('test');

		$storageDir = new vfsStreamDirectory('test1');
		$storageDir->addChild($storageFile);

		$storageDir = new vfsStreamDirectory('test2');
		$storageDir->addChild($storageFile);

		$storageDir = new vfsStreamDirectory('test6');
		$storageDir->addChild($storageFile);

		$rootDir = new vfsStreamDirectory('test');
		$rootDir->addChild($storageDir);

		vfsStreamWrapper::setRoot($rootDir);

		$defaultStorage = new FileStorage('test1');
		$readWriteStorage = new FileStorage('test2');
		$readOnlyStorage = new FileStorage('test6');

		$this->assertFalse($defaultStorage->isReadOnly(),
			'A storage should report it is not read only if no read only setting is defined');
		$this->assertFalse($readWriteStorage->isReadOnly(), 'The read-write storage should report it is not read only');
		$this->assertTrue($readOnlyStorage->isReadOnly(), 'The read only storage should report it is read only');

		$data = $readOnlyStorage->get('test');
		$this->assertNotEmpty($data, 'The retrieved data should not be empty');
		try {
			$readOnlyStorage->set('test', 'test2');
			$this->fail('No StorageException thrown for trying to write to a read only storage');
		} catch (StorageException $exception) {
			$this->assertContains('read only storage', $exception->getMessage());
		}
		$this->assertSame($data, $readOnlyStorage->get('test'), 'The data should not be changed in the storage');
	}
}
