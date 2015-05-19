jQuery(document).ready( function($) {
	// hide my meta fields
	$( 'table.media td.title div.row-actions' ).each(function() {
		$( this ).find( 'div.media-url-box' ).hide();
	});
	// show inputs on click
	$( 'span.media-url' ).on( 'click', 'a.media-url-click', function(event) {

		// this removes the hash in the URL for cleaner UI
		event.preventDefault();

		// stop the propagation
		event.stopPropagation();

		// target the specific row we are editing
		var edit_row = $( this ).next( 'div.media-url-box' );

		// add a new class
		$( this ).toggleClass( 'media-url-open' );

		// add a class
		$( edit_row ).addClass( 'media-url-visible' );

		// show my edit fields
		$( edit_row ).slideToggle( 'slow' );

		$( edit_row ).find( 'input.media-url-field' ).select();
		
		// hide the rest
		$( 'div.media-url-box' ).not( edit_row ).slideUp( 'slow' );

	});

	// select text on click
	$( 'input.media-url-field' ).focus(function(){this.select();});

});