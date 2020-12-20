<?php

namespace Milena\Pims\Events;

class PimsUserSaveEvents
{
    private int $eventId;
    private ?object $event;
    private ?object $venue;
    private int $userId;
    private ?array $eventSuccess = null;
    private ?array $venueSuccess = null;
    private ?array $userEventSuccess = null;

    public function __construct( $eventId ) {
        $this->eventId = $eventId;
        $this->userId = get_current_user_id();
        $this->insertUserEventsInfo();
    }

    private function insertUserEventsInfo() {
        $this->event = $this->getItemInfo($this->eventId, 'events');
        if(isset($this->event)) {
            $this->venue = $this->getItemInfo($this->event->venue_id, 'venues');
            if(isset($this->venue)) {
                $this->saveVenueInfo();
                $this->saveEventInfo();
                $this->saveUserEvent();
            }
        }
    }

    private function saveVenueInfo(){
        global $wpdb;

        $venueExists = $wpdb->query($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "pims_venues WHERE id = %d", $this->venue->id));

        if(empty($venueExists)) {
            $error = $wpdb->insert($wpdb->prefix . 'pims_venues', array(
                'id' => $this->venue->id,
                'label' => $this->venue->label,
                'city' => $this->venue->city,
                'country_code' => $this->venue->country_code
            ));

            if(!empty($error)) {
                $this->venueSuccess = ['saved' => true, 'exists' => false];
            }
        } else {
            $this->venueSuccess = ['saved' => false, 'exists' => true ];
        }

    }

    private function saveEventInfo(){
        global $wpdb;

        $eventExists = $wpdb->query($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "pims_events WHERE id = %d", $this->event->id));

        if(empty($eventExists)) {
            $error = $wpdb->insert($wpdb->prefix . 'pims_events', array(
                'id' => $this->event->id,
                'venue_id' => $this->event->venue_id,
                'label' => $this->event->label,
                'datetime' => $this->event->datetime,
                'costing_capacity' => $this->event->costing_capacity,
                'currency' => $this->event->currency,
                'sold_out_date' => $this->event->sold_out_date
            ));

            if(!empty($error)) {
                $this->eventSuccess =  ['saved' => true, 'exists' => false];
            }
        } else {
            $this->eventSuccess = ['saved' => false, 'exists' => true ];
        }
    }

    private function saveUserEvent(){
        global $wpdb;
        $relationExists = $wpdb->query($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "pims_user_events WHERE event_id = %d AND user_id = %d", array($this->event->id, $this->userId)));

        if(empty($relationExists)) {
            $error = $wpdb->insert($wpdb->prefix . 'pims_user_events', array(
                'user_id' => $this->userId,
                'event_id' => $this->event->id
            ));

            if(!empty($error)) {
                $this->userEventSuccess = ['saved' => true, 'exists' => false];
            }
        } else {
            $this->userEventSuccess = ['saved' => false, 'exists' => true ];
        }
    }

    private function getItemInfo(int $id , string $type = 'events') {
        $url = 'https://sandbox.pims.io/api/v1/' . $type  .'/'. $id;
        $itemInfo = null;

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode(  'sandbox:c5jI1ABi8d0x87oWfVzvXALqkf0hToGq' )
            )
        );

        $response = wp_remote_get( $url, $args );
        $responseCode = wp_remote_retrieve_response_code( $response );

        if($responseCode == 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            if(!empty($data)) {
                $itemInfo = $data;
            }
        }

        return $itemInfo;
    }

    public function getEventSuccess(){
        return $this->eventSuccess;
    }

    public function getVenueSuccess(){
        return $this->venueSuccess;
    }

    public function getUserEventSuccess(){
        return $this->userEventSuccess;
    }

}