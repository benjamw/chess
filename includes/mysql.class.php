<?php
/*
+---------------------------------------------------------------------------
|
|   mysql.class.php (php 4.x)
|
|   by Benjam Welker
|   http://iohelix.net
|   based on works by W. Jason Gilmore
|   http://www.wjgilmore.com; http://www.apress.com
|
+---------------------------------------------------------------------------
|
|   > MySQL DB Queries module
|   > Date started: 2005-09-02
|   >  Last edited: 2006-11-14
|
|   > Module Version Number: 0.9.2
|
+---------------------------------------------------------------------------
*/

class mysql
{
	var $linkid;      // MySQL Resource ID
	var $host;        // MySQL Host name
	var $user;        // MySQL Username
	var $pswd;        // MySQL password
	var $db;          // MySQL Database
	var $query;       // MySQL query
	var $query_time;  // Time it took to run the query
	var $pass_query;  // Query passed by argument
	var $result;      // Query result
	var $line;        // Line of query
	var $file;        // File of query
	var $error;       // Any error message encountered while running
	var $log_path;    // The path to the log file
	var $querycount;  // Total number of queries executed since class inception
	var $error_debug; // Allows for error debug output
	var $query_debug; // Allows for output of all queries all the time
	var $super_debug; // Allows output of ALL debugging output



	/* Class constructor.  Initializes the host, user, pswd, db and log fields */
	function mysql($host, $user, $pswd, $db, $log_path = './')
	{

		/*#
		  #
		  # The following was moved to the config file for
		  # ease of use by the admin (so they don't have to config
		  # multiple files, and only have to edit one),
		  # but the original source was left here for those who wish
		  # to implement this class in their own creations.
		  #
		  # to enable, just change  /*#  above to  //*#  and edit settings
		  #
		  # to disable, change  //*#  above to  /*#
		  # and copy-paste this section into your own config file
		  #

		// BEGIN CONFIG ----------------------

		define ('DB_ERR_EMAIL_ERRORS', true); // (true / false) set to true to email errors to TO address below
		define ('DB_ERR_LOG_ERRORS'  , true); // (true / false) set to true to log errors in mysql.err file
		define ('DB_ERR_TO'          , 'yourname@yoursite.com'); // set your TO email address
		define ('DB_ERR_SUBJECT'     , 'Query Error'); // don't really need to change this
		define ('DB_ERR_FROM'        , 'yourname@yoursite.com'); // set your FROM email address (can be the same as TO above)
		define ('FILE_PATH_END'      , '/yourscript/'); // the name of the directory containing the script (with wrapping / )

		// END CONFIG ------------------------

		  #
		  # end of config section removal
		  #
		  #*/

		$this->host = $host;
		$this->user = $user;
		$this->pswd = $pswd;
		$this->db   = $db;
		$this->log_path = $log_path;

		// each of these can be set independently as needed
		$this->error_debug = false; // set to true for output of errors
		$this->query_debug = false; // set to true for output of every query
		$this->super_debug = false; // set to true for other debugging output (like passed query arguments, etc.)

		// make sure the log path ends with /
		if (strrpos($this->log_path,'/') != (strlen($this->log_path) - 1))
		{
			$this->log_path .= '/';
		}
	}



	/* Connect to the MySQL database server */
	function connect( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->load_arguments($args);
		}

		$this->linkid = @mysql_connect($this->host, $this->user, $this->pswd);

		if ( ! $this->linkid)
		{
			$this->error = mysql_errno( ).': '.mysql_error( );
			$this->error_report( );

			if ($this->error_debug)
			{
				echo "There was an error connecting to the server in {$this->file_name} on line {$this->line}:<br />ERROR - {$this->error}";
			}
			else
			{
				die('There was a database error. An email has been sent to the system administrator.');
			}
		}
	}



	/* Selects the MySQL Database */
	function select( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->load_arguments($args);
		}

		if ( ! @mysql_select_db($this->db, $this->linkid))
		{
			$this->error = mysql_errno($this->linkid).': '.mysql_error($this->linkid);
			$this->error_report( );

			if ($this->error_debug)
			{
				echo "There was an error selecting the database in {$this->file_name} on line {$this->line}:<br />ERROR - {$this->error}";
			}
			else
			{
				die('There was a database error. An email has been sent to the system administrator.');
			}
		}
	}



	/* Connects to the server AND selects the default database in one function */
	function connect_select( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		$this->clear_arguments( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->load_arguments($args);
		}

		$this->connect( );
		$this->select( );
	}



	/* Execute Database Query */
	function query( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( ); // don't clear unless we have new
			$this->load_arguments($args);

			if (false !== $this->pass_query)
			{
				$this->query = $this->pass_query;
			}
		}

		if ($this->super_debug)
		{
			echo 'QUERY ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		$done = true; // innocent until proven guilty

		// start time logging
		$time = microtime_float( );
		$this->result = @mysql_query($this->query, $this->linkid);
		$this->query_time = microtime_float( ) - $time;

		if ($this->query_debug)
		{
			$this->query = trim(preg_replace('/\\s+/', ' ', $this->query));
			echo "<div style='background:white;color:black;'><br />{$this->query} - <strong>Aff(".$this->affected_rows( ).") - {$this->file_name}: {$this->line}</strong></div>";
		}

		if ( ! $this->result)
		{
			$this->error = mysql_errno($this->linkid).': '.mysql_error($this->linkid);
			$this->error_report( );

			if ($this->error_debug)
			{
				echo "<div style='background:#900;color:white;'>There was an error in your query in {$this->file_name} on line {$this->line}: ERROR - {$this->error}<br />Query: {$this->query}</div>";
				print_r(debug_backtrace( ));
			}
			else
			{
				$this->error = 'There was a database error. An email has been sent to the system administrator.';
			}

			$done = false;
		}

		if ($done)
		{
			$this->querycount++;
			return $this->result;
		}

		return false;
	}



	/* Determine total rows affected by query */
	function affected_rows( )
	{
		$count = @mysql_affected_rows($this->linkid);
		return $count;
	}



	/* Determine total rows returned by query */
	function num_rows( )
	{
		$count = @mysql_num_rows($this->result);

		if ( ! $count)
		{
			return 0;
		}

		return $count;
	}


	/** public function insert
	 *		Insert the associative data array into the table.
	 *			$data['field_name'] = value
	 *			$data['field_name2'] = value2
	 *		If the field name has a trailing space: $data['field_name ']
	 *		then the query will insert the data with no sanitation
	 *		or wrapping quotes (good for function calls, like NOW( )).
	 *
	 * @param string table name
	 * @param array associative data array
	 * @param string [optional] where clause (for updates)
	 * @param bool [optional] whether or not we should replace values (true / false)
	 * @action execute a mysql query
	 * @return int insert id for row
	 */
	function insert($table, $data_array, $where = '', $replace = false)
	{
		$where = trim($where);
		$replace = (bool) $replace;

		if ('' == $where) {
			$query  = (false == $replace) ? ' INSERT ' : ' REPLACE ';
			$query .= ' INTO ';
		}
		else {
			$query = ' UPDATE ';
		}

		$query .= '`'.$table.'`';

		if ( ! is_array($data_array)) {
			throw new MySQLException(__METHOD__.': Trying to insert non-array data');
		}
		else {
			$query .= ' SET ';
			foreach ($data_array as $field => $value) {
				if (is_null($value)) {
					$query .=  " `{$field}` = NULL , ";
				}
				elseif (' ' == substr($field, -1, 1)) { // i picked a trailing space because it's an illegal field name in MySQL
					$field = trim($field);
					$query .= " `{$field}` = {$value} , ";
				}
				else {
					$query .= " `{$field}` = '".sani($value)."' , ";
				}
			}

			$query = substr($query, 0, -2).' '; // remove the last comma (but preserve those spaces)
		}

		$query .= ' '.$where.' ';
		$this->query = $query;
		$return = $this->query( );

		if ('' == $where) {
			return $this->fetch_insert_id( );
		}
		else {
			return $return;
		}
	}


	/** public function multi_insert
	 *		Insert the array of associative data arrays into the table.
	 *			$data[0]['field_name'] = value
	 *			$data[0]['field_name2'] = value2
	 *			$data[0]['DBWHERE'] = where clause [optional]
	 *			$data[1]['field_name'] = value
	 *			$data[1]['field_name2'] = value2
	 *			$data[1]['DBWHERE'] = where clause [optional]
	 *
	 * @param string table name
	 * @param array associative data array
	 * @param bool [optional] whether or not we should replace values (true / false)
	 * @action execute multiple mysql queries
	 * @return array insert ids for rows (with original keys preserved)
	 */
	function multi_insert($table, $data_array, $replace = false)
	{
		if ( ! is_array($data_array)) {
			throw new MySQLException(__METHOD__.': Trying to multi-insert non-array data');
		}
		else {
			$result = array( );

			foreach ($data_array as $key => $row) {
				$where = (isset($row['DBWHERE'])) ? $row['DBWHERE'] : '';
				unset($row['DBWHERE']);
				$result[$key] = $this->insert($table, $row, $where, $replace);
			}
		}

		return $result;
	}


	/** public function delete
	 *		Delete the row from the table
	 *
	 * @param string table name
	 * @param string where clause
	 * @action execute a mysql query
	 * @return result
	 */
	function delete($table, $where)
	{
		$query = "
			DELETE
			FROM `{$table}`
			{$where}
		";

		$this->query = $query;

		try {
			return $this->query( );
		}
		catch (MySQLException $e) {
			throw $e;
		}
	}


	/** public function multi_delete
	 *		Delete the array of data from the table.
	 *			$table[0] = table name
	 *			$table[1] = table name
	 *
	 *			$where[0] = where clause
	 *			$where[1] = where clause
	 *
	 *		If recursive is true, all combinations of table name
	 *		and where clauses will be executed.
	 *
	 *		If only one table name is set, that table will
	 *		be used for all of the queries, looping through
	 *		the where array
	 *
	 *		If only one where clause is set, that where clause
	 *		will be used for all of the queries, looping through
	 *		the table array
	 *
	 * @param mixed table name array or single string
	 * @param mixed where clause array or single string
	 * @param bool optional recursive (default false)
	 * @action execute multiple mysql queries
	 * @return array results
	 */
	function multi_delete($table_array, $where_array, $recursive = false)
	{
		if ( ! is_array($table_array)) {
			$recursive = false;
			$table_array = (array) $table_array;
		}

		if ( ! is_array($where_array)) {
			$recursive = false;
			$where_array = (array) $where_array;
		}

		if ($recursive) {
			foreach ($table_array as $table) {
				foreach ($where_array as $where) {
					$result[] = $this->delete($table, $where);
				}
			}
		}
		else {
			if (count($table_array) == count($where_array)) {
				for ($i = 0, $count = count($table_array); $i < $count; ++$i) {
					$result[] = $this->delete($table_array[$i], $where_array[$i]);
				}
			}
			elseif (1 == count($table_array)) {
				$table = $table_array[0];
				foreach ($where_array as $where) {
					$result[] = $this->delete($table, $where);
				}
			}
			elseif (1 == count($where_array)) {
				$where = $where_array[0];
				foreach ($table_array as $table) {
					$result[] = $this->delete($table, $where);
				}
			}
			else {
				throw new MySQLException(__METHOD__.': Trying to multi-delete with incompatible array sizes');
			}
		}

		return $result;
	}



	/* Return query result row as an object */
	function fetch_object( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_OBJECT ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_object($this->result);
		return $row;
	}



	/* Return query result row as an indexed array */
	function fetch_row( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_ROW ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_row($this->result);
		return $row;
	}



	/* Return query result row as an associative array */
	function fetch_assoc( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_ASSOC ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_assoc($this->result);
		return $row;
	}



	/* Return query result row as an associative array and an indexed array */
	function fetch_both( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_BOTH ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_array($this->result, MYSQL_BOTH);
		return $row;
	}



	/* Return query result as an array of arrays */
	function fetch_array( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_ARRAY ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$arr = array( );
		while ($row = @mysql_fetch_array($this->result))
		{
			$arr[] = $row;
		}

		return $arr;
	}



	/* Return query result as an array of single values */
	function fetch_value_array( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_VALUE_ARRAY ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$arr = array( );
		while ($row = @mysql_fetch_row($this->result))
		{
			$arr[] = $row[0];
		}

		return $arr;
	}



	/* Return single query result value */
	function fetch_value( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->super_debug)
		{
			echo 'FETCH_VALUE ';
			echo '<pre>';
			print_r($this->get_arguments( ));
			echo '</pre>';
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_row($this->result);
		return $row[0];
	}



	/* Return the total number of queries executed during
		 the lifetime of this object                         */
	function num_queries( )
	{
		return $this->querycount;
	}



	/* get the id for the previous INSERT command */
	function fetch_insert_id( )
	{
		return @mysql_insert_id($this->linkid);
	}



	/* get the errors, if any */
	function fetch_error( )
	{
		return $this->error;
	}



	/* report the errors to the admin */
	function error_report( )
	{
		// generate an error report and then act according to configuration
		$error_report  = "An error has been generated by the server.\nFollowing is the debug information:\n\n";
		$error_report .= "   *  File: {$this->file_name}\n";
		$error_report .= "   *  Line: {$this->line}\n";
		$error_report .= "   * Error: {$this->error}\n";

		$error_report_short = "\n" . date('Y-m-d H:i:s') . " Error in {$this->file_name} on line {$this->line}: ERROR - {$this->error}";

		// if a database query caused the error, show the query
		if ('' != $this->query)
		{
			$error_report .= "   * Query: {$this->query}\n";
			$error_report_short .= " [sql={$this->query}]";
		}

		// send the error as email if set
		if (DB_ERR_EMAIL_ERRORS)
		{
			mail(DB_ERR_TO, trim(DB_ERR_SUBJECT), $error_report."\n\n".safe_var_export(debug_backtrace( ))."\n\n\$GLOBALS = ".safe_var_export($GLOBALS), 'From: '.DB_ERR_FROM."\r\n");
		}

		// log the error (remove line breaks and multiple concurrent spaces)
		$this->logger(trim(preg_replace('/\\s+/', ' ', $error_report_short))."\n");
	}



	/* log any errors */
	function logger($report)
	{
		if (DB_ERR_LOG_ERRORS)
		{
			$log = $this->log_path . "mysql.err";
			$fp = fopen($log,'a+');
			fwrite($fp,$report);
			@chmod($log, 0777);
			fclose($fp);
		}
	}



	/* extract the arguments */
	function load_arguments($args)
	{
		foreach ($args as $arg)
		{
			if ($this->super_debug)
			{
				echo '<hr />arg = (';
				echo stripslashes(var_export($arg, true)).') - ';
				echo 'is_line('.var_export(is_int($arg), true).') ';
				echo 'is_file('.var_export(('/' == substr($arg, 0, 1)) || (0 != preg_match('/^\\w:/', $arg)), true).') ';
				echo 'is_database('.var_export(is_string($arg) && (0 != strlen($arg)) && (false === strpos($arg, ' ')), true).') ';
				echo 'is_query('.var_export(0 != preg_match('/^\\s*(SELECT|INSERT|UPDATE|DELETE|DROP|DESC|REPLACE|CREATE|ALTER|ANALYZE|BACKUP|CACHE|CHANGE|CHECK|COMMIT|DEALLOCATE|DO|EXECUTE|EXPLAIN|FLUSH|GRANT|HANDLER|HELP|KILL|LOAD|LOCK|MASTER|OPTIMIZE|PREPARE|PURGE|RENAME|REPAIR|RESET|RESTORE|REVOKE|ROLL|SAVE|SET|SHOW|START|STOP|TRUNCATE|UNLOCK|USE)/i', $arg), true).') --- ';
			}

			if (is_int($arg)) // it's an integer
			{
				if ($this->super_debug) echo 'LINE - ';
				$this->line = $arg;
				if ($this->super_debug) echo $this->line;
			}
			elseif (('/' == substr($arg, 0, 1)) || (0 != preg_match('/^\\w:/', $arg))) // the string begins with '/' or a drive letter
			{
				if ($this->super_debug) echo 'FILE - ';
				$this->file_name = substr($arg, strpos($arg, FILE_PATH_END));
				if ($this->super_debug) echo $this->file_name;
			}
			elseif (is_string($arg) && (0 != strlen($arg)) && (false === strpos($arg, ' '))) // there are no spaces
			{
				if ($this->super_debug) echo 'DATABASE - ';
				$this->db = $arg;
				if ($this->super_debug) echo $this->db;
			}
			elseif (0 != preg_match('/^\\s*(SELECT|INSERT|UPDATE|DELETE|DROP|DESC|REPLACE|CREATE|ALTER|ANALYZE|BACKUP|CACHE|CHANGE|CHECK|COMMIT|DEALLOCATE|DO|EXECUTE|EXPLAIN|FLUSH|GRANT|HANDLER|HELP|KILL|LOAD|LOCK|MASTER|OPTIMIZE|PREPARE|PURGE|RENAME|REPAIR|RESET|RESTORE|REVOKE|ROLL|SAVE|SET|SHOW|START|STOP|TRUNCATE|UNLOCK|USE)/i', $arg)) // it begins with a query word
			{
				if ($this->super_debug) echo 'QUERY - ';
				$this->pass_query = $arg;
				if ($this->super_debug) echo $this->pass_query;
			}
			else
			{
				if ($this->super_debug) echo 'UNKNOWN - ';
				$arg_dump = var_export($arg, true);
				$this->error = 'Unknown argument found: ' . $arg_dump;
				if ($this->super_debug) echo $this->error;
			}
		}

		// wait until after all arguments are entered before outputting error
		// because the error may happen on the first argument and the other
		// arguments have important error report data (it's what they're for)
		if ('Unknown argument' == substr((string) $this->error, 0, 16))
		{
			$this->error_report( );
		}

		if ($this->super_debug) {echo'<pre>';print_r($this->get_arguments( ));echo'</pre>';}
	}



	/* clear the arguments */
	function clear_arguments( )
	{
		// don't clear query or db as we may use them later
		$this->line = false;
		$this->file_name = false;
		$this->error = false;
		$this->pass_query = false;
	}



	/* return the arguments */
	function get_arguments( )
	{
		$args['line'] = $this->line;
		$args['file'] = $this->file_name;
		$args['error'] = $this->error;
		$args['query'] = $this->pass_query;

		return $args;
	}

} // end of mysql class



/*
 +---------------------------------------------------------------------------
 |   > Extra SQL Functions
 +---------------------------------------------------------------------------
*/


/* escape the data before it gets queried into the database */
function sani($data)
{
	if (is_array($data))
	{
		return array_map('sani', $data);
	}
	else
	{
		if (get_magic_quotes_gpc( ))
		{
			$data = stripslashes($data);
		}

#    $data = htmlentities($data, ENT_NOQUOTES); // convert html to &html;

		if (function_exists('mysql_real_escape_string'))
		{
			$data = mysql_real_escape_string($data); // php 4.3.0+
		}
		else
		{
			$data = mysql_escape_string($data); // php 4.0+
		}

		return $data;
	}
}


if ( ! function_exists('microtime_float'))
{
	function microtime_float( )
	{
		list($usec, $sec) = explode(' ', microtime( ));
		return ((float)$usec + (float)$sec);
	}
}


function safe_var_export($var)
{
	if ( ! is_array($var))
	{
		return var_export($var, true);
	}

	foreach ($var as $key => $data)
	{
		if (('GLOBALS' == $key) || ($data == $var))
		{
			$var[$key] = 'RECURSION';
			continue;
		}
	}

	return var_export($var, true);
}

?>