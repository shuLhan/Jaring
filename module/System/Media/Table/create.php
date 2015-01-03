<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
	// insert into media table
	$table	= "_media";
	$fields	= [ "id", "name", "extension", "size", "mime", "path" ];
	$fupath	= Jaring::$_media_dir . sha1_file ($_FILES["content"]["tmp_name"]);

	$pi = pathinfo ($_FILES["content"]["name"]);

	$bindv		= [];
	$bindv[]	= Jaring::$_db->generate_id ();
	$bindv[]	= $pi["filename"];
	$bindv[]	= $pi["extension"];
	$bindv[]	= $_FILES["content"]["size"];
	$bindv[]	= $_FILES["content"]["type"];
	$bindv[]	= $fupath;

	Jaring::execute_insert ($table, $fields, $bindv);

	move_uploaded_file ($_FILES["content"]["tmp_name"], APP_PATH ."/". $fupath);

	// link media id into table _media_table
	$id		= $bindv[0];

	$table	= "_media_table";
	$fields	= [ "table_id", "_media_id" ];
	$bindv	= [
				$_POST["table_id"]
			,	$id
			];

	Jaring::execute_insert ($table, $fields, $bindv);

	Jaring::$_out->set (true, self::MSG_SUCCESS_CREATE);
