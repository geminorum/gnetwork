jQuery(document).ready(function($) {
	$('a.audio-go-to-time').click(function(e) {
		e.preventDefault();
		var instance = $(this).data('instance');
		$('audio')[instance].player.setCurrentTime($(this).data('time'));
		$('audio')[instance].player.setCurrentRail();
		$('audio')[instance].player.play();
	});
});
