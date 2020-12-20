<?php

namespace Milena\Pims\Events;

class PimsEventsInit
{
    private function __construct() {}

    public static function init(){
        add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueueScripts' ));
        add_shortcode('pims-events', array( __CLASS__, 'eventsCreateShortcode' ));
        add_filter('template_include', array(__CLASS__, 'frontPageTemplateOverride'), 1);
        add_filter('the_content', array( __CLASS__, 'frontPageLoadEventsFilter' ), 1);
    }

    public static function activation() {
        self::createTables();
    }

    public static function createTables()
    {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $eventsTableName = $wpdb->prefix . "pims_events";
        $venuesTableName = $wpdb->prefix . "pims_venues";
        $userRelationalTableName = $wpdb->prefix . "pims_user_events";
        $charset_collate = $wpdb->get_charset_collate();

        $sqlEvents = "CREATE TABLE IF NOT EXISTS $eventsTableName (
  id mediumint(9) NOT NULL,
  venue_id mediumint(9) NOT NULL,
  label tinytext NOT NULL,
  datetime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  costing_capacity int(11) NOT NULL,
  currency varchar(55) DEFAULT '' NOT NULL,
  sold_out_date datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  CONSTRAINT Constr_Pims_EventID UNIQUE( id ),
  CONSTRAINT Constr_Pims_Event_Venue FOREIGN KEY ( venue_id ) REFERENCES $venuesTableName ( id )
) $charset_collate;";


        $sqlVenues = "CREATE TABLE IF NOT EXISTS $venuesTableName (
 id mediumint(9) NOT NULL,
 label tinytext NOT NULL,
 city text NOT NULL,
 country_code varchar(4) NOT NULL,
 PRIMARY KEY  (ID),
 CONSTRAINT Constr_Pims_VenueID UNIQUE( id )
) $charset_collate;";

        $sqlEventsUserRelational = "CREATE TABLE IF NOT EXISTS $userRelationalTableName (
 user_id bigint(20) UNSIGNED NOT NULL,
 event_id mediumint(9) NOT NULL,
 CONSTRAINT Constr_User_ID FOREIGN KEY ( user_id ) REFERENCES ".$wpdb->prefix."users ( ID ),
 CONSTRAINT Contr_Event_ID FOREIGN KEY ( event_id ) REFERENCES $eventsTableName ( id )
)";

        dbDelta( $sqlVenues );
        dbDelta( $sqlEvents );
        dbDelta( $sqlEventsUserRelational );

        add_option( "pims_db_version", "1.0" );
    }

    public static function eventsCreateShortcode() {

        if(is_user_logged_in()) {
            $dataUserId = 'data-userid="' . get_current_user_id() . '"';
        } else {
            $dataUserId = '';
        }

        $events = new PimsEvents();
        if($events->getResponseCode() === 200) {
            return '<div id="pims__events" style = "display:block!important;" ' . $dataUserId . '>
                    <div>See all events on <a href="' . get_home_url() . '">Home</a></div>
                    <div>
                    <p>From Date: <input type="text" class="datepicker" data-datedirection="from"></p>
                    <p>To Date: <input type="text" class="datepicker" data-datedirection="to"></p>
                    </div>
                    <label for="sort-by">Sort By:</label>
                    <select id="sort-by">
                        <option value=""></option>
                        <option value="label">Event Title ASC</option>
                        <option value="-label">Event Title DESC</option>
                        <option value="datetime">Date ASC</option>
                        <option value="-datetime">Date DESC</option>
                        <option value="venue_label">Venue Name ASC</option>
                        <option value="-venue_label">Venue Name DESC</option>
                        <option value="venue_city">Venue City ASC</option>
                        <option value="-venue_city">Venue City DESC</option>
                        <option value="venue_country">Venue Country ASC</option>
                        <option value="-venue_country">Venue Country DESC</option>
                    </select>
                    <div>
                        <label>Num of events per page:</label>
                        <input id="pims__page_size" type="number" min="1" value="" />
                    </div>
                    <div><img alt="loading" id="loader" src="/wp-content/plugins/pims-events/images/transparrent.gif"></div>
                    <div id="events__list">
                        <!-- here goes template -->
                    </div>
                </div>
                <button class="pims__pagination-prev" data-page="' . $events->getPreviousPage() . '" disabled>PREV</button><button class="pims__pagination-next" data-page="' . $events->getNextPage() . '">NEXT</button>
                <button id="pims_resetFilter">RESET FILTERS</button>';
        }
        return '';
    }

    public static function frontPageTemplateOverride($template){
        if(is_front_page()) {
            return WP_PLUGIN_DIR . '/pims-events/templates/front-page.php';
        }

        return $template;
    }

    public static function frontPageLoadEventsFilter($content) {
        if (is_front_page() && is_main_query()) {
            remove_filter('the_content', array(__CLASS__, 'frontPageLoadEventsFilter'), 1);
            $events = new PimsEvents(329);
            $template = '';

            if($events->getResponseCode() === 200) {
                $template = '<div id="events__list">';

                foreach ($events->getEvents() as $event) {
                    $template .= '<div class="event" data-id="' . $event['id'] . '">
                                    <div>' . $event['label'] . '</div>
                                    <div>' . $event['date'] . '</div>
                                    <div>' . $event['costing_capacity'] . '</div>';

                    if(!empty($event->sold_out_date)) {
                        $template .= '<div class="sold-out">SOLD OUT</div>';
                    }

                    $template .= '    <div class="venue" data-venueid="' . $event['venue_id'] . '">
                                       <div>' . $event['venue_label'] . '</div>
                                       <div>' . $event['venue_city'] . '</div>
                                       <div>' . $event['venue_country'] . '</div>
                                   </div>
                                </div>';
                }

                $template .= '</div>';
            }


            $content = $template . $content;
        }

        return $content;
    }

    public static function enqueueScripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style( 'jquery-ui-datepicker-style' , '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
        wp_enqueue_script( 'jquery-ui-datepicker' );
        if(!is_front_page()) {
            wp_enqueue_script( 'pims-events-js', plugins_url( '../JS/events.js', __FILE__ ), array('jquery'), '1.0.0', true);
        }
        wp_enqueue_style('pimsEventsStyle', plugins_url( '../css/events.css', __FILE__ ), false, '1.0.0');
    }

    public static function uninstall() {
        global $wpdb;
        $eventsTableName = $wpdb->prefix . "pims_events";
        $venuesTableName = $wpdb->prefix . "pims_venues";
        $userRelationalTableName = $wpdb->prefix . "pims_user_events";
        $sqlEvents = "DROP TABLE IF EXISTS $eventsTableName";
        $sqlVenues = "DROP TABLE IF EXISTS $venuesTableName";
        $sqlEventsUserRelational = "DROP TABLE IF EXISTS $userRelationalTableName";

        $wpdb->query($sqlEventsUserRelational);
        $wpdb->query($sqlEvents);
        $wpdb->query($sqlVenues);

        delete_option('pims_db_version');
    }
}