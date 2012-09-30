<?php
	
	class bot
	{
		//Public socket variable.
		var $socket;

		//Autoruns when new bot is called.
		function start()
		{
			global $config;
			$this->loadallModules();
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
				or die("Unable to create the socket.\n");
			$this->socketconnect = socket_connect($this->socket, $config['server'], $config['port'])
				or die("Could not connect to: ".$config['server'].":".$config['port']);

			$this->alive($this->socket);
		}

		//Sends nick/user info to the server, also joins channels.
		function ident()
		{
			global $config;
			$this->raw('USER', $config['nick'].' '.$config['nick'].' '.$config['nick'].' :'.$config['name']);
			$this->raw('NICK', $config['nick']);
			$this->raw('MODE', $config['nick']." +B", "no");
			if(isset($config['nspass'])) { $this->msg($config['ns']['nick'], $config['ns']['pass']) }
			$this->join($config['channels']);
		}

		//Raw data function, sends data to server. $show makes it verbose.
		function raw($command, $message = null, $show = "yes")
		{
			if($message == null)
			{
				socket_write($this->socket, $command."\r\n");
				if($show == "yes") { echo($command."\n"); }
			}
			else
			{
				socket_write($this->socket, $command.' '.$message."\r\n");
				if($show == "yes") { echo($command.' '.$message."\n"); }
			}
		}

		//Does nothing atm.. well, it does give a timestamp.. but nothing uses it!
		function output($data)
		{
			echo "[".date("h:i:s")."]";
		}

		//Admin function, checks to see if user is set as an admin,
		//and their admin level. Default level is 0, unless set in config file.
		function auth($target, $hostname, $level="0")
		{
			global $config;
			if(isset($config['admins'][$target]) && $config['admins'][$target]['host'] == $hostname)
			{
				if($config['admins'][$target]['level'] >= $level) { return 1; }
				else { return 0; }
			}
			else { return 0; }
		}

		//Sends a message to a channel, or person.
		function msg($target, $message)
		{
			socket_write($this->socket, "PRIVMSG ".$target." :".$message."\n\r");
			if($this->isChan($target)) { echo "<".$config['nick'].">".$message."\n"; }
			else { echo "<".$config['nick']."> to <".$target."> ".$message."\n"; }
		}

		//Checks for a # to see if it's a channel or not.
		function isChan($target)
		{
			if(strstr($target, "#")) { return true; }
			else { return false; }
		}

		//Placeholder, not sure if I'll need it once I flesh out the modules system.
		function isCommand($data)
		{

		}

		//Keepalive function, the while loop where the bot actually LIVES.
		function alive($socket)
		{
			while($output = socket_read($this->socket, 4096))
			{
				if(strstr($output, "NOTICE AUTH :*** Found your hostname")) { $this->ident(); }
				print($output);
				$this->parse($output);
			}
		}

		//Parse the raw socket data into shit we can manipulate. Make it all perdy like.
		function parse($data)
		{
			global $config;

			if($data[0] == ":") { $this->data = ltrim($data, ":"); }
			else { $this->data = $data; }

			$this->data = rtrim($this->data. "\n\r");
			$this->output = explode(" ", $this->data);
			$this->sender = array_shift($this->output);
			$this->messagetype = array_shift($this->output);
			$this->channel = array_shift($this->output);
			$this->arguements = array_shift($this->output);
			$this->arguements = substr($this->arguements, 1);
			$this->arguementsplit = explode(".", $this->arguements);
			$this->command = array_shift($this->arguementsplit);
			$this->user = explode("@", $this->sender);
			$this->usernick = explode("!", $this->user[0]);

			$parsed['type'] = $this->messagetype; //PRIVMSG, NOTICE
			$parsed['channel'] = $this->channel; //Channel sent from
			$parsed['user']['nick'] = $this->usernick[0];
			$parsed['user']['name'] = substr($this->usernick[1], 1);
			$parsed['user']['host'] = $this->user[1];
			$parsed['command'] = substr($this->command, 1);
			$parsed['prefixcommand'] = $config['prefix']."".$parsed['command'];
			$parsed['arguements'] = $this->arguementsplit;
			$parsed['text'] = implode($this->output, " ");

			if($parsed['text'] == "!!" && isset($global['chkchk'])) { $parsed['text'] = $global['chkchk']; }
			elseif($parsed['text'] != "!!" && isCommand($parsed['command']))
			if($parsed['user']['nick'] == "PING") { $this->raw('PONG', $parsed['type']); }

			$this->process($parsed);
			}

		//Commands go here.
		function process($data)
		{
			global $config;

			switch($data['command'])
			{
				case "say":
					$this->command['msg'] = $data['text'];
					$this->command['auth'] = "2";
					$this->command['target'] = $data['channel'];
					$this->command['exec'] = "msg";
					$this->command['help'] = $config['prefix']."say <text>";
					break;
				case "auth":
					//shit here
				case "join":
					//shit here too
				case "part":
					//more shit here
			}

			if(isset($this->command['msg']))
			{
				if(isset($this->command['auth']) && $this->auth($data['nick'], $data['host'], $this->command['auth']))
				{
					if(isset($this->command['target'])) { $this->command['exec']($this->command['target'], $this->command['msg']); }
				}
			}
		}

		//Joins a channel, or multiple channels, if more than one is set.
		function join($channel, $show = "yes")
		{
			if(is_array($channel))
			{
				foreach($channel as $chan)
				{
					$this->raw('JOIN', $chan, $show);
				}
			}
			else
			{
				$this->raw('JOIN', $channel);
			}
		}

		//Parts a channel. Also uses a part message if given.
		function part($channel, $message, $show = "yes")
		{
			if(isset($msg)) { $this->raw('PART', $channel.' :'.$message, $show); }
			else { $this->raw('PART', $channel, $show); }
		}

		//Loads modules.
		function loadallModules()
		{
			global $modules;
			require('modules/list.php');
			$modules = array_slice(scandir('modules'), 2);
			foreach($modules as $module)
			{
				loadModule($module);
			}
			
		}

		function isCommand($data)
		{
			global $modules;

		}

		function unloadModule($data)
		{
			global $modules;
		}

		function loadModule($data)
		{
			global $modules;
			$module = file_get_contents($data);

		}

		//This is placeholder for another file, which I'm too lazy to make atm. ##users.php##
		// $module['users']['auth'] = "0";
		// $module['users']['description'] = "Stores and retrieves userdata (lastfm account, steam info, etc).";
		// $module['users']['help'] = $config['prefix']."user.set.help for more info!";
		// $module['users']['arguments'] = array("set", "add", "remove" "help");

		function isUser($target)
		{
			$userlist = array_slice(scandir('data/users'), 2);

			foreach($userlist as $user)
			{
				if($user == $target) { return true; }
			}

			return false;
		}

		function newUser($target, $hostname, $level="0")
		{




			file_put_contents("data/users/".$target.".php", $userdata);
		}
	}
?>
