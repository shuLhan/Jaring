<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/

//{{{ util: safely open PDO class.
class SafePDO extends PDO
{
	public static function exception_handler($exception)
	{
		// Output the exception details
		die("Uncaught exception: ". $exception->getMessage());
	}

	public function __construct($dsn, $username="", $password="", $driver_options=array())
	{
		// Temporarily change the PHP exception handler while we . . .
		set_exception_handler(array(__CLASS__, "exception_handler"));

		// . . . create a PDO object
		parent::__construct($dsn, $username, $password, $driver_options);

		// Change the exception handler back to whatever it was before
		restore_exception_handler();
	}
}
//}}}

class JaringDB
{
	public $_class		= "";
	public $_url		= "";
	public $_user		= "";
	public $_pass		= "";
	public $_pool_min	= 0;
	public $_pool_max	= 100;
	public $_dbo		= null;
	public $_ps			= null;

//{{{ constructor
	public function __construct ($url, $username, $password, $pool_min
			, $pool_max)
	{
		$this->_url			= $url;
		$this->_user		= $username;
		$this->_pass		= $password;
		$this->_pool_min	= $pool_min;
		$this->_pool_max	= $pool_max;
		$this->_dbo			= null;
		$this->_ps			= null;
	}
//}}}
//{{{ db : initialize database.
	public function init ()
	{
		if (stristr ($this->_url, "sqlite") === FALSE) {
			$this->_class = "postgresql";
			$this->create ();
		} else {
			$this->init_sqlite ();
			$this->_class = "sqlite";
		}
	}
//}}}
//{{{ db : initialize sqlite database.
	public function init_sqlite ()
	{
		// Check if sqlite is file or memory.
		$a = explode(":", $this->_url);

		if (count ($a) === 2) {
			// sqlite is file based, recreate db url using app. path.
			$a[1] = $f_db	= APP_PATH . $a[1];
			$this->_url	= implode (":", $a);

			if (file_exists ($f_db)) {
				$this->create ();
				return;
			}
		}

		$this::create ();

		// Populate new database file.
		$f_sql = APP_PATH ."/db/init.ddl.sql";
		$this->execute_script ($f_sql);

		$f_sql = APP_PATH ."/db/init.dml.sql";
		$this->execute_script ($f_sql);

		$f_sql = APP_PATH ."/db/app.sql";
		$this->execute_script ($f_sql);

		// insert logo
		$fp = fopen (APP_PATH ."/images/logo.svg", "rb");
		$q	= " update _profile set logo_type = 'image/svg+xml', logo = ? where id = 1 ";

		$this->_ps = $this->_dbo->prepare ($q);
		$this->_ps->bindParam (1, $fp, PDO::PARAM_LOB);
		$this->_ps->execute ();
	}
//}}}
//{{{ db : create PDO object.
	public function create ()
	{
		$this->_dbo = new SafePDO ($this->_url, $this->_user, $this->_pass);
		$this->_dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
//}}}
//{{{ db : execute script
	public function execute_script ($f_sql)
	{
		if (! file_exists ($f_sql)) {
			return false;
		}

		$f_sql_v	= file_get_contents ($f_sql);
		$queries	= explode (";", $f_sql_v);

		foreach ($queries as $q) {
			try {
				$this->_dbo->exec ($q);
			} catch (Exception $e) {
				error_log ($q);
				throw $e;
			}
		}

		return true;
	}
//}}}
//{{{ db : do execute
	public function do_exec ($bindv = null, $fetch = false)
	{
		$rs = [];
		$s = true;

		if (null !== $bindv) {
			$s = $this->_ps->execute ($bindv);
		} else {
			$s = $this->_ps->execute ();
		}

		if ($s && $fetch) {
			$rs = $this->_ps->fetchAll (PDO::FETCH_ASSOC);

			$this->_ps->closeCursor ();
		}

		return $rs;
	}
//}}}
//{{{ db : execute query
	/**
	  @param q		: query.
	  @param bindv	: array of binding value, if query containt "?".
	  @param fetch	: should we fetch after execute? delete statement MUST set to false
	  @return rs	: result set.
	*/
	public function execute ($q, $bindv = null, $fetch = true)
	{
		$this->_ps = $this->_dbo->prepare ($q);

		return $this->do_exec ($bindv, $fetch);
	}
//}}}
//{{{ db : prepare fields for where query
	public function prepare_fields ($fields, $sep = "and", $op = "=")
	{
		$s = "";

		foreach ($fields as $k => $v) {
			if ($k > 0) {
				$s .= " $sep ";
			}
			$s .= " $v $op ? ";
		}

		return $s;
	}
//}}}
//{{{ db : generate uniq id using timestamp + millisecond
	public function generate_id ()
	{
		return round (microtime (true) * 1000);
	}
//}}}
//{{{ db : prepare insert query
	public function prepare_insert ($table, $fields)
	{
		$qbind	= "";

		$nfield	= count ($fields);
		for ($i = 0; $i < $nfield; $i++) {
			if ($i > 0) {
				$qbind .= ",";
			}
			$qbind .= "?";
		}

		$q	=" insert into $table "
			." (". implode (",", $fields) .")"
			." values ( $qbind )";

		$this->_ps = $this->_dbo->prepare ($q);
	}
//}}}
//{{{ db : prepare update query
	public function prepare_update ($table, $fields, $ids)
	{
		$qupdate	=" update $table ";
		$qset		=" set ". $this->prepare_fields ($fields, ",");
		$qwhere		=" where ". $this->prepare_fields ($ids);

		$q	= $qupdate
			. $qset
			. $qwhere;

		$this->_ps = $this->_dbo->prepare ($q);
	}
//}}}
//{{{ db : prepare delete statement
	public function prepare_delete ($table, $fields)
	{
		$qdelete	=" delete from $table";
		$qwhere		=" where ". $this->prepare_fields ($fields);

		$this->_ps	= $this->_dbo->prepare ($qdelete . $qwhere);
	}
//}}}
//{{{ db : execute insert
	public function execute_insert ($table, $fields, $bindv = null, $fetch = false)
	{
		$this->prepare_insert ($table, $fields);

		return $this->do_exec ($bindv, $fetch);
	}
//}}}
//{{{ db : execute update
	public function execute_update ($table, $fields, $ids, $bindv = null, $fetch = false)
	{
		$this->prepare_update ($table, $fields, $ids);

		return $this->do_exec ($bindv, $fetch);
	}
//}}}

//{{{ db : execute delete
	public function execute_delete ($table, $fields, $bindv = null, $fetch = false)
	{
		$this->prepare_delete ($table, $fields);

		return $this->do_exec ($bindv, $fetch);
	}
//}}}
}
