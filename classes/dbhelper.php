<?php

class db
{
	var $m_db;
	var $m_prefix;

	var $m_result;
	var $m_numRows;
	var $m_row;
	var $m_curRow;
	var $m_curVal;
	var $m_numCalls;
	
	function db($dbserver, $dbuser, $dbpass, $dbprefix) {
		$this->m_result = null;
		$this->m_numRows = 0;
		$this->m_row = null;
		$this->m_curRow = 0;
		$this->m_curVal = 0;
		$this->m_prefix = $dbprefix;
		$this->m_db = mysql_connect($dbserver, $dbuser, $dbpass) or die("Connect Error: " .mysql_error());
		
		mysql_select_db($this->m_prefix, $this->m_db) or die("Couldn't open db:" .mysql_error());
	}

	function close() {
		mysql_close($this->m_db);
	}

	function create_db($dbname) {
	}
	
	function create_table($tablename) {
	}

	function isDuplicateName($nameField, $nameVal, $table, $whereField, $whereVal) {
		$sql = "select $nameField from $table where $whereField='$whereVal' and $nameField='$nameVal'";

		$this->runQuery($sql);

		$numRows = $this->getNumRows();
		if($numRows == 0)
			return false;
		else
			return true;
	}

	function begin() {
		mysql_query("BEGIN");
	}
	
	function commit() {
		mysql_query("COMMIT");
	}

	function rollback() {
		mysql_query("ROLLBACK");
	}

	function runQuery($query) {
		GLOBAL $dbcalls;
		$this->m_result = null;
		$this->m_numRows = 0;
		$this->m_row = null;
		$this->m_curRow = 0;
		$this->m_curVal = 0;
		++$dbcalls;
//echo $query . "<br>";
		$this->m_result = mysql_query($query);// or die("Error in runQuery: " . mysql_error());	
		
		return $this->m_result;
	}
	
	function getRowObject() {
		$this->m_row = mysql_fetch_object($this->m_result) or die ("Error in row object fetch");
		return $this->m_row;
	}

	function getErrorMsg() {
		return mysql_error();
	}

	function getNumRows() {
		if($this->m_result) {
			$this->m_numRows = mysql_num_rows($this->m_result);	
			return $this->m_numRows;
		}
	}

	function getNumRowsAffected() {
		return mysql_affected_rows();
	}
	
	function getNextRow() {
		$this->m_row = mysql_fetch_row($this->m_result) or die("Error executing query: " . mysql_error());
		$this->m_curRow++;
		return $this->m_row;
	}

	function getNextValue() {
		$value = $this->m_row[$this->m_curVal];	 
		$this->m_curVal++;
		return $value;
	}
	
	function getLastInsertId() {
		return mysql_insert_id();
	}
}
?>
