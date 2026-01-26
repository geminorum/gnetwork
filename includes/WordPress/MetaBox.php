<?php namespace geminorum\gNetwork\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core;

class MetaBox extends Core\Base
{
	// @OLD: `MetaBox::getTitleAction()`
	public static function markupTitleAction( $action )
	{
		if ( empty( $action['link'] ) )
			return '';

		$html = Core\HTML::tag( 'a', [
			'href'   => $action['url'],
			'title'  => $action['title'] ?: FALSE,
			'target' => empty( $action['newtab'] ) ? FALSE : '_blank',
		], $action['link'] );

		return ' '.Core\HTML::tag( 'span', [ 'class' => 'postbox-title-action' ], $html );
	}

	public static function markupTitleInfo( $text, $icon = NULL )
	{
		if ( ! $text )
			return '';

		$html = ' <span class="postbox-title-action" data-tooltip="'.Core\Text::wordWrap( $text ).'"';
		$html.= ' data-tooltip-pos="'.( Core\L10n::rtl() ? 'down-left' : 'down-right' ).'"';
		$html.= ' data-tooltip-length="medium">'.Core\HTML::getDashicon( $icon ?? 'info' ).'</span>';

		return $html;
	}

	public static function markupTitleHelp( $text, $icon = NULL )
	{
		if ( Strings::isEmpty( $text ) )
			return '';

		return ' '.Core\HTML::tag( 'span', [
			'class' => 'postbox-title-info',
			'style' => 'display:none',
			'title' => Core\Text::decodeEntities( $text ),
			'data'  => [
				'title' => 'info',
			],
		], Core\HTML::getDashicon( $icon ?? 'editor-help' ) );
	}
}
