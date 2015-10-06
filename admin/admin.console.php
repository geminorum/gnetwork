<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

gNU::superAdminOnly();


$footnotes = 'پا‌نوشت‌ها</strong>

1 – Dr.Virtue

2 – Wine of This year s Vintage

3 – Dr. Soul

4 - Conformity

5 – Michael Wager

6 – Mendy

7 – Terrence McNally"s The Lisbon Traviata

8 – The Caretaker by Harold Pinter

9 – Mel Gussow

10 – Albee

11 – The Death of Bessie Smith

12. Nilo Cruz

13 . Equus by Peter Shaffer

14 – Tracy Letts s Killer Joe

15 – Garcia Lorca s Blood Wedding

16 – God Spell

17 – Outburst

18 – Lanford Wilson

19 – In The Playwright’s Art

20 – The Hot 1 Baltimore

21 – April

22 – Neil Simon s Lost in Yonkers

23 – Bella

24 – Louie

25 – John Guare

26 – Lydie Breeze

27 – Larry Kramer’s The Normal Heart

28 – William Hoffman’s As Is

29 – Paula Vogel’s The Baltimore Waltz

30 – Tony Kushner’s Angels in America

31 – Craig Lucas’s The Dying Gaul

32 – Peloponnesian

33 – Tracers

34 – John DiFusco

35 – The Trilogy by David Rabe Including Streamers – Basic Training of Pavlo Hummel and Sticks and Bones

36 – In the Heart of America by Naomi Wallace

37 – Clifford Odets

38 – Waiting for Lefty

39 – The Children s Hour by Lillian Hellman

40 – Boys in the Band by Mart Crowley

41 – Torch Song Trilogy by Harvey Fierstein

42 – Jeffrey by Paul Rudnik';

gNetworkReference::replaceFootnotes( '', $footnotes, 'ndash' );



return;

global $gNetwork, $gEditorial, $gMemberNetwork, $gPeopleNetwork;
require_once( GNETWORK_DIR.'assets/libs/kint/Kint.class.php' );

echo gNU::size( $gEditorial );
Kint::dump( $gEditorial );

return;

global $wp;

gNU::dump( gNU::currentURL() );
gNU::dump( $_SERVER['REQUEST_URI'] );
gNU::dump( $wp );


return;


// gNetworkMedia::clean_attachment( 8319 );

global $gnetworkOptionsNetwork, $gnetworkOptionsBlog;
gNU::dump( $gnetworkOptionsNetwork );
gNU::dump( $gnetworkOptionsBlog );

gNU::dump( get_site_option( 'gnetwork_site', array() ) );
gNU::dump( get_option( 'gnetwork_blog', array() ) );

$text   = 'این یک متن فارسی است';
$accent = 'كَتَبَ عَلىٰ نَفسِهِ الرَّحمَةَ';
$name   = 'ارنست هِمینگ‌وی';

// require GPEOPLE_DIR.'assets/libs/urlify/URLify.php';
// echo URLify::filter( 'Lo siento, no hablo español.', 255, 'fa' );
// echo URLify::filter( $text, 255, 'fa' );
// echo URLify::downcode( $text );

// echo iconv( 'UTF-8', 'UTF-8//TRANSLIT', $accent );
// echo preg_replace( '/\p{Mn}/u', '', Normalizer::normalize( $accent, Normalizer::FORM_KD ) ); // http://stackoverflow.com/a/3542752
echo preg_replace( '/\p{Mn}/u', '', Normalizer::normalize( $name, Normalizer::FORM_KD ) ); // http://stackoverflow.com/a/3542752
