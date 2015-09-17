<?php defined( 'ABSPATH' ) or die( 'Restricted access' );
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'."\n"; ?>
<rss version="2.0">
	<channel>
		<title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
		<link><?php bloginfo_rss('url'); ?></link>
		<item>
			<title><?php echo $title; ?></title>
			<link><?php echo $link; ?></link>
			<description><?php echo $desc; ?></description>
			<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', current_time( 'mysql', true ), false ); ?></pubDate>
		</item>
	</channel>
</rss>
