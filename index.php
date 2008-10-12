<?php
/**
 * $Id$
 *
 * This small php script generates a Web interface to tex server.
 *
 * Author: kyanh <xkyanh@gmail.com>
 * License: GPL
 *
 * Features:
 * - generate pdf output from tex file. pdf link is downloadable.
 * - light spam protection
 *
 * Demo:
 * - http://kyanh.zapto.org:1006 (server maybe in downtime)
 *
 * Requirement and Installation:
 * - tex system (executable by the world)
 * - apache web server (lighttpd is possible with some hacks. see .htaccess)
 * - php support
 * - a directory contains
 *	- ./tmp/ : directory for output and log files. Permission: 777
 *	- ./texfiles/ : directory for user tex files. Permission: 777
 * - advance users:
 *	- custom latex options
 *	- add some captcha questions (english only :P)
 *	- rewrite this script. you are welcome :P
 *
 * Note:
 * - use this program at your risk. I provide no-warranty. I have no duties
 * - incase this script harms your system. Again, USE AT YOUR RISK!
 *
 * Story:
 * - Similar interface was written in 2007 and published. Now I cannot find
 * - where it is. It may be destroyed when I am moving files from PC to PC.
 * - I wrote this script last night (Oct 1st, 2008). During my coding there were
 * - some eletric cuts. Now it goes well. Thanks to God :P
 */

	umask(022);

	session_start();

	error_reporting($_SERVER['REMOTE_ADDR'] == '192.168.1.9' ? E_ALL : 0);

	define('USER', $_SERVER['REMOTE_ADDR']);
	define('SUFFER', 4); /* minimum distance between two accesses */

	/********************************************************* system variables */

	$tex_stream_default =
'\documentclass[12pt,a4paper,oneside]{article}

\usepackage{
  geometry,
  amsmath,
  ntheorem
}

% \usepackage[utf8x]{vietnam}

\geometry{a4paper,pdftex,left=3cm,right=4cm}

\theoremstyle{plain}
\theoremheaderfont{\bfseries}
\theorembodyfont{\itshape}
\theoremseparator{.}
\newtheorem{mytheo}{My theorem}

\begin{document}

% you must type something here...

\end{document}
';

	$tex_stream = isset($_POST['tex_stream']) ? $_POST['tex_stream']: '';
	$tex_stream = trim($tex_stream);
	$tex_stream_default = trim($tex_stream_default);

	/* access time */
	$access = time();
	$jobname = USER."-$access";
	$jobdir = dirname(__FILE__) . "/tmp"; /* physical path */
	$jobdir_web = "./tmp"; /* web path */
	$texdir = dirname(__FILE__) . "/texfiles";

	/* captcha variables */
	/* captcha */

	$captcha = array(
		"tổng 1 + 4 = 5",
		"phần nguyên của số Pi = 3",
		"phần nguyên của 10 / 100 = 0",
		"bất đẳng thức Cauchy áp dụng cho số = duong",
		"phần ảo của số phức 1 + 3i = 3",
		"số điện thoại cứa hỏa = 114",
		"số điện thoại cứu thương = 115",
		"số điện thoại công an cứu nạn = 113",
		"thành phố Hồ Chí Minh có đài HTV7 và HTV = 9",
		"số chẵn theo sau số 99 = 100",
		"trên bản đồ, nước Việt Nam có hình chữ = S",
		"dịch qua tiếng Anh từ 'bão tố' = storm",
		"dịch qua tiếng Anh từ 'gà con' = chicken",
		"dịch qua tiếng Anh từ 'tình yêu' = love",
		"ngày giải phóng miền Nam là 30/4/ = 1975",
		"số tự nhiên có một chữ số lớn nhất là = 9",
		"số lẻ theo sau số 99 = 101"
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

	/******************************************************************* suffer */

	function suffer() {
		global $access, $captcha_id;

		$last_access = (isset($_SESSION['access'])) ? $_SESSION['access'] : 0;
		$last_access = intval($last_access);
		$captcha_id_last = isset($_SESSION['captcha_id_last']) ? $_SESSION['captcha_id_last'] : -1;
		$ret = TRUE;

		if ( ($access - $last_access < SUFFER) or ($captcha_id == $captcha_id_last) )
			$ret = FALSE;

		$_SESSION['access'] = $access;
		$_SESSION['captcha_id_last'] = $captcha_id;

		return $ret;
	}

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
			// $ret = "$ret <font color=\"red\">???</font>";
		}
		elseif ($op == 'check') {
			if ($captcha_id < count($captcha)) {
				$ret = $captcha[$captcha_id];
				$ret = explode('=', $ret);
				$ret = trim($ret[1]);
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
		global $tex_stream, $jobname, $jobdir, $jobdir_web, $tex_stream_default;
		global $access, $last_access;

		if (!suffer()) {
			$output = array('be patient');
			$retval = 255;
		}
		elseif (empty($tex_stream)) {
			$output = array('please specify the tex stream');
			$retval = 255;
		}
		else {
			if (captcha('check') != TRUE ) {
				$output = array('<font color="red">please pass the firewall :)</font>');
				$retval = 255;
			}
			else{
				$texfile = make_tex_file();
				$options = array(
					"-no-shell-escape",
					"-halt-on-error",
					"-output-directory=$jobdir",
					"-jobname=$jobname",
					"-output-format=pdf"
				);

				$option = implode(" ", $options);
				exec("/usr/bin/latex $option $texfile", $output, $retval);

				if ($retval == 0) {
					$out = array(
						"Output: <a href=\"$jobdir_web/$jobname.pdf\">$jobname.pdf</a>",
						"","");
					$out += $output;
					$output = $out;
				}
			}
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
		$st = str_replace('/usr/share/texmf', "<font color=\"blue\">TEXDIR/</font>", $st);
		$st = str_replace('/home/users/kyanh', "<font color=\"blue\">TEXDIR/</font>", $st);

		$st = preg_replace("/\/([^.\/]+\.sty)/", "/<font color=\"green\">\\1</font>", $st);

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
	<link type="text/css" rel="stylesheet" href="style.css">
	<link type="text/css" rel="stylesheet" href="sh_style.css">
	<script src="sh_main.min.js" type="text/javascript"></script>
	<script src="sh_latex.js" type="text/javascript"></script>
</head>
<body onload="sh_highlightDocument();">
	<div id="rice">
		<form method="post" action="index.php" id="form_tex_stream">
			<textarea id="tex_stream" name="tex_stream"><?php echo $tex_stream; ?></textarea>
			<div id="captcha">
				<span class="captcha_text"><?php print captcha('text'); ?></span>
				<input name="captcha_answer" type="text">
				<input name="captcha_id" value="<?php print captcha('id'); ?>" type="hidden">
			</div>
			<input type="submit" value="typeset" class="submit">
			<input type="reset" value="reset" class="reset">
		</form>
	</div>
	<div id="log">
		<?php print $typeset['output']; ?>
	</div>
	<div class="clear__"></div>
	<div id="tex_color">
		<pre class="sh_latex"><?php echo empty($tex_stream) ? $tex_stream_default : $tex_stream; ?></pre>
	</div>
</body>
</html>
