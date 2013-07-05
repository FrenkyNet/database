<?php
/**
 * FuelPHP\Database is an easy flexible PHP 5.3+ Database Abstraction Layer
 *
 * @package    FuelPHP\Database
 * @version    1.0
 * @author     Frank de Jonge
 * @license    MIT License
 * @copyright  2011 - 2012 FuelPHP Development Team
 */

namespace FuelPHP\Database\Collector;

use FuelPHP\Database\DB;
use FuelPHP\Database\Exception;

class Select extends Where
{
	/**
	 * @var  int  $type  query type
	 */
	public $type = DB::SELECT;

	/**
	 * @var  array  $having  having conditions
	 */
	public $having = array();

	/**
	 * @var  array  $groupBy  GROUP BY clause
	 */
	public $groupBy = array();

	/**
	 * @var  object  $lastJoin  last join object
	 */
	protected $_lastJoin = null;

	/**
	 * @var  array  $joins  query joins
	 */
	public $joins = array();

	/**
	 * @var  array  $columns  columns to use
	 */
	public $columns = array();

	/**
	 * @var  boolean  $columns  wether to use distinct
	 */
	public $distinct = false;

	/**
	 * @var  mixed  $table  table
	 */
	public $table = array();

	/**
	 * Constructor
	 *
	 * @param  array  $columns  an array of columns to select
	 */
	public function __construct()
	{
	}

	/**
	 * Set the table to select from
	 *
	 * @param   string  $table  table to select from
	 * @param   ...
	 * @return  $this
	 */
	public function from($table)
	{
		$tables = func_get_args();

		$this->table = array_merge($this->table, $tables);

		return $this;
	}

	/**
	 * Sets/adds columns to select
	 *
	 * @param   mixed   column name or array($column, $alias) or object
	 * @param   ...
	 * @return  $this
	 */
	public function select($column)
	{
		$this->columns = array_merge($this->columns, func_get_args());

		return $this;
	}

	/**
	 * Choose the columns to select from, using an array.
	 *
	 * @param   array  $columns  list of column names or aliases
	 * @return  object current instance
	 */
	public function selectArray(array $columns)
	{
		$this->columns = array_merge($this->columns, $columns);

		return $this;
	}

	/**
	 * Enables or disables selecting only unique (distinct) values
	 *
	 * @param   bool    $distinct  enable or disable distinct values
	 * @return  $this
	 */
	public function distinct($distinct = true)
	{
		$this->distinct = $distinct;

		return $this;
	}

	/**
	 * Creates a "GROUP BY ..." filter.
	 *
	 * @param   mixed   column name or array($column, $alias)
	 * @param   ...
	 * @return  object  $this
	 */
	public function groupBy($columns)
	{
		$columns = func_get_args();
		$this->groupBy = array_unique(array_merge($this->groupBy, $columns));

		return $this;
	}

	/**
	 * Adds a new join.
	 *
	 * @param   string  $table  string column name or alias array
	 * @param   string  $type   join type
	 * @return  $this
	 */
	public function join($table, $type = null)
	{
		$this->join[] = $this->_lastJoin = new Join($table, $type);

		return $this;
	}

	/**
	 * Sets an "AND ON" clause on the last join.
	 *
	 * @param   string  $column1  column name
	 * @param   string  $op       logic operator
	 * @param   string  $column2  column name
	 * @return  $this
	 */
	public function on($column1, $op, $column2 = null)
	{
		if ( ! $this->_lastJoin)
		{
			throw new Exception('You must first join a table before setting an "ON" clause.');
		}

		call_user_func_array(array($this->_lastJoin, 'on'), func_get_args());

		return $this;
	}

	/**
	 * Sets an "AND ON" clause on the last join.
	 *
	 * @param   string  $column1  column name
	 * @param   string  $op       logic operator
	 * @param   string  $column2  column name
	 * @return  $this
	 */
	public function andOn($column1, $op, $column2 = null)
	{
		if ( ! $this->_lastJoin)
		{
			throw new Exception('You must first join a table before setting an "AND ON" clause.');
		}

		call_user_func_array(array($this->_lastJoin, 'andOn'), func_get_args());

		return $this;
	}

	/**
	 * Sets an "OR ON" clause on the last join.
	 *
	 * @param   string  $column1  column name
	 * @param   string  $op       logic operator
	 * @param   string  $column2  column name
	 * @return  $this
	 */
	public function orOn($column1, $op, $column2 = null)
	{
		if( ! $this->_lastJoin)
		{
			throw new Exception('You must first join a table before setting an "OR ON" clause.');
		}

		call_user_func_array(array($this->_lastJoin, 'orOn'), func_get_args());

		return $this;
	}

	/**
	 * Alias for andHaving.
 	 *
	 * @param   mixed   $column  array of 'and having' statements or column name
	 * @param   string  $op      having logic operator
	 * @param   mixed   $value   having value
	 * @return  $this
	 */
	public function having($column, $op = null, $value = null)
	{
		return call_user_func_array(array($this, 'andHaving'), func_get_args());
	}

	/**
	 * Alias for andNotHaving.
 	 *
	 * @param   mixed   $column  array of 'and not having' statements or column name
	 * @param   string  $op      having logic operator
	 * @param   mixed   $value   having value
	 * @return  $this
	 */
	public function notHaving($column, $op = null, $value = null)
	{
		return call_user_func_array(array($this, 'andNotHaving'), func_get_args());
	}

	/**
	 * Adds an 'and having' statement to the query.
	 *
	 * @param   mixed   $column  array of 'and having' statements or column name
	 * @param   string  $op      having logic operator
	 * @param   mixed   $value   having value
	 * @return  $this
	 */
	public function andHaving($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->andHavingOpen();
			$column($this);
			$this->andHavingClose();
			return $this;
		}

		if (func_num_args() == 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('having', 'and', $column, $op, $value);
	}

	/**
	 * Adds an 'and not having' statement to the query.
	 *
	 * @param   mixed   $column  array of 'and not having' statements or column name
	 * @param   string  $op      having logic operator
	 * @param   mixed   $value   having value
	 * @return  $this
	 */
	public function andNotHaving($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->andNotHavingOpen();
			$column($this);
			$this->andNotHavingClose();
			return $this;
		}

		if (func_num_args() == 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('having', 'and', $column, $op, $value, true);
	}

	/**
	 * Adds an 'or having' statement to the query.
	 *
	 * @param   mixed   $column  array of 'or having' statements or column name
	 * @param   string  $op      having logic operator
	 * @param   mixed   $value   having value
	 * @return  $this
	 */
	public function orHaving($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->orHavingOpen();
			$column($this);
			$this->havingClose();
			return $this;
		}

		if (func_num_args() == 2)
		{
			$value = $op;
			$op = is_array($value) ? 'in' : '=';
		}

		return $this->addCondition('having', 'or', $column, $op, $value);
	}

	/**
	 * Adds an 'or having' statement to the query.
	 *
	 * @param   mixed   $column  array of 'or having' statements or column name
	 * @param   string  $op      having logic operator
	 * @param   mixed   $value   having value
	 * @return  $this
	 */
	public function orNotHaving($column, $op = null, $value = null)
	{
		if($column instanceof \Closure)
		{
			$this->orNotHavingOpen();
			$column($this);
			$this->havingClose();
			return $this;
		}

		if (func_num_args() == 2)
		{
			$value = $op;
			$op = '=';
		}

		return $this->addCondition('having', 'or', $column, $op, $value, true);
	}

	/**
	 * Opens an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function havingOpen()
	{
		$this->having[] = array(
			'type' => 'and',
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Opens an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function notHavingOpen()
	{
		$this->having[] = array(
			'type' => 'and',
			'not' => true,
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function havingClose()
	{
		$this->having[] = array(
			'nesting' => 'close',
		);

		return $this;
	}

	/**
	 * Closes an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function notHavingClose()
	{
		return $this->havingClose();
	}

	/**
	 * Opens an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function andHavingOpen()
	{
		$this->having[] = array(
			'type' => 'and',
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Opens an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function andNotHavingOpen()
	{
		$this->having[] = array(
			'type' => 'and',
			'not' => true,
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'and having' nesting.
	 *
	 * @return  $this
	 */
	public function andHavingClose()
	{
		return $this->havingClose();
	}

	/**
	 * Closes an 'and not having' nesting.
	 *
	 * @return  $this
	 */
	public function andNotHavingClose()
	{
		return $this->havingClose();
	}

	/**
	 * Opens an 'or having' nesting.
	 *
	 * @return  $this
	 */
	public function orHavingOpen()
	{
		$this->having[] = array(
			'type' => 'or',
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Opens an 'or having' nesting.
	 *
	 * @return  $this
	 */
	public function orNotHavingOpen()
	{
		$this->having[] = array(
			'type' => 'or',
			'not' => true,
			'nesting' => 'open',
		);

		return $this;
	}

	/**
	 * Closes an 'or having' nesting.
	 *
	 * @return  $this
	 */
	public function orHavingClose()
	{
		return $this->havingClose();
	}

	/**
	 * Closes an 'or having' nesting.
	 *
	 * @return  $this
	 */
	public function orNotHavingClose()
	{
		return $this->havingClose();
	}
}