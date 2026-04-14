<?php
/**
 * GABC Loader Class
 *
 * @package GridxflexAnnouncementBarswithCTA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GABC_Loader
 *
 * Manages all actions and filters for the plugin.
 */
class GABC_Loader {

	/**
	 * Holds all actions
	 *
	 * @var array
	 */
	private $actions = array();

	/**
	 * Holds all filters
	 *
	 * @var array
	 */
	private $filters = array();

	/**
	 * Add an action
	 *
	 * @param string $hook Hook name
	 * @param object $component Component object
	 * @param string $callback Callback function
	 * @param int    $priority Priority
	 * @param int    $accepted_args Number of arguments
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add_hook( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a filter
	 *
	 * @param string $hook Hook name
	 * @param object $component Component object
	 * @param string $callback Callback function
	 * @param int    $priority Priority
	 * @param int    $accepted_args Number of arguments
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add_hook( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add hook
	 *
	 * @param array  $hooks Hooks array
	 * @param string $hook Hook name
	 * @param object $component Component object
	 * @param string $callback Callback function
	 * @param int    $priority Priority
	 * @param int    $accepted_args Number of arguments
	 */
	private function add_hook( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return $hooks;
	}

	/**
	 * Run the loader
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
