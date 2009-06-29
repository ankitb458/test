<?php
#
# MC Database Statement Object
# ----------------------------
# Copyright, Mesoconcepts <http://www.mesoconcepts.com>
# All rights reserved
#

class dbs extends PDOStatement
{
	#
	# exec()
	#

	public function exec($args = null)
	{
		if ( $this->execute($args) === false  )
		{
			$error = $this->errorInfo();

			if ( isset($error[2]) )
			{
				$captions =& new captions;

				$message = $captions->get(
						'err_sql_error',
						array(
							'sql' => $this->queryString,
							'err_msg' => $error[2]
							)
						);

				throw new exception($message);
			}
		}
	} # exec()


	#
	# num_rows()
	#

	public function num_rows()
	{
		return $this->rowCount();
	} # num_rows()


	#
	# num_cols()
	#

	public function num_cols()
	{
		return $this->columnCount();
	} # num_cols()


	#
	# get_results()
	#

	public function get_results($args = null)
	{
		if ( isset($args) )
		{
			$this->execute($args);
		}

		return $this->fetchAll(PDO::FETCH_ASSOC);
	} # get_results()


	#
	# get_row()
	#

	public function get_row($args = null)
	{
		if ( isset($args) )
		{
			$this->execute($args);
		}

		return $this->fetch(PDO::FETCH_ASSOC & PDO::FETCH_BOUND);
	} # get_row()


	#
	# get_col()
	#

	public function get_col($args = null)
	{
		if ( isset($args) )
		{
			$this->execute($args);
		}

		return $this->fetchAll(PDO::FETCH_COLUMN);
	} # get_col()


	#
	# get_var()
	#

	public function get_var($args = null)
	{
		if ( isset($args) )
		{
			$this->execute($args);
		}

		return $this->fetchColumn();
	} # get_var()


	#
	# bind_var()
	#

	public function bind_var($param, &$var)
	{
		return $this->bindParam($param, $var);
	} # bind_var()


	#
	# bind()
	#

	public function bind($column, &$var)
	{
		return $this->bindcolumn($column, $var);
	} # bind()


	#
	# dump()
	#

	public function dump($args = null)
	{
		debug::dump($this->queryString);
		debug::dump($this->get_results($args));
	} # dump()
} # dbs
?>