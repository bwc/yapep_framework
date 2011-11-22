<?php

namespace YapepBase\Test\Database;
use YapepBase\Exception\DatabaseException;

use YapepBase\Database\SqliteConnection;

require_once dirname(__FILE__) . '/../../bootstrap.php';

/**
 * SqliteConnection test case.
 */
class SqliteConnectionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \YapepBase\Database\SqliteConnection
     */
    private $subject;

    /**
     * Prepares the environment before running a test.
     *
     * We are using '_' as the param prefix.
     */
    protected function setUp() {
        parent::setUp();
        $this->subject = new SqliteConnection(array('path' => ':memory:'), 'test', '_');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() {
        if (!empty($this->subject)) {
            $this->subject->query('DROP TABLE IF EXISTS test');
        }
        $this->subject = null;

        parent::tearDown();
    }

    /**
     * Creates the test table
     */
    protected function createTestTable() {
        $sql = '
        	CREATE TABLE test (
        		id INTEGER PRIMARY KEY,
        		test TEXT
    		)
    	';

        $this->subject->query($sql);
    }

    /**
     * Tests running queries without using bound params
     */
    public function testQuery() {
        $this->createTestTable();

        $sql = '
        	INSERT INTO
        		test
    			(
    				id,
    				test
    			)
			VALUES
				(
					1,
					\'test\'
				)
    	';

        $this->subject->query($sql);

        $sql = '
    		SELECT
    			test
			FROM
				test
			WHERE
				id = 1
		';

        $result = $this->subject->query($sql);

        $this->assertInstanceOf('\PDOStatement', $result, 'Result is of wrong type');
        $this->assertEquals('test', $result->fetchColumn(0), 'Invalid result');
    }

    /**
     * Tests running queries using bound params.
     */
    public function testBoundQuery() {
        $this->createTestTable();

        $sql = '
        	INSERT INTO
        		test
    			(
    				id,
    				test
    			)
			VALUES
				(
					:_id,
					:_test
				)
    	';

        $params = array(
            'id' => 1,
            'test' => 'test',
        );

        $this->subject->query($sql, $params);

        $sql = '
    		SELECT
    			test
			FROM
				test
			WHERE
				id = :_id
		';

        $params = array(
            'id' => 1,
        );

        $result = $this->subject->query($sql, $params);

        $this->assertInstanceOf('\PDOStatement', $result, 'Result is of wrong type');
        $this->assertEquals('test', $result->fetchColumn(0), 'Invalid result');
    }

    /**
     * Tests a successful transaction
     */
    public function testSuccessfulTransaction() {
        $this->createTestTable();

        $sql = '
        	INSERT INTO
        		test
    			(
    				id,
    				test
    			)
			VALUES
				(
					:_id,
					:_test
				)
        ';

        $this->subject->beginTransaction();

        $this->subject->query($sql, array('id' => 1, 'test' => 'test'));
        $this->subject->query($sql, array('id' => 2, 'test' => 'test2'));

        $this->assertTrue($this->subject->completeTransaction(), 'Transaction failed');

        $sql = '
        	SELECT
        		id, test
    		FROM
    			test
			ORDER BY
				id ASC
        ';

        $result = $this->subject->query($sql);

        $row = $result->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['id'], 'Invalid first ID');
        $this->assertEquals('test', $row['test'], 'Invalid first ID');

        $row = $result->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(2, $row['id'], 'Invalid second ID');
        $this->assertEquals('test2', $row['test'], 'Invalid second ID');

        $this->assertFalse($result->fetch(\PDO::FETCH_ASSOC));
    }

    /**
     * Tests a manualy failed transaction
     */
    public function testFailedTransaction() {
        $this->createTestTable();

        $sql = '
        	INSERT INTO
        		test
    			(
    				id,
    				test
    			)
			VALUES
				(
					:_id,
					:_test
				)
        ';

        $this->subject->beginTransaction();

        $this->subject->query($sql, array('id' => 1, 'test' => 'test'));
        $this->subject->failTransaction();
        $this->subject->query($sql, array('id' => 2, 'test' => 'test2'));

        $this->assertFalse($this->subject->completeTransaction(), 'Transaction failed');

        $sql = '
        	SELECT
        		id, test
    		FROM
    			test
			ORDER BY
				id ASC
        ';

        $result = $this->subject->query($sql);

        $this->assertFalse($result->fetch(\PDO::FETCH_ASSOC));
    }

    /**
     * Tests a transaction with an error
     */
    public function testErrorTransaction() {
        $this->createTestTable();

        $sql = '
        	INSERT INTO
        		test
    			(
    				id,
    				test
    			)
			VALUES
				(
					:_id,
					:_test
				)
        ';

        $this->subject->beginTransaction();

        $this->subject->query($sql, array('id' => 1, 'test' => 'test'));
        $sql = '
        	INSERT INTO
        		test
    			(
    				id,
    				test2
    			)
			VALUES
				(
					:_id,
					:_test
				)
        ';

        try {
            $this->subject->query($sql, array('id' => 2, 'test' => 'test2'));
        } catch (DatabaseException $e) {
        }

        $this->assertFalse($this->subject->completeTransaction(), 'Transaction failed');

        $sql = '
        	SELECT
        		id, test
    		FROM
    			test
			ORDER BY
				id ASC
        ';

        $result = $this->subject->query($sql);

        $this->assertFalse($result->fetch(\PDO::FETCH_ASSOC));
    }

    /**
     * Tests if a query error produces an exception
     */
    public function testQueryError() {
        $this->createTestTable();

        $this->setExpectedException('\YapepBase\Exception\DatabaseException');

        $sql = '
        	SLECT
        		*
    		FROM
    			test
        ';

        $this->subject->query($sql);
    }

    /**
     * Tests the quoting
     */
    public function testQuote() {
        $this->assertEquals('\'test\'', $this->subject->quote('test'), 'Error quoting simple string');
        $this->assertEquals('\'o\'\'neill\'', $this->subject->quote('o\'neill'),
        	'Error quoting string with quote mark');
    }
}
