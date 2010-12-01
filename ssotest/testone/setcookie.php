<?php

setcookie("session_key", $_GET['session_key'], time()+3600, "/", ".eassotestone.com", true, true);

header('Content-type: image/jpeg');

readfile("kitty.jpg");

?>