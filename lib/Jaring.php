<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/

include "JaringOut.php";
include "JaringDB.php";

class Jaring
{
//{{{ var : constanta
	const MSG_SUCCESS_UPDATE	= "Data has been updated.";
	const MSG_SUCCESS_CREATE	= "New data has been created.";
	const MSG_SUCCESS_DESTROY	= "Data has been deleted.";
	const MSG_ACCESS_FAIL		= "You don't have sufficient privilege.";
	const MSG_REQUEST_INVALID	= "Invalid request ";
	const MSG_DATA_LOCK			= "This data has been locked, it can not be deleted.";
	const MSG_ADMIN_PROFILE		= "This user is administrator of profile and can not be deleted.";
	const MOD_INIT				= "/init";

	const ACCESS_NO		= 0;
	const ACCESS_READ	= 1;
	const ACCESS_CREATE	= 2;
	const ACCESS_UPDATE	= 3;
	const ACCESS_DELETE	= 4;
//}}}
//{{{ var : static
	public static $_ext				= ".php";
	public static $_title			= "Jaring Framework";
	public static $_name			= "jaring";
	public static $_path			= "/";
	public static $_path_mod		= "module";
	public static $_mod_init		= "";
	public static $_mod_home		= "/module/home/";
	public static $_mod_main		= "/module/main/";
	public static $_content_type	= 0;
	public static $_menu_mode		= 1;
	public static $_paging_size		= 50;
	public static $_media_dir		= "media";
	public static $_db				= null;
	public static $_out				= null;

	//	Module configuration. Set by each modules index.
	public static $_mod	= [
							"db_table"	=> [
								"name"			=> ""
							,	"profiled"		=> true
							,	"profile_id"	=> "_profile_id"
							,	"id"			=> ["id"]
							,	"generate_id"	=> "id"
							,	"read"			=> []
							,	"search"		=> []
							,	"order"			=> []
							,	"create"		=> []
							,	"update"		=> []
							]
						,	"db_rel"	=> [
								"tables"		=> []
							,	"conditions"	=> []
							,	"read"			=> []
							,	"search"		=> []
							,	"order"			=> []
							]
						];

	//
	//	Cookies values.
	//	Variables that will be instantiated when calling cookies_get.
	//
	public static $_c_uid			= 0;
	public static $_c_username		= "Anonymous";
	public static $_c_profile_id	= 0;
//}}}

	public function __construct ()
	{}

//{{{ f : implode with additional string for prefix and suffix of each array item.
	public static function implode_with_circumfix ($sep, $array, $prefix, $suffix)
	{
		$r = "";

		foreach ($array as $k => $v) {
			if ($k > 0) {
				$r .= $sep;
			}
			$r .= $prefix . $v . $suffix;
		}

		return $r;
	}
//}}}
//{{{ cookie : get value
	public static function cookies_get ()
	{
		$ckey = "user_id";
		if (isset ($_COOKIE[$ckey])) {
			self::$_c_uid = $_COOKIE[$ckey];
		}

		$ckey = "user_name";
		if (isset ($_COOKIE[$ckey])) {
			self::$_c_username = $_COOKIE[$ckey];
		}

		$ckey = "profile_id";
		if (isset ($_COOKIE[$ckey])) {
			self::$_c_profile_id = $_COOKIE[$ckey];
		}
	}
//}}}
//{{{ cookie : check if user has cookie.
	public static function cookies_check ()
	{
		if (0 === self::$_c_uid) {
			header ("Location:". self::$_mod_home);
			exit ();
		}
	}
//}}}
//{{{ init
	public static function init ()
	{
		try {
			$f_app_conf	= APP_PATH ."/app.conf";

			if (!file_exists($f_app_conf)) {
				$f_app_conf = APP_PATH . "/app.default.conf";
			}

			$app_conf = parse_ini_file ($f_app_conf);

			self::$_title			= $app_conf["app.title"];
			self::$_name			= $app_conf["app.name"];
			self::$_ext				= $app_conf["app.extension"];
			self::$_path			= $app_conf["app.path"];
			self::$_path_mod		= $app_conf["app.module.dir"];
			self::$_mod_init		= self::$_path . self::$_path_mod . self::MOD_INIT . self::$_ext;
			self::$_mod_home		= self::$_path . self::$_path_mod . "/home/";
			self::$_mod_main		= self::$_path . self::$_path_mod . "/main/";
			self::$_content_type	= $app_conf["app.content.type"];
			self::$_menu_mode		= $app_conf["app.menu.mode"];
			self::$_paging_size		= $app_conf["app.paging.size"];
			self::$_media_dir		= "/". $app_conf["app.media.dir"] ."/";

			self::$_db = new JaringDB ($app_conf["db.url"]
										, $app_conf["db.username"]
										, $app_conf["db.password"]
										, $app_conf["db.pool.min"]
										, $app_conf["db.pool.max"]
									);
			self::$_out = new JaringOut ();

			self::cookies_get ();
		} catch (Exception $e) {
			error_log ($e);
		}
	}
//}}}

//{{{ db : generate ID for each data
	public static function db_prepare_id (&$data)
	{
		$fprofid = self::$_mod["db_table"]["profile_id"];

		if (self::$_mod["db_table"]["profiled"]) {
			foreach ($data as &$d) {
				$d[$fprofid] = self::$_c_profile_id;
			}
		}

		if (! isset (self::$_mod["db_table"]["generate_id"])) {
			return;
		}

		$id = self::$_mod["db_table"]["generate_id"];

		if (null !== $id) {
			foreach ($data as &$d) {
				if (empty ($d[$id])) {
					$d[$id] = self::$_db->generate_id ();
				}
			}
		}
	}
//}}}
//{{{ crud -> db : check system profile id, throw exception if id = 1.
	public static function request_check_system_profile ($data)
	{
		$fprofid = self::$_mod["db_table"]["profile_id"];

		// Disallow user to delete data where profile id = 1.
		if (self::$_mod["db_table"]["profiled"]) {
			foreach ($data as $d) {
				if ($d[$fprofid] === 1 || $d[$fprofid] === "1") {
					throw new Exception (self::MSG_DATA_LOCK);
				}
			}
		}
	}
//}}}
//{{{ crud -> db : request read : populate relationship.
	public static function request_read_populate_relationship (&$qselect, &$qfrom
															, &$qwhere
															, &$qorder
															, $query)
	{
		// populate relationship.
		if (count (self::$_mod["db_rel"]["tables"]) <= 0) {
			return;
		}

		// generate relationship: tables.
		$qfrom .= "," . implode (",", self::$_mod["db_rel"]["tables"]);

		// generate relationship: read fields.
		$a = self::$_mod["db_rel"]["read"];

		if (count ($a) > 0) {
			$qselect .= "," . implode (",", $a);
		}

		// generate relationship: where conditions.
		foreach (self::$_mod["db_rel"]["conditions"] as $k => $v) {
			if ($v !== "") {
				$qwhere .= " and ". $v;
			}
		}

		// generate relationship: where search.
		$a = self::$_mod["db_rel"]["search"];
		if (count ($a) > 0) {
			$qwhere .=" and (";
			$qwhere .= self::implode_with_circumfix (" or ", $a, "", " like $query ");
			$qwhere .=")";
		}

		// generate relationship: order by
		$a = self::$_mod["db_rel"]["order"];
		if (count ($a) > 0) {
			if (strlen ($qorder) <= 0) {
				$qorder = " order by ";
			} else {
				$qorder .= ",";
			}

			$qorder .= implode (",", $a);
		}
	}
//}}}
//{{{ crud -> db : handle read request
	public static function request_read ()
	{
		$query		= "'%".$_GET["query"]."%'";
		$start		= (int) $_GET["start"];
		$limit		= (int) $_GET["limit"];
		$tname		= self::$_mod["db_table"]["name"];
		$freads		= self::$_mod["db_table"]["read"];
		$fsearch	= self::$_mod["db_table"]["search"];
		$forder		= self::$_mod["db_table"]["order"];

		$fprofid	= self::$_mod["db_table"]["profile_id"];
		$qselect	= " select ";
		$qfrom		= " from ". $tname;
		$qwhere		= " where 1=1 ";
		$qorder		= "";
		$qlimit		= " limit ". $start .",". $limit;

		// if table is profiled, then filter by profile id
		if (self::$_mod["db_table"]["profiled"]
		&&  self::$_c_profile_id !== "1") {
			$qwhere	.= " and $tname.$fprofid = ". self::$_c_profile_id;
		}

		// get parameter name that has the same name with read fields,
		// and use it as the filter
		foreach ($freads as $v) {
			if (! array_key_exists ($v, $_GET)) {
				continue;
			}

			$qwhere .=" and $tname.$v = ";

			if (is_numeric ($_GET[$v])) {
				$qwhere .= $_GET[$v];
			} else {
				$qwhere .= "'". $_GET[$v] ."'";
			}
		}

		// generate select.
		$qselect .= self::implode_with_circumfix (",", $freads, $tname.".", "");

		// generate where: add filter by search field.
		if (count ($fsearch) > 0) {
			$qwhere .=" and (";
			$qwhere .= self::implode_with_circumfix (" or ", $fsearch
							, $tname."."
							, " like $query");
			$qwhere .= ")";
		}

		// generate order by.
		if (count ($forder) > 0) {
			$qorder	= " order by ";
			$qorder .= self::implode_with_circumfix (",", $forder, $tname.".", "");
		}

		self::request_read_populate_relationship ($qselect, $qfrom, $qwhere, $qorder, $query);

		// Get total rows
		$qtotal	=" select COUNT($tname." . self::$_mod["db_table"]["id"][0] .") as total "
				. $qfrom
				. $qwhere;

		$rs = self::$_db->execute ($qtotal);

		if (count ($rs) <= 0) {
			$t = 0;
		} else {
			$t = (int) $rs[0]["total"];
		}

		// Get data
		$qread	= $qselect . $qfrom . $qwhere . $qorder . $qlimit;

		self::$_out->set (true, self::$_db->execute ($qread), $t);

		if (function_exists ("request_read_after")) {
			request_read_after (self::$_out->data);
		}
	}
//}}}
//{{{ crud -> db : handle create request
	public static function request_create ($data)
	{
		$table	= self::$_mod["db_table"]["name"];
		$fields	= self::$_mod["db_table"]["create"];

		if (function_exists ("request_create_before")) {
			$s = request_create_before ($data);

			if ($s === false) {
				return;
			}
		}

		self::$_db->prepare_insert ($table, $fields);
		self::db_prepare_id ($data);

		foreach ($data as $d) {
			$bindv = [];

			foreach (self::$_mod["db_table"]["create"] as $field) {
				if (array_key_exists ($field, $d)) {
					array_push ($bindv, $d[$field]);
				}
			}

			self::$_db->_ps->execute ($bindv);
			self::$_db->_ps->closeCursor ();

			unset ($bindv);
		}

		if (function_exists ("request_create_after")) {
			$s = request_create_after ($data);

			if ($s === false) {
				return;
			}
		}

		self::$_out->set (true, self::MSG_SUCCESS_CREATE);
	}
//}}}
//{{{ crud -> db : handle update request
	public static function request_update ($data)
	{
		$table	= self::$_mod["db_table"]["name"];
		$fields	= self::$_mod["db_table"]["update"];
		$ids	= self::$_mod["db_table"]["id"];

		if (function_exists ("request_update_before")) {
			$s = request_update_before ($data);

			if ($s === false) {
				return;
			}
		}

		self::$_db->prepare_update ($table, $fields, $ids);

		foreach ($data as $d) {
			$bindv = [];

			foreach ($fields as $field) {
				if (array_key_exists ($field, $d)) {
					array_push ($bindv, $d[$field]);
				}
			}
			foreach ($ids as $field) {
				array_push ($bindv, $d[$field]);
			}

			self::$_db->_ps->execute ($bindv);
			self::$_db->_ps->closeCursor ();

			unset ($bindv);
		}

		if (function_exists ("request_update_after")) {
			$s = request_update_after ($data);

			if ($s === false) {
				return;
			}
		}

		self::$_out->set (true, self::MSG_SUCCESS_UPDATE);
	}
//}}}
//{{{ crud -> db : handle delete request
	public static function request_delete ($data)
	{
		if (function_exists ("request_delete_before")) {
			$s = request_delete_before ($data);

			if ($s === false) {
				return;
			}
		} else {
			self::request_check_system_profile ($data);
		}

		self::$_db->prepare_delete (self::$_mod["db_table"]["name"]
								,self::$_mod["db_table"]["id"]
								);

		foreach ($data as $d) {
			$bindv = [];

			foreach (self::$_mod["db_table"]["id"] as $field) {
				array_push ($bindv, $d[$field]);
			}

			self::$_db->_ps->execute ($bindv);
			self::$_db->_ps->closeCursor ();

			unset ($bindv);
		}

		if (function_exists ("request_delete_after")) {
			$s = request_delete_after ($data);

			if ($s === false) {
				return;
			}
		}

		self::$_out->set (true, self::MSG_SUCCESS_DESTROY);
	}
//}}}

//{{{ crud : check file upload error code.
	// return true on upload ok.
	// return false on error.
	public static function request_upload_check_err ($f)
	{
		switch ($_FILES[$f]["error"]) {
		case UPLOAD_ERR_OK:
			return true;
		case UPLOAD_ERR_INI_SIZE:
			$msg = "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$msg = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
			break;
		case UPLOAD_ERR_PARTIAL:
			$msg = "The uploaded file was only partially uploaded.";
			break;
		case UPLOAD_ERR_NO_FILE:
			$msg = "No file was uploaded.";
			return false;
		case UPLOAD_ERR_NO_TMP_DIR:
			$msg = "Missing a temporary folder.";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$msg = "Failed to write file to disk.";
			break;
		case UPLOAD_ERR_EXTENSION:
			$msg = "A PHP extension stopped the file upload.";
			break;
		}

		self::$_out->data = $msg;
		return false;
	}
//}}}
//{{{ crud : get module name based on request URI.
	public static function get_module_name ($uri)
	{
		return trim (
					str_replace (
						"/"
					,	"_"
					,	substr (
							strstr (
								$uri
							,	self::$_path_mod
							)
						,	strlen(self::$_path_mod)
						)
					)
				,	"_"
				);
	}
//}}}
//{{{ crud : get access code based on HTTP method
	public static function request_get_crud_access ($method)
	{
		switch ($method) {
		case "GET":
			return self::ACCESS_READ;
		case "POST":
			return self::ACCESS_CREATE;
		case "PUT":
			return self::ACCESS_UPDATE;
		case "DELETE":
			return self::ACCESS_DELETE;
		default:
			throw new Exception (self::MSG_REQUEST_INVALID . $method);
		}
	}
//}}}
//{{{ crud : get access code based on GET/POST parameter.
	public static function request_get_action_access ($action)
	{
		switch ($action) {
		case "read":
			return self::ACCESS_READ;
		case "create":
			return self::ACCESS_CREATE;
		case "update":
			return self::ACCESS_UPDATE;
		case "destroy":
			return self::ACCESS_DELETE;
		default:
			throw new Exception (self::MSG_REQUEST_INVALID
								. $action);
		}
	}
//}}}
//{{{ crud : get user access.
	public static function request_get_access ($mode)
	{
		$access	= self::ACCESS_NO;

		if ("crud" === $mode) {
			$access = self::request_get_crud_access($_SERVER["REQUEST_METHOD"]);
		} else {
			$action	= "read";

			if ("GET" === $_SERVER["REQUEST_METHOD"]) {
				$action = $_GET["action"];
			} else {
				$action = $_POST["action"];
			}

			$access = self::request_get_action_access ($action);
		}

		return $access;
	}
//}}}
//{{{ crud : check user access to module
	public static function check_user_access ($mod, $uid, $access)
	{
		$q	="
				select	GM.permission
				from	_user			U
				,		_group			G
				,		_user_group		UG
				,		_menu			M
				,		_group_menu		GM
				where	GM._group_id	= G.id
				and		GM._menu_id		= M.id
				and		UG._group_id	= G.id
				and		UG._user_id		= U.id
				and		M.module		= '". $mod ."'
				and		U.id			= ". $uid ."
				order by GM.permission desc
				limit	0,1
			";

		$rs = self::$_db->execute ($q);

		if (count ($rs) <= 0) {
			return false;
		}
		if (((int) $rs[0]["permission"]) >= $access) {
			return true;
		}

		return false;
	}
//}}}
//{{{ crud : route request.
	public static function request_switch ($path, $access, $data)
	{
		switch ($access) {
		case self::ACCESS_READ:
			$path .= "read.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_read ();
			}
			break;

		case self::ACCESS_CREATE:
			$path .= "create.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_create ($data);
			}
			break;

		case self::ACCESS_UPDATE:
			$path .= "update.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_update ($data);
			}
			break;

		case self::ACCESS_DELETE:
			$path .= "delete.php";
			if (file_exists ($path)) {
				require_once $path;
			} else {
				self::request_delete ($data);
			}
			break;
		}
	}
//}}}
//{{{ crud : main.
	public static function request_handle ($mode = "crud")
	{
		$i		= 1;
		$q		= "";
		$t		= 0;

		$fprofid	= self::$_mod["db_table"]["profile_id"];
		$uri	= explode ("?", $_SERVER["REQUEST_URI"])[0];
		$path	= APP_PATH.$uri;
		$module	= self::get_module_name ($uri);

		try {
			self::$_db->init ();

			$access	= self::request_get_access ($mode);

			$s		= self::check_user_access ($module, self::$_c_uid, $access);
			if (false === $s) {
				throw new Exception (self::MSG_ACCESS_FAIL);
			}

			if ("crud" === $mode) {
				$data = json_decode (file_get_contents("php://input"), true);
			} else {
				$data = $_POST;
			}

			// Convert json object to array
			if (null !== $data && ! is_array (current ($data))) {
				$data = array($data);
			}

			// push _profile_id to field ids.
			if (self::$_mod["db_table"]["profiled"]) {
				self::$_mod["db_table"]["id"][] = $fprofid;
			}

			self::$_db->_dbo->beginTransaction ();
			self::request_switch ($path, $access, $data);
			self::$_db->_dbo->commit ();
		} catch (Exception $e) {
			self::$_out->data = addslashes ($e->getMessage ());
		}

		header("Content-Type: application/json");
		echo json_encode (self::$_out, JSON_NUMERIC_CHECK);
	}
//}}}
}
