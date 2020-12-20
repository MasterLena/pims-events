<?php
/**
 * Pims Events
 *
 * Plugin Name: PIMS Events  - Show events fetched from https://api.pims.io/
 * Description: Access PIMS Api to fetch Events and manipulate them
 * Version:     1.0.0
 * Author:      Milena Bimbasic
 * Requires PHP: 7.4
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: pims-events
 */


if((stripos(phpversion(), '7.4') === 0)) {
    require_once 'load.php';

    if ( class_exists('Milena\Pims\Events\PimsEventsInit') && class_exists('Milena\Pims\Events\PimsEvents') ) {
        register_activation_hook( __FILE__, array( 'Milena\Pims\Events\PimsEventsInit', 'activation' ) );
        register_deactivation_hook( __FILE__, array( 'Milena\Pims\Events\PimsEventsInit', 'uninstall' ) );
        add_action( 'plugins_loaded', array( 'Milena\Pims\Events\PimsEventsInit', 'init' ) );
    }
} else {
    wp_die('You must have php 7.4');
}
