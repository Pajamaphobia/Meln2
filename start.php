<?php
if (PHP_SAPI !== 'cli') { die("To be ran from console only, you make me sad."); }

date_default_timezone_set('America/Detroit'); //Where you live at, foo
set_time_limit(0); //Run that shit FOREVERRRRR (fo eva eva, fo eva eva).
error_reporting(E_ALL); //Makes sure php reports all errors.
chdir(__DIR__); //Changes dir to where the bot's files reside, in case of execution from a different directory.

$config = array
(
	'starttime' => strtotime("now"), //When the bot started (used for uptime.)
	'server' => "irc.server.tld", //What network to connect to.
	'port' => 6667, //What port to connect to.
	'channels' => array("#channel"), //Which channels to join
	'nick' => "meln", //Bot's nick
	'name' => "melnbot", //Bot's username
	'admins' => array("nick" => array('host' => "your.visible.hostname", 'level' => "5")), //Admins
	'prefix' => ";", //Prefix bot looks for in commands; eg. :help.
	'ns' => array("nick" => "nickserv", "pass" => "botnickservpasswordhere"), //Nickserv password.
	'api' => array("google" => "", "ud" => "", "lastfm" => ""), //3rd party api keys.
	'debug' => false //Turns on/off debug mode. Turning on outputs values of key variables.
);

require('bot.php'); //Include the rest of the bot.

$bot = new bot();
$bot->start($config);

?>