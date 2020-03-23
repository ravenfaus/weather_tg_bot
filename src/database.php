<?php
/*
Class for working with SQLite3 database
Author: t.me/RavenFaus
*/
require_once('lib/user.php');

class database
{
	private $path;
	private $db;
	public function __construct($path)
	{
		$this->path = $path;
		$this->db = new SQLite3($path);
		$this->db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,
															is_bot INTEGER,
															is_admin INTEGER,
															first_name TEXT,
															last_name TEXT,
															username TEXT,
															lang TEXT,
															last_msg TEXT,
															last_msg_id INTEGER,
															coord TEXT)");
	}

	public function add_user($user)
	{
		$this->db->query("INSERT INTO users(id,is_bot,is_admin,first_name,last_name,username,lang,last_msg) VALUES(
						".$user->id.", ".
						($user->is_bot ? '1' : '0') .", ".
						($user->is_admin ? '1' : '0').", ".
						"'".$user->first_name."', ".
						"'".$user->last_name."', ".
						"'".$user->username."', ".
						"'".$user->lang."', ".
						"'".$user->last_msg."')");
	}

	public function get_user_by($param, $query)
	{
		$r = $this->db->query("SELECT * FROM users WHERE " . $param . " = '" . $query ."'")->fetchArray();
		$user = ['id'=> $r['id'], 'is_bot' => $r['is_bot'], 'first_name' => $r['first_name'], 'last_name' => $r['last_name'],
			'username' => $r['username'], 'language_code' => $r['lang'], 'last_msg' => $r['last_msg']];
		return new user($user);
	}

	public function set_coord($id, $coord)
	{
		$this->db->query("UPDATE users SET coord = '" . $coord . "' WHERE id = " . $id);
	}

	public function user_coord($id)
	{
		$r = $this->db->query("SELECT coord FROM users WHERE id = " . $id);
		if (empty($r))
			return '';
		else
			return $r->fetchArray()['coord'];
	}

	public function set_lang($id, $lang)
	{
		$this->db->query("UPDATE users SET lang = '" . $lang . "' WHERE id = " . $id);
	}

	public function user_lang($id)
	{
		return $this->db->query("SELECT lang FROM users WHERE id = " . $id)->fetchArray()['lang'];
	}

	public function set_last_msg_id($id, $msg_id)
	{
		$this->db->query("UPDATE users SET last_msg_id = '" . $msg_id . "' WHERE id = " . $id);
	}

	public function get_last_msg_id($id)
	{
		return $this->db->query("SELECT last_msg_id FROM users WHERE id = " . $id)->fetchArray()['last_msg_id'];
	}

	public function get_last_msg($id)
	{
		$last_msg = $this->db->query("SELECT last_msg FROM users WHERE id = " . $id);
		return $last_msg->fetchArray()['last_msg'];
	}

	public function set_last_msg($id, $msg)
	{
		$this->db->query("UPDATE users SET last_msg = '".$msg."' WHERE id = " . $id);
	}

	public function get_users()
	{
		return $this->db->query("SELECT * FROM users");
	}

	public function user_exists($id)
	{
		$stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
		$stmt->bindParam(':id', $id, SQLITE3_INTEGER);
		$is_exists = $stmt->execute();
		if (empty($is_exists->fetchArray()))
			return false;
		return true;
	}

	public function add_admin($id)
	{
		$this->db->query("INSERT INTO users(id, first_name, is_bot, is_admin, lang) VALUES (".$id.", 'Admin', 0, 1, 'en')");
	}

	public function user_admin($id)
	{
		$is_exists = $this->db->query("SELECT is_admin FROM users WHERE id = " . $id . " and is_admin = 1");
		if (empty($is_exists->fetchArray()))
			return false;
		else return true;
	}
}
?>
