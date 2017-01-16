<?php

	$protocol = $_SERVER["SERVER_PROTOCOL"];
	if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
		$protocol = 'HTTP/1.0';
	header( "$protocol 403 Service Unavailable", TRUE, 403 );

	if ( function_exists( 'nocache_headers' ) )
		nocache_headers();

	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Retry-After: 600' );

?><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head><title>Restricted</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style type="text/css">
*,*:after,*:before {-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;padding:0;margin:0;border:0;}
.clearfix:before,.clearfix:after{content:" ";display:table;}.clearfix:after{clear:both;}.clearfix{*zoom:1;}
html,body {height:100%; background-color: #fff;color: gray;}
html * { -webkit-font-smoothing: antialiased; }
body { font:normal 14px/24px Tahoma, serif; }
.clr {clear:both;}
a:link,a:visited {color: #686868;text-decoration: none;}
a:hover {color: navy;text-decoration: none;}
h1 { font: 1.6em georgia, arial, tahoma, courier new, monospace;font-weight:700; }
.bo { position: absolute; margin: -150px 0pt 0pt -300px; width: 600px; height: 300px; top: 50%; left: 50%; font: 0.9em tahoma,courier new,monospace; text-align: center; }
</style>
</head><body><div class="bo">
	<br /><br /><br /><br /><br /><br /><br />
	<span dir="ltr"><b>Restricted</b><br /><a href="/wp-login.php?action=logout">logout</a></span>
	<br /><br />
</div></body></html><?php
die();
