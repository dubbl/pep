<?php
	
	/**
	 * This class is the base class for all models and contains functions for 
	 * dealing with a SQLite 3 database. 
	 * 
	 * @author robertabramski
	 *
	 */
	class Model
	{
		private $db;
		private $type;
		private $path;
		
		public $menu;
		public $table;
		public $allow = array('admin');
		public $fields;
		public $creatable = true;
		public $updateable = true;
		public $deletable = true;
		public $description;
		
		/**
		 * Constructs the class. 
		 * 
		 */
		public function __construct()
		{
			$this->path = DB_DIR.'pep.db';
	
			switch(true)
			{
				case class_exists('PDO'):
					$this->db = new PDO('sqlite:'.$this->path);
					if($this->db) $this->type = 'PDO';
				break;
				
				case class_exists('SQLite3'):
					$this->db = new SQLite3($this->path);
					if($this->db) $this->type = 'SQLite3';
				break; 
			}
			
			// Set names intuitively from class name.
			$this->table = strtolower(get_class($this));
			$this->menu = ucfirst(strtolower(get_class($this)));
		}
		
		/**
		 * Destructs the class.
		 * 
		 */
		public function __destruct()
		{
			if($this->db) $this->close();
		}
		
		/**
		 * Returns the name of the type of the connection made. The value will be
		 * either PDO or SQLite3. SQLite version 2 is not supported. 
		 * 
		 * @access	public
		 * 
		 */
		public function get_type()
		{
			return $this->type;
		}
		
		/**
		 * Returns the number of rows in a table.
		 * 
		 * @access public
		 * @param string|array	 	$where	The where clause as a string or an array.
		 * @param string			$table	The table selected.
		 * 
		 */
		public function num_rows($where = null, $table = null)
		{
			$result = $this->select('count(*)', $where, ($table ? $table : $this->table));
			return $result[0][0];
		}
		
		/**
		 * Returns the last row id after a SELECT query has run.
		 * 
		 * @access	public
		 * @return	int
		 * 
		 */
		public function last_id()
		{
			switch($this->type)
			{
				case 'PDO': 	return $this->db->lastInsertId(); 
				case 'SQLite3': return $this->db->lastInsertRowID(); 
			}
		}
		
		/**
		 * Returns the bottom row id without a SELECT query running before it.
		 * 
		 * @access 	public
		 * @param 	string 		$id_name	The name of the row id.
		 * @param 	string 		$table		The table selected.
		 * @return	int
		 * 
		 */
		public function bottom_row($id_name, $table = null)
		{
			$query = sprintf('SELECT %s FROM %s ORDER BY ROWID DESC LIMIT 1', $id_name, ($table ? $table : $this->table));
			$result = $this->smart_query($query);
			return $result[0][$id_name]; 
		}
		
		/**
		 * Selects the active table to run queries to.
		 *   
		 * @access	public
		 * @param 	string 	$table	The table selected.
		 * 
		 */
		public function from($table)
		{
			$this->table = $table;
		}
		
		/**
		 * Builds a select query. 
		 * 
		 * @access 	public
		 * @param 	string|array	$data	The data to insert as a string or an array.
		 * @param 	string|array	$where	The where clause as a string or an array.
		 * @param 	int			 	$limit	The limit.
		 * @param 	int			 	$offset	The offset.
		 * @param 	string		 	$table	The table selected.
		 * 
		 */
		public function select($data, $where = null, $limit = null, $offset = null, $table = null)
		{
			if(empty($data)) return false;
			
			if(!is_array($data)) $data = array($data);
			if($where) $dests = $this->handle_where_clause($where);
			
			$query  = 'SELECT '.implode(', ', $data);
			$query .= ' FROM '.($table ? $table : $this->table);
			$query .= ($where ? ' WHERE '.implode('', $dests) : '');
			$query .= ($limit ? ' LIMIT '.$limit .($offset ? ', '.$offset : '') : '');		
			
			return $this->smart_query($query);
		}
		
		/**
		 * Builds an insert query. 
		 * 
		 * @access	public
		 * @param 	array 		$data	The data to insert as an associative array.
		 * @param 	string		$table	The table selected.
		 * 
		 */
		public function insert($data, $table = null)
		{
			if(empty($data)) return false;
			
			foreach($data as $key => $val)
			{
				$key = ' '.$key;
				if(is_null($val)) $val = 'NULL';
				else $val = $this->escape($val);
				
				$f[] = $key;
				$v[] = $val;
			}
			
			$fields = implode(', ', $f);
			$values = implode(', ', $v);
			
			$query = 'INSERT INTO '.($table ? $table : $this->table).' ('.$fields.') VALUES ('.$values.')';
			return $this->smart_query($query);
		}
		
		/**
		 * Builds an update query.
		 * 
		 * @access	public
		 * @param 	string|array 	$data	The data to update as a string or an associative array.
		 * @param 	string|array 	$where	The where clause as a string or an array.
		 * @param 	string			$table	The table selected.
		 * 
		 */
		public function update($data, $where = null, $table = null)
		{
			if(empty($data)) return false;
			
			if(!is_array($data))
			{
				$sets = array($data);
			}
			else
			{
				foreach($data as $key => $val)
				{
					$key = $key.' = ';
					if(is_null($val)) $val = 'NULL';
					else $val = $this->escape($val);
	
					$sets[] = $key.$val;
				}
			}
			
			if($where) $dests = $this->handle_where_clause($where);
			
			$query  = 'UPDATE '.($table ? $table : $this->table);
			$query .= ' SET '.implode(', ', $sets);
			$query .= ($where ? ' WHERE '.implode('', $dests) : '');
			
			return $this->smart_query($query);
		}
		
		/**
		 * Builds a delete query.
		 * 
		 * @access	public
		 * @param 	string|array	$where	The where clause as a string or an array.
		 * @param 	string			$table	The table selected.
		 * 
		 */
		public function delete($where, $table = null)
		{
			$dests = $this->handle_where_clause($where);
			
			$query  = 'DELETE FROM '.($table ? $table : $this->table);
			$query .= ($where ? ' WHERE '.implode('', $dests) : '');
			
			return $this->smart_query($query);
		}
		
		/**
		 * Handles where clause concatenation.
		 * 
		 * @access 	private	
		 * @param 	string|array	$where	The where string or associative array.
		 * 
		 */
		private function handle_where_clause($where)
		{
			if(!is_array($where))
			{
				$dests = array($where);
			}
			else
			{
				foreach($where as $key => $val)
				{
					$prefix = (count($dests) == 0) ? '' : ' AND ';
	
					if($val !== '')
					{
						$key = $key.' =';
						if(is_string($val)) $val = ' '.$this->escape($val);
						else $val = ' '.$val;
					}
	
					$dests[] = $prefix.$key.$val;
				}
			}
			
			return $dests;
		}
		
		/**
		 * Runs a query and returns a value depending on the type of statement run. 
		 * A SELECT statement run will return the selected data in a multidimensional 
		 * array. An INSERT statement returns the last inserted id and UPDATE and 
		 * DELETE queries return the number of rows affected. If none of these types 
		 * are detected, then a regular query is run.
		 * 
		 * @access	public
		 * @see 	query()
		 * @param 	string $query	The query string.
		 * @return 	mixed
		 * 
		 */
		public function smart_query($query)
		{
			if(stripos($query, 'SELECT') === 0)
			{
				$result = $this->query($query);
				if(!$result) return false;
				
				switch($this->type)
				{
					case 'PDO': return $result->fetchAll(PDO::FETCH_ASSOC);
					case 'SQLite3':
						$arr = array(); $i = 0;
						while($res = $result->fetchArray(SQLITE3_ASSOC)) { $arr[$i] = $res; $i++; }
						return $arr;
				}
			}
			else if(stripos($query, 'INSERT') === 0)
			{
				$result = $this->query($query);
				if(!$result) return false;
				
				switch($this->type)
				{
					case "PDO": 	return $this->db->lastInsertId(); 
					case "SQLite3": return $this->db->lastInsertRowID();
				}
			}
			else if(stripos($query, 'UPDATE') === 0 || stripos($query, 'DELETE') === 0)
			{
				switch($this->type)
				{
					case "PDO": 	return $this->db->exec($query);
					case "SQLite3": 
						$this->db->exec($query);
						return $this->db->changes();
				}
			}
			
			return $this->query($query);
		}
		
		/**
		 * Gets text describing the most recent failed request. 
		 * 
		 * @access	public
		 * @return	string
		 * 
		 */
		public function get_error()
		{
			switch($this->type)
			{
				case 'PDO': 	$error = $this->db->errorInfo(); return $error[2]; 
				case 'SQLite3': return $this->db->lastErrorMsg(); 
			}
		}
		
		/**
		 * Runs a query and returns a result object as either a PDOStatement object 
		 * or a SQLite3Result depending on which type of connection is made. A value
		 * of false is returned if neither object is returned.
		 * 
		 * @access	public
		 * @see		get_type()
		 * @param 	string $query	The query string.
		 * @return 	mixed
		 * 
		 */
		public function query($query)
		{
			$result = @$this->db->query($query);
			return $result ? $result : false;
		}

		/**
		 * Executes a query with no return value.
		 * 
		 * @access	public
		 * @param 	string $query	The query string.
		 * @return	void
		 * 
		 */
		public function exec($query)
		{
			switch($this->type)
			{
				case 'PDO': 	$this->db->exec($query); break; 
				case 'SQLite3': $this->db->exec($query); break; 
			}
		}
		
		/**
		 * Escapes a string to be injected into a query.
		 * 
		 * @access	public
		 * @param 	string 	$value	The string to escape.
		 * @return 	string	The escaped string.
		 * 
		 */
		public function escape($value)
		{
			switch($this->type)
			{
				case 'PDO': 	return $this->db->quote($value); break; 
				case 'SQLite3': return "'".$this->db->escapeString($value)."'"; break; 
			}
		}
		
		/**
		 * Closes a database connection.
		 * 
		 * @access	public
		 * 
		 */
		public function close()
		{
			switch($this->type)
			{
				case 'PDO': 	$this->db = null; break;
				case 'SQLite3': $this->db->close(); break;
			}	
		}
	}

?>
