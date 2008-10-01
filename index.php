<?php
	error_reporting(E_ALL);

	$tex_stream = isset($_POST['tex_stream']) ? $_POST['tex_stream']: '';
	$tex_stream = trim($tex_stream);
	$jobname = time();
	$jobdir = dirname(__FILE__) . "/tmp/";

	function typeset() {
		global $tex_stream, $jobname, $jobdir;
		exec("echo $tex_stream | /usr/bin/latex -no-shell-escape--halt-on-error --output-directory=$jobdir --jobname=$jobname", $output, $retval);
		return array('output' => $output, 'retval' => intval($retval));
	}

	if (!empty($tex_stream)) $typeset = typeset();
?>
<html>
<head>
	<title>TeX server</title>
	<style>
		#tex_stream {
			width: 600px;
			height: 400px;
			display: block;
		}
		#tex_stream:focus {
			background-color: #cccc66;
		}
		input {
			display: inline;
			width: 100px;
		}
		input:hover {
			background-color: #66cc99;
		}

		#warn {
			width: 592px;
			color: #fff;
			background: #000;
			border: 1px outset #fff;
			margin-bottom: 5px;
			padding: 2px;
			text-align: center;
		}

		#warn strong {
			color: yellow;
		}

		.clear__ {
			clear: both;
		}
	</style>
</head>
<body>
	<div id="warn">
			This is a personal TeX server.
			This is a demo/testing server.<br>
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
		<?php print $jobname; ?>
	</div>
	<div class="clear__"></div>
</body>
