<?php
/**
 * Mock WP_CLI class for unit tests.
 *
 * @package Evt
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Mock WP_CLI class for testing CLI commands without WP-CLI environment.
	 */
	class WP_CLI {

		/**
		 * Registered commands.
		 *
		 * @var array<string, callable>
		 */
		public static $commands = array();

		/**
		 * Log messages.
		 *
		 * @var string[]
		 */
		public static $logs = array();

		/**
		 * Success messages.
		 *
		 * @var string[]
		 */
		public static $successes = array();

		/**
		 * Error messages.
		 *
		 * @var string[]
		 */
		public static $errors = array();

		/**
		 * Add command mock.
		 *
		 * @param string   $name    Command name.
		 * @param callable $handler Handler.
		 * @return void
		 */
		public static function add_command( string $name, $handler ): void {
			self::$commands[ $name ] = $handler;
		}

		/**
		 * Log mock.
		 *
		 * @param string $msg Message.
		 * @return void
		 */
		public static function log( string $msg ): void {
			self::$logs[] = $msg;
		}

		/**
		 * Success mock.
		 *
		 * @param string $msg Message.
		 * @return void
		 */
		public static function success( string $msg ): void {
			self::$successes[] = $msg;
		}

		/**
		 * Error mock.
		 *
		 * @param string $msg Message.
		 * @throws RuntimeException Mock error throw.
		 * @return void
		 */
		public static function error( string $msg ): void {
			self::$errors[] = $msg;
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de prueba en test.
			throw new RuntimeException( 'WP_CLI_ERROR: ' . $msg );
		}

		/**
		 * Reset state.
		 *
		 * @return void
		 */
		public static function reset(): void {
			self::$commands  = array();
			self::$logs      = array();
			self::$successes = array();
			self::$errors    = array();
		}
	}
}

if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}
