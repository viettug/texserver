<?php
	error_reporting(E_ALL);

	$tex_stream = isset($_POST['tex_stream']) ? $_POST['tex_stream']: '';
	$tex_stream = trim($tex_stream);
	$jobname = time();
	$jobdir = dirname(__FILE__) . "/tmp/";
	$texdir = dirname(__FILE__) . "/texfiles/";

	/* create the tex file from tex_stream */
	function make_tex_file() {
		global $tex_stream,$jobname,$jobdir,$texdir;

		$ip = $_SERVER['REMOTE_ADDR'];
		$stream = "%% ip: $ip time: $jobname %%\n$tex_stream";

		$tmpfname = tempnam($texdir, "$jobname-");
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $stream);
		fclose($handle);

		return $tmpfname;
	}

	/* typeset the tex_stream */
	function typeset() {
		global $tex_stream, $jobname, $jobdir;

		if (!empty($tex_stream)) {
			$texfile = make_tex_file();

			exec("/usr/bin/latex -no-shell-escape--halt-on-error --output-directory=$jobdir --jobname=$jobname $texfile", $output, $retval);
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

	/* higlight the output */
	function hilight_log($st) {
		global $jobdir, $texdir;
		/* hide something for security reason */
		$st = str_replace($jobdir, "<font color=\"blue\">JOBDIR::</font>", $st);
		$st = str_replace($texdir, "<font color=\"blue\">JOBDIR::</font>", $st);
		$st = preg_replace("/(Output written on .*)/", "<font color=\"#66ccdd\">\\1</font>", $st);
		$st = preg_replace("/(Transcript written on .*)/", "<font color=\"red\">\\1</font>", $st);
		return $st;
	}

	/* typset it now */
	$typeset = typeset();
?>
<html>
<head>
	<title>TeX server</title>
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
			margin-right: 1em;
		}
		#log {
			width: 300px;
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
		<input type="reset" value="reset">
		<input type="submit" value="typset">
		</form>
	</div>
	<div id="log">
		<?php print $typeset['output']; ?>
	</div>
	<div class="clear__"></div>
</body>
