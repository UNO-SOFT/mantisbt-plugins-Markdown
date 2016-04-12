<?php
# :vim set noet:

define(MANTIS_DIR, dirname(__FILE__) . '/../..' );
define(MANTIS_CORE, MANTIS_DIR . '/core' );

require_once(MANTIS_DIR . '/core.php');
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class MarkdownPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Markdown';	# Proper name of plugin
		$this->description = 'Markdown note renderer.';	# Short description of the plugin
		$this->page = 'config';		   # Default plugin page

		$this->version = '0.1';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '1.3.0',  #   Should always depend on an appropriate version of MantisBT
			);

		$this->author = 'Tamás Gulácsi';		 # Author/team name
		$this->contact = 'T.Gulacsi@unosoft.hu';		# Author/team e-mail address
		$this->url = 'http://www.unosoft.hu';			# Support webpage

		require_once( dirname(__FILE__) . '/core/Parsedown.php' );
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

	function display_formatted( $p_event, $p_string, $p_extra=null ) {
		if( substr( $p_string, 0, 4 ) !== '#MD#' ) {
			return $p_string;
		}
		$p_string = substr( $p_string, 4 );
		$p_string = str_replace( "<br />\r\n", "\n", $p_string );
		$p_string = str_replace( ' * ', ' \* ', $p_string );
		$res = $this->pd->text( $p_string );
		$count = 0;
		if( strpos( $res, '<p>' ) === 0 && strpos( $res, '<p>', 3 ) === false ) {
			$res = substr( $res, 3, strlen( $res ) - 3 - 4 );
		}
		$res = str_replace( ' \* ', ' * ', $res );
		return $res;
	}

}
