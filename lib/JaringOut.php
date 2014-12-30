<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/

class JaringOut
{
	public $success	= false;
	public $total		= 0;
	public $data		= "";

	function __construct ($success = false, $data = "", $total = 0)
	{
		$this->success	= $success;
		$this->total	= $total;
		$this->data		= $data;
	}

	function set ($success = false, $data = "", $total = 0)
	{
		$this->success		= $success;
		$this->total		= $total;
		$this->data			= $data;
	}
}
