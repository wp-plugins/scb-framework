<?php

function scb_error_handler($errno, $errstr) {
	echo $errstr;
	
	ob_start();
	debug_print_backtrace();
	$out = explode( "\n#", ob_get_clean());
	$out = array_slice( $out, 1);
	echo '<pre>' . "\n#" . implode( "\n#", $out ) . '</pre>';
}
set_error_handler('scb_error_handler', E_WARNING|E_ERROR|E_RECOVERABLE_ERROR|E_USER_WARNING|E_USER_ERROR);

function debug_filters( $tag = false ) {
	global $wp_filter;

	if ( $tag ) {
		$hook[ $tag ] = $wp_filter[ $tag ];

		if ( !is_array( $hook[ $tag ] ) ) {
			trigger_error("Nothing found for '$tag' hook", E_USER_NOTICE);
			return;
		}
	}
	else {
		$hook = $wp_filter;
		ksort( $hook );
	}

	echo '<pre>';
	foreach ( $hook as $tag => $priority ) {
		echo "<br />&gt;&gt;&gt;&gt;&gt;\t<strong>$tag</strong><br />";
		ksort( $priority );
		foreach ( $priority as $priority => $function ) {
			echo $priority;
			foreach( $function as $name => $properties )
				echo "\t$name<br>\n";
		}
	}
	echo '</pre>';
}

class scbDebug {
	private $args;

	function __construct($args) {
		$this->args = $args;

		register_shutdown_function(array($this, '_delayed'));
	}

	function _delayed() {
		if ( !current_user_can('administrator') )
			return;

		$this->raw($this->args);
	}

	static function raw($args, $with_pre = true) {
		if ( $with_pre )
			echo "<pre>";

		foreach ( $args as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);

		if ( $with_pre )
			echo "</pre>";
	}

	static function info() {
		self::raw(scbLoad4::get_info());
	}
}


// Integrate with FirePHP
function fb_debug() {
	$args = func_get_args();

	if ( class_exists('FirePHP') ) {
		$firephp = FirePHP::getInstance(true);
		$firephp->group('debug');
		foreach ( $args as $arg )
			$firephp->log($arg);
		$firephp->groupEnd();

		return;
	}

	new scbDebug($args);
}

function debug() {
	$args = func_get_args();

	scbDebug::raw($args);
}

function debug_scb() {
	add_action('shutdown', array('scbDebug', 'info'));
}

function dpb() {
	echo '<pre>';
	debug_print_backtrace();
	echo '</pre>';
}

function debug_lq() {
	global $wpdb;
	
	debug($wpdb->last_query);
}

function debug_ajax() {
	if ( !defined('DOING_AJAX') )
		return;

	$args = func_get_args();
	scbDebug::raw($args, false);
	die;
}

