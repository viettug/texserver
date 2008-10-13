@echo off
set server=http://kyanh.zapto.org:1006/index2.php
set src=c:\texmaker
set path=%path%;%dest%
set file=%1
%src%\gawk --file c:/texmaker/gawk.txt %file%.tex > output
%src%\curl --progress-bar -F "action=typeset" -F "tex_stream=<output" %server% > curl_out.bat
call curl_out.bat
