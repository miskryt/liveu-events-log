<?php

namespace LiveuEventsLog\Helpers;


/**
 * Helper functions.
 */
class DiffParser {

	public static function text_diff( $left_string, $right_string, $args = null ) {
		$defaults = array(
			'title' => '',
			'title_left' => '',
			'title_right' => '',
			'leading_context_lines' => 1,
			'trailing_context_lines' => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! class_exists( 'WP_Text_Diff_Renderer_Table' ) ) {
			require ABSPATH . WPINC . '/wp-diff.php';
		}

		$left_string = normalize_whitespace( $left_string );
		$right_string = normalize_whitespace( $right_string );

		$left_lines = explode( "\n", $left_string );
		$right_lines = explode( "\n", $right_string );
		$text_diff = new \Text_Diff( $left_lines, $right_lines );

		$renderer = new \WP_Text_Diff_Renderer_Table( $args );
		$renderer->_leading_context_lines = $args['leading_context_lines'];
		$renderer->_trailing_context_lines = $args['trailing_context_lines'];

		$diff = $renderer->render( $text_diff );

		if ( $diff === '' ) {
			return '';
		}

		$r = '';

		$r .= "<div class='' tabindex='0'>";
		$r .= "<div class=''>";

		$r .= "<table class='diff'>\n";

		if ( ! empty( $args['show_split_view'] ) ) {
			$r .=
				"<col class='content diffsplit left' /><col class='content diffsplit middle' /><col class='content diffsplit right' />";
		} else {
			$r .= "<col class='content' />";
		}

		if ( $args['title'] || $args['title_left'] || $args['title_right'] ) {
			$r .= '<thead>';
		}
		if ( $args['title'] ) {
			$r .= "<tr class='diff-title'><th colspan='4'>$args[title]</th></tr>\n";
		}
		if ( $args['title_left'] || $args['title_right'] ) {
			$r .= "<tr class='diff-sub-title'>\n";
			$r .= "\t<td></td><th>$args[title_left]</th>\n";
			$r .= "\t<td></td><th>$args[title_right]</th>\n";
			$r .= "</tr>\n";
		}
		if ( $args['title'] || $args['title_left'] || $args['title_right'] ) {
			$r .= "</thead>\n";
		}

		$r .= "<tbody>\n$diff</div>\n</tbody>\n";
		$r .= '</table>';

		$r .= '</div>';
		$r .= '</div>';

		return $r;
	}
}
