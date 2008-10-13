<?php
/**
 * $Id: index.php 21 2008-10-12 22:20:20Z kyanh $
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

	define('USER', $_SERVER['REMOTE_ADDR']);
	define('SUFFER', 4); /* minimum distance between two accesses */
	define('IS_LOCAL', $_SERVER['REMOTE_ADDR'] == '192.168.1.9');
	define('LATEX', '/usr/bin/latex');
	error_reporting(IS_LOCAL ? E_ALL : 0 );

	if (!IS_LOCAL) die('access deninied');

	/********************************************************* system variables */

	$tex_stream_default = '';

	$tex_stream = isset($_POST['tex_stream']) ? $_POST['tex_stream']: '';
	$tex_stream = trim($tex_stream);
	$tex_stream_default = trim($tex_stream_default);

	/* access time */
	$access = time();
	$jobname = USER."-$access";
	$jobdir = dirname(__FILE__) . "/tmp"; /* physical path */
	$jobdir_web = "/tmp"; /* web path */
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

	/************************************************************** hilight log */

	function hilight_log($st) {
		$st = explode("\\onenout1", $st);
		$st = $st[1];
		return $st;
	}


	/*************************************************** typeset the tex_stream */

	function typeset() {
		global $tex_stream, $jobname, $jobdir, $jobdir_web, $tex_stream_default;
		global $access, $last_access;

		$texfile = make_tex_file();
		$options = array(
			"-no-shell-escape",
			"-halt-on-error",
			"-output-directory=$jobdir",
			"-jobname=$jobname",
			"-output-format=pdf"
		);

		$option = implode(" ", $options);
		exec(LATEX. " $option $texfile", $output, $retval);

		$url = "http://".$_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT']."$jobdir_web/$jobname";
		if ($retval == 0) {
			$output = array("%src%\\curl -s $url.pdf > output.pdf ","%src%\\foxit output.pdf");
		}
		else {
			$output = array("%src%\\curl -s $url.log > output.log ", "%src%\\u2d output.log", "notepad output.log");
		}

		$output = implode("\n", $output);

		$retval = intval($retval);
		return array('output' => $output, 'retval' => $retval);
	}

	/************************************************************ typset it now */

	$typeset = typeset();

	if (empty($tex_stream)) {
		print '
		<form method="post" action="index2.php" id="form_tex_stream">
		<textarea name="tex_stream"></textarea>
		<input name="action" type="submit" value="typeset">
		</form>';
	}
	else {
		print $typeset['output'];
	}
?>
