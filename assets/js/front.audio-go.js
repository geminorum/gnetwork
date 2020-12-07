jQuery(function ($) {
  $('a.audio-go-to-time').click(function (e) {
    e.preventDefault();
    const instance = $(this).data('instance');
    $('audio')[instance].player.setCurrentTime($(this).data('time'));
    $('audio')[instance].player.setCurrentRail();
    $('audio')[instance].player.play();
  });
});
