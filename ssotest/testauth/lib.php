<?php

/**
createdb eassotest
CREATE TABLE sessions (
  session_id BIGSERIAL PRIMARY KEY,
  session_key TEXT UNIQUE NOT NULL,
  last_active TIMESTAMP DEFAULT now()
);
CREATE TABLE members (
  member_id NUMERIC NOT NULL,
  hash TEXT NOT NULL,
  salt TEXT NOT NULL
);
INSERT INTO members (member_id, hash, salt) VALUES ('1234', '7439600dde5facc4017df40294d7d426', 'b4249461104c2263ca62885b2b95b553');
INSERT INTO members (member_id, hash, salt) VALUES ('1235', 'fbf7a98d43828729135258c30f08885b', '7fb43bc9ca50db8536f4a2dd7a45a230');
*/
/*
$salt = md5(rand());
$hash = md5($salt."password");
var_dump($salt);
var_dump($hash);
*/

class db {
	function runq($query) {
		$conn = pg_connect("host=localhost dbname=eassotest user=user password=skoobar");
		$result = pg_query($conn, $query);
		$return = pg_fetch_all($result);
		pg_close($conn);
	
		return $return;
	}
}

class eassotestauth extends db {
	function login($member_id, $password) {
		$member = $this->runq("SELECT * FROM members WHERE member_id='".pg_escape_string($member_id)."' AND hash=md5(salt||'".pg_escape_string($password)."');");

		if (!empty($member[0]['member_id'])) {
			$session_key = md5(rand());
	
			$this->runq("INSERT INTO sessions (session_key) VALUES ('".pg_escape_string($session_key)."');");
	
			setcookie("session_key", $session_key, time()+3600, "/", ".eassotestauth.com", true, true);

			echo "<img src='https://eassotestone.com/setcookie.php?session_key=".urlencode($session_key)."' />";
	
			echo "login success";
			die;
		} else {
			echo "login failed";
			die;
		}
	}

	function authenticate($cookie_session_key) {
		$key_query = $this->runq("SELECT * FROM sessions WHERE session_key='".pg_escape_string($_COOKIE['session_key'])."';");
	
		if (!empty($key_query[0]['session_id'])) {
			$logged_in = true;
			$session_id = $key_query[0]['session_id'];
			$session_key = $key_query[0]['session_key'];
		} else {
			$logged_in = false;
			$session_id = null;
			$session_key = null;
		}

		return array($logged_in, $session_id, $session_key);
	}

	function logout($session_id, $session_key) {	
		$this->runq("DELETE FROM sessions WHERE session_id='".pg_escape_string($session_id)."';");
		setcookie("session_key", $session_key, time()+1, "/", ".eassotestauth.com", true, true);
		echo "logout success";
		die;
	}
}

?>