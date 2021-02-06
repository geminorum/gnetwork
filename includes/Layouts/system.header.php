<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">

<?php if ( ! empty( $head_title ) ) echo '<title>'.$head_title.'</title>'; ?>
<?php if ( ! empty( $head_callback ) && is_callable( $head_callback ) ) call_user_func( $head_callback ); ?>

<style type="text/css">
:root {
  font-size: calc(1vw + 1vh + .5vmin);
}
html, body, .wrap {
  height: 100%;
  margin: 0;
}
body {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	color: #333;
	background: #f7f7f7;
}
.wrap {
	text-align: center;
	display: -webkit-flex;
	display: flex;
	-webkit-align-items: center;
	align-items: center;
	-webkit-justify-content: center;
	justify-content: center;
}
h1 { color: #d9534f; }
h1, h3 { margin: 0; }
.small { font-size: small; }
</style>
</head>
<body<?php if ( ! empty( $body_class ) ) echo ' class="'.$body_class.'"'; ?>>
<div class="wrap"><div>
