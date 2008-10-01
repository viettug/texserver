<?php
	error_reporting(E_ALL);

	define('USER', $_SERVER['REMOTE_ADDR']);

	/********************************************************* system variables */

	$tex_stream = isset($_POST['tex_stream']) ? $_POST['tex_stream']: '';
	$tex_stream = trim($tex_stream);
	$jobname = USER . "-". time();
	$jobdir = dirname(__FILE__) . "/tmp/";
	$texdir = dirname(__FILE__) . "/texfiles/";

	/* captcha variables */
	/* captcha */

	$captcha = array(
		"tổng 1 + 4 = 5",
		"phần lẻ của 100 / 100 = 0",
		"phần nguyên của 10 / 100 = 0",
		"bất đẳng thức Cauchy áp dụng cho số = duong",
		"phần ảo của số phức 1 + 3i = 3",
		"số điện thoại cứa hỏa = 113",
		"số điện thoại cứu thương = 114",
		"thành phố Hồ Chí Minh có đài HTV7 và HTV = 9",
		"số chẵn theo sau số 99 = 100",
		"số lẻ theo sau số 99 = 101",
		"trên bản đồ, nước Việt Nam có hình chữ = a",
		""
	);

	/************************************************ captcha details from user */

	$captcha_id = isset($_POST['captcha_id']) ? $_POST['captcha_id']: '';
	$captcha_id = base64_decode($captcha_id);
	$captcha_id = base64_decode($captcha_id);
	$captcha_answer = isset($_POST['captcha_answer']) ? $_POST['captcha_answer']: '';
	$captcha_answer = trim($captcha_answer);
	$captcha_answer = strtolower($captcha_answer);

	srand(make_seed());
	$captcha_newid = rand(0, count($captcha) - 1);

	/****************************************************************** captcha */

	function captcha($op) {
		global $captcha_newid, $captcha, $captcha_answer, $captcha_id;
		$ret = '';
		if ($op == 'id') {
			$ret = base64_encode($captcha_newid);
			$ret = base64_encode($ret);
		}
		elseif ($op == 'text') {
			$ret = $captcha[$captcha_newid];
			$ret = explode('=', $ret);
			$ret = $ret[0];
			$ret = trim($ret);
		}
		elseif ($op == 'check') {
			if ($captcha_id < count($captcha)) {
				$ret = $captcha[$captcha_id];
				$ret = explode('=', $ret);
				$ret = $ret[1];
				$ret = ($captcha_answer == $ret);
			}
			else {
				$ret = FALSE;
			}
		}
		else {
			$ret = NULL;
		}
		return $ret;
	}

	/********************************************************* make random seed */

	function make_seed()
	{
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}

	/************************************** create the tex file from tex_stream */

	function make_tex_file() {
		global $tex_stream,$jobname,$jobdir,$texdir;

		$stream = "%% ip: ". USER . " time: $jobname %%\n$tex_stream";

		$tmpfname = tempnam($texdir, "$jobname-");
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $stream);
		fclose($handle);

		return $tmpfname;
	}

	/*************************************************** typeset the tex_stream */

	function typeset() {
		global $tex_stream, $jobname, $jobdir;

		if (!empty($tex_stream)) {
			if (captcha('check') != TRUE ) {
				$output = array('<font color="red">please pass the firewall :)</font>');
				$retval = 255;
			}
			else{
				$texfile = make_tex_file();

				exec("/usr/bin/latex -no-shell-escape--halt-on-error --output-directory=$jobdir --jobname=$jobname $texfile", $output, $retval);
			}
		}
		else {
			$output = array('please specify the tex stream');
			$retval = 255;
		}

		$output = implode("<br>\n", $output);
		$output = hilight_log($output);

		$retval = intval($retval);
		return array('output' => $output, 'retval' => $retval);
	}

	/****************************************************** higlight the output */

	function hilight_log($st) {
		global $jobdir, $texdir;
		/* hide something for security reason */
		$st = str_replace($jobdir, "<font color=\"blue\">JOBDIR/</font>", $st);
		$st = str_replace($texdir, "<font color=\"blue\">JOBDIR/</font>", $st);
		$st = str_replace('/usr/share/texmf-dist/', "<font color=\"blue\">TEXDIR/</font>", $st);
		$st = preg_replace("/(Output written on .*)/", "<font color=\"green\">\\1</font>", $st);
		$st = preg_replace("/(Transcript written on .*)/", "<font color=\"red\">\\1</font>", $st);
		return $st;
	}

	/************************************************************ typset it now */

	$typeset = typeset();
?>
<html>
<head>
	<title>TeX server</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<style>
		#tex_stream {
			width: 500px;
			height: 300px;
			display: block;
			border: 1px inset #fff;
			padding: 2px;
		}
		#tex_stream:focus {
			background-color: #f0f0f0;
		}
		#tex_stream:hover {
			background-color: #f3f3f3;
			color: #000;
		}

		input {
			display: inline;
			width: 100px;
		}
		input:hover {
			background-color: #66cc99;
		}

		#warn {
			width: 494px;
			border: 1px inset #fff;
			margin-bottom: 5px;
			padding: 2px;
			text-align: right;
			color: navy;
			background-color: #f0f0f0;
			font-size: 90%;
		}

		#warn strong {
			color: blue;
		}

		#warn:hover {
			color: #fff;
			background: #000;
		}

		#warn:hover strong {
			color: yellow;
		}

		#svnid {
			font-size: 90%;
			width: 500px;
			text-align: right;
			font-style: italic;
		}

		.clear__ {
			clear: both;
		}

		#rice {
			width: 500px;
			float: left;
			margin-right: 2px;
		}

		#log {
			width: 290px;
			height: 400px;
			float: left;
			border: 1px inset #fff;
			color: black;
			background-color: #f0f0f0;
			padding: 2px;
			font-size: 85%;
			font-family: monospace;
			overflow: scroll;
			white-space: nowrap;
		}

		#captcha {
			width: 100%;
			color: blue;
			font-size: 90%;
			text-align: right;
			text-style: italic;
		}

		#captcha input {
			background-color: #ccc;
		}
	</style>
</head>
<body>
	<div id="svnid">$Id$</div>
	<div id="warn">
		This is a personal TeX server.
		This is a demo/test server.<br>
		Use it for fun. <strong>Please don't hack</strong>.
		<br>
		Send your feedback to <strong>xkYanh</strong> at GmAil d0t c0m.
	</div>
	<div class="clear__"></div>
	<div id="rice">
		<form method="post" action="index.php" id="form_tex_stream">
			<textarea id="tex_stream" name="tex_stream"><?php	echo $tex_stream; ?></textarea>
			<div id="captcha">
				<span class="captcha_text"><?php print captcha('text'); ?></span>
				<input name="captcha_answer" type="text">
				<input name="captcha_id" value="<?php print captcha('id'); ?>" type="hidden">
			</div>
			<input type="reset" value="reset">
			<input type="submit" value="typset">
		</form>
	</div>
	<div id="log">
		<?php print $typeset['output']; ?>
	</div>
	<div class="clear__"></div>
</body>
