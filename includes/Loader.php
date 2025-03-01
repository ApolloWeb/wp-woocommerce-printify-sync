<?php
/**
 * Loader class to register actions and filters.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

class Loader {

    /**
     * Actions array.
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Filters array.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Add a new action.
     *
     * @param string $hook          Hook name.
     * @param object $component     Object instance.
     * @param string $callback      Callback method.
     * @param int    $priority      Priority.
     * @param int    $accepted_args Number of accepted arguments.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Add a new filter.
     *
     * @param string $hook          Hook name.
     * @param object $component     Object instance.
     * @param string $callback      Callback method.
     * @param int    $priority      Priority.
     * @param int    $accepted_args Number of accepted arguments.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Execute all registered actions and filters.
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}