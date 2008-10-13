dest=texserver
default:
	@rm -fv $(dest).zip
	@zip -9r $(dest).zip \
		*.* \
		codepress/* \
		texmaker/* \
		.htaccess \
		-x ".svn*"
	@chmod 600 $(dest).zip
	@chmod 700 texmaker
