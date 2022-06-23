<?php
# :vim set noet:

// Copyright (C) 2016 Tamás Gulácsi
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License along
// with this program; if not, write to the Free Software Foundation, Inc.,
// 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

define(MANTIS_DIR, dirname(__FILE__) . '/../..' );
define(MANTIS_CORE, MANTIS_DIR . '/core' );

require_once(MANTIS_DIR . '/core.php');
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class MarkdownPlugin extends MantisFormattingPlugin {
	function register() {
		$this->name = 'Markdown';	# Proper name of plugin
		$this->description = 'Markdown note renderer.';	# Short description of the plugin
		$this->page = 'config';		   # Default plugin page

		$this->version = '0.3';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '2.1.0',
			);

		$this->author = 'Tamás Gulácsi';		 # Author/team name
		$this->contact = 'T.Gulacsi@unosoft.hu';		# Author/team e-mail address
		$this->url = 'http://www.unosoft.hu';			# Support webpage

		foreach( array('vendor/erusev', 'library') as $t_base )	{
			$t_fn = MANTIS_DIR . '/' . $t_base . '/parsedown/Parsedown.php';
			if( file_exists( $t_fn ) ) {
				require_once( $t_fn );
				break;
			}
		}
		$this->pd = new Parsedown();
		$this->pd->setMarkupEscaped(FALSE);
		$this->pd->setBreaksEnabled(FALSE);
	}

	function config() {
		return array();
	}

	function hooks() {
		return array(
			'EVENT_DISPLAY_FORMATTED' => 'display_formatted',
		);
	}

	function display_formatted( $p_event, $p_string, $p_multiline = true ) {
		static $s_core_formats, $s_core_installed;
		static $s_urls, $s_buglinks;
		if( null === $s_core_installed ) {
			$s_core_installed = plugin_is_installed( 'MantisCoreFormatting' );
			if( !$s_core_installed ) {
				$s_core_formats = FALSE;
			} else {
				$s_core_formats = config_get( 'plugin_MantisCoreFormatting_process_text' ) == TRUE;
			}
			$this->pd->setBreaksEnabled( !$s_core_cormats );
		}
		if( !$s_core_formats ) {
			$t_string = $p_string;
			if( substr( $t_string, 0, 4 ) === '#MD#' ) {
				// Markdown formatting
				$t_string = md_strip_mark( $t_string );
				$t_string = md_process_bugnote_link( $t_string );
				$t_string = $this->pd->text( $t_string );
				return strip_p( $t_string );
			}
			// Mantis formatting
			// replicate the functionality of MantisCoreFormatting->text
			$t_string = string_strip_hrefs( $t_string );
			$t_string = string_html_specialchars( $t_string );
			$t_string = string_restore_valid_html_tags( $t_string, $p_multiline );

			if( $p_multiline ) {
				$t_string = string_preserve_spaces_at_bol( $t_string );
				$t_string = string_nl2br( $t_string );
			}

			if( $s_core_installed ) {
				return $t_string;
			}

			if( null === $s_urls ) {
				$s_urls = config_get( 'plugin_MantisCoreFormatting_process_urls' ) == FALSE;
				$s_buglinks = config_get( 'plugin_MantisCoreFormatting_process_buglinks' ) == FALSE;
			}
			if( $s_urls ) {
				$t_string = string_insert_hrefs( $t_string );
			}
			if( $s_buglinks ) {
				$t_string = string_process_bug_link( $t_string );
				$t_string = string_process_bugnote_link( $t_string );
			}
			return $t_string;
		}

		// Already Mantis-formatted
		if( substr( $p_string, 0, 4 ) !== '#MD#' ) {
			return $p_string;
		}
		$t_string = md_strip_mark( $p_string );

		// Try to "deformat" to get the original text back.
		$t_string = str_replace( "<br />\r\n", "\n", $t_string );
		$olen = strlen( $t_string );
		$t_string = str_replace( ' * ', ' \* ', $t_string );
		$changed_stars = strlen( $t_string ) - $olen;
		$t_string = $this->pd->text( $t_string );
		$t_string = strip_p( $t_string );
		if( $changed_stars > 0 ) {
			$t_string = str_replace( ' \* ', ' * ', $t_string );
		}
		return $t_string;
	}

}

function strip_p( $p_string ) {
	if( substr( $p_string, 0, 3 ) === '<p>' && strpos( $p_string, '<p>', 3 ) === false && substr( $p_string, strlen( $p_string ) - 4 ) === '</p>' ) {
		return substr( $p_string, 3, strlen( $p_string ) - 3 - 4 );
	}
	return $p_string;
}

function md_strip_mark( $p_string ) {
	if( substr( $p_string, 0, 4 ) !== '#MD#' ) {
		return $p_string;
	}
	$p_string = substr( $p_string, 4 );
	if( substr( $p_string, 0, 1 ) === "\n" ) {
		return substr( $p_string, 1 );
	}
	return $p_string;
}

function md_process_bugnote_link( $p_string ) {
	static $s_tag = null;
	if( $s_tag === null ) {
		$s_tag = config_get( 'bugnote_link_tag' );
	}
	if( $s_tag == '' ) {
		return $p_string;
	}
	return preg_replace_callback(
		'/(^|[^\w])(' . preg_quote( $s_tag, '/' ) . '\d+)\b/',
		function( $p_array ) {
			return $p_array[1] . '[' . $p_array[2] . '](' . string_process_bugnote_link( $p_array[2], false ) . ')';
		},
		$p_string
	);
}
