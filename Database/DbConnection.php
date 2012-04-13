<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package      YapepBase
 * @subpackage   Database
 * @author       Zsolt Szeberenyi <szeber@yapep.org>
 * @copyright    2011 The YAPEP Project All rights reserved.
 * @license      http://www.opensource.org/licenses/bsd-license.php BSD License
 */


namespace YapepBase\Database;
use YapepBase\Exception\DatabaseException;
use \PDO;
use \PDOException;
use \PDOStatement;
use YapepBase\Application;
use YapepBase\Debugger\IDebugger;

/**
 * Base class for database connections.
 *
 * @package    YapepBase
 * @subpackage Database
 */
abstract class DbConnection {

	/**
	 * Stores the connection instance
	 *
	 * @var \PDO
	 */
	protected $connection;

	/**
	 * Stores the connedtion name
	 *
	 * @var string
	 */
	protected $connectionName;

	/**
	 * Stores the number of open transactions.
	 *
	 * @var int
	 */
	protected $transactionCount = 0;

	/**
	 * Stores whether the current transaction has failed.
	 *
	 * @var bool
	 */
	protected $transactionFailed = false;

	/**
	 * Stores the parameter prefix.
	 *
	 * @var string
	 */
	protected $paramPrefix = '';

	/**
	 * Constructor
	 *
	 * @param array  $configuration    The configuration for the parameters.
	 * @param string $connectionName   The name of the connection.
	 * @param string $paramPrefix      The prefix for the bound parameters.
	 *
	 * @throws DatabaseException   On connection errors.
	 */
	public function __construct(array $configuration, $connectionName, $paramPrefix = '') {
		$this->connectionName = $connectionName;
		$this->paramPrefix = $paramPrefix;
		try {
			$this->connect($configuration);
		} catch (PDOException $exception) {
			$message = null;
			$code = 0;
			$this->parsePdoException($exception, $message, $code);
			throw new DatabaseException($message, $code, $exception);
		}
		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Opens the connection
	 *
	 * @param array $configuration   The configuration for the connection
	 */
	abstract protected function connect(array $configuration);

	/**
	 * Returns the prefix which is for prefixing parameters in the query.
	 *
	 * @return string
	 */
	public function getParamPrefix() {
		return $this->paramPrefix;
	}

	/**
	 * Runs a query and returns the result object.
	 *
	 * @param string $query    The query to execute.
	 * @param array  $params   The parameters for the query.
	 *
	 * @return \YapepBase\Database\DbResult   The result of the query.
	 *
	 * @throws \YapepBase\Exception\DatabaseException   On execution errors.
	 */
	public function query($query, array $params = array()) {
		try {
			$debugger = Application::getInstance()->getDiContainer()->getDebugger();

			// If we have a debugger, we have to log the query
			if ($debugger !== false) {
				$queryId = $debugger->logQuery(IDebugger::QUERY_TYPE_DB, $query, $params);
				$startTime = microtime(true);
			}

			$statement = $this->connection->prepare($query);
			foreach ($params as $key=>$value) {
				$statement->bindValue(':' . $this->paramPrefix . $key, $value, $this->getParamType($value));
			}
			$statement->execute();

			// If we have a debugger, we have to log the execution time
			if ($debugger !== false) {
				$debugger->logQueryExecutionTime(IDebugger::QUERY_TYPE_DB, $queryId, microtime(true) - $startTime);
			}

			return new DbResult($statement);
		}
		catch (PDOException $exception) {
			$this->transactionFailed = true;
			$message = null;
			$code = 0;
			$this->parsePdoException($exception, $message, $code);
			throw new DatabaseException($message, $code, $exception);
		}
	}

	/**
	 * Runs a paginated query, and returns the result.
	 *
	 * You can't use LIMIT or OFFSET clause in your query, becouse then it will be duplicated in the method.
	 *
	 * @param string   $query          Th query to execute.
	 * @param array    $params         The parameters for the query.
	 * @param int      $pageNumber     The number of the requested page.
	 * @param int      $itemsPerPage   How many items should be listed in the page.
	 * @param bool|int $itemCount      If it is set to FALSE then it wont be populated. (outgoing parameter)
	 *
	 * @return \YapepBase\Database\DbResult   The result of the query.
	 *
	 * @throws \YapepBase\Exception\DatabaseException   On execution errors.
	 */
	public function queryPaged($query, array $params, $pageNumber, $itemsPerPage, &$itemCount = false) {
		if ($itemCount !== false) {
			$query = preg_replace('/SELECT/i', '$0 SQL_CALC_FOUND_ROWS', $query, 1);
		}

		$query .= '
			LIMIT
				' . (int)$itemsPerPage . '
			OFFSET
				' . (int)(($pageNumber - 1) * $itemsPerPage);

		$result = $this->query($query, $params);

		if ($itemCount !== false) {
			$itemCount = (int)$this->query('SELECT FOUND_ROWS()')->fetchColumn();
		}
		return $result;
	}

	/**
	 * Returns the PDO data type for the specified value.
	 *
	 * Also casts the specified value if it's necessary.
	 *
	 * @param mixed $value   The value to examine.
	 *
	 * @return int   The PDO data type.
	 */
	protected function getParamType(&$value) {
		if (is_integer($value) || is_float($value)) {
			return PDO::PARAM_INT;
		} elseif (is_null($value)) {
			return PDO::PARAM_NULL;
		} elseif (is_bool($value)) {
			return PDO::PARAM_BOOL;
		} else {
			$value = (string)$value;
			return PDO::PARAM_STR;
		}
	}

	/**
	 * Begins a transaction.
	 *
	 * If there already is an open transaction, it just increments the transaction counter.
	 *
	 * @return int   The number of open transactions.
	 */
	public function beginTransaction() {
		if (0 == $this->transactionCount) {
			$this->connection->beginTransaction();
			$this->transactionFailed = false;
		}
		return ++$this->transactionCount;
	}

	/**
	 * Completes (commits or rolls back) a transaction.
	 *
	 * If there is more then 1 open transaction, it only decrements the transaction count by one, and returns the
	 * current transaction status. It is possible for these transactions to fail and be eventually rolled back,
	 * if any further statements fail.
	 *
	 * @return bool   TRUE if the transaction was committed, FALSE if it was rolled back.
	 */
	public function completeTransaction() {
		$this->transactionCount--;
		if (0 == $this->transactionCount) {
			if ($this->transactionFailed) {
				$this->connection->rollBack();
				return false;
			} else {
				return $this->connection->commit();
			}
		}
		return $this->transactionFailed;
	}

	/**
	 * Sets a transaction's status to failed.
	 */
	public function failTransaction() {
		$this->transactionFailed = true;
	}

	/**
	 * Returns the quoted version of the specified value.
	 *
	 * Do not use this function to quote data in a query, use the bound parameters instead. {@see self::query()}
	 *
	 * @param mixed $value   The value to quote.
	 *
	 * @return string   The quoted value.
	 */
	public function quote($value) {
		return $this->connection->quote($value, $this->getParamType($value));
	}

	/**
	 * Returns the last insert id for the connection.
	 *
	 * @param string $name   Name of the sequence object from which the ID should be returned.
	 *
	 * @return string
	 *
	 * @throws DatabaseException   If the driver does not support the capability.
	 *
	 * @see PDO::lastInsertId()
	 */
	public function lastInsertId($name = null) {
		try {
			return $this->connection->lastInsertId($name);
		} catch (PDOException $exception) {
			$message = null;
			$code = 0;
			$this->parsePdoException($exception, $message, $code);
			throw new DatabaseException($message, $code, $exception);
		}
	}

	/**
	 * Parses the message and code from the specified PDOException.
	 *
	 * @param \PDOException $exception   The exception to parse
	 * @param string        $message     The parsed message (outgoing param).
	 * @param int           $code        The parsed code (outgoing param).
	 *
	 * @return \YapepBase\Exception\DatabaseException
	 */
	protected function parsePdoException(PDOException $exception, &$message, &$code) {
		$message = $exception->getMessage();
		$code = (int)$exception->getCode();
		$matches = array();

		// Parse the ANSI error code from the message.
		// Regex is based on the one from samuelelliot+php dot net at gmail dot com.
		if (strstr($message, 'SQLSTATE[') && preg_match('/SQLSTATE\[(\d+)\]: (.+)$/', $message, $matches)) {
			$message = $matches[2];
			$code = $matches[1];
		}
	}

	/**
	 * Returns the provided string, with all wildcard characters escaped.
	 *
	 * This method should be used to escape the string part in the "LIKE 'string' ESCAPE 'escapeCharacter'" statement.
	 *
	 * @param string $string            The string to escape.
	 * @param string $escapeCharacter   The character to use as the escape character. Defaults to backslash.
	 *
	 * @return string
	 */
	 public function escapeWildcards($string, $escapeCharacter = '\\') {
		 return preg_replace('/([_%' . preg_quote($escapeCharacter, '/') . '])/',
			 addcslashes($escapeCharacter, '$\\') . '$1', $string);
	 }
}