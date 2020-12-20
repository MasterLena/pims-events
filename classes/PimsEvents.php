<?php

namespace Milena\Pims\Events;

class PimsEvents
{
    private string $params = '';
    private array $venues = array();
    private array $events = array();
    private int $previousPage = 0;
    private int $nextPage = 0;
    private int $responseCode;

    public function __construct(int $pageSize = 12, string $dateFrom = '', string $dateTo = '', string $sort = '', int $page = 1 ) {
        $params = '?page_size=' . $pageSize;

        if(!empty($dateFrom)) {
            $params .= '&from_datetime=' . $dateFrom . 'T00:00:00';
        }

        if(!empty($dateTo)) {
            $params .= '&to_datetime=' . $dateTo . 'T00:00:00';
        }

        if(!empty($sort)) {
            $params .= '&sort=' . $sort;
        }

        if(!empty($page)) {
            $params .= '&page=' . $page;
        }

        $this->params = $params;

        $this->setEvents();
    }

    private function setEvents() {
        $url = 'https://sandbox.pims.io/api/v1/events'. $this->params;
        $eventsJSON = array();

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode(  'sandbox:c5jI1ABi8d0x87oWfVzvXALqkf0hToGq' )
            )
        );

        $response = wp_remote_get( $url, $args );
        $responseCode = wp_remote_retrieve_response_code( $response );
        $this->setVenues();
        $venues = $this->getVenues();
        $this->responseCode = $responseCode;

        if($responseCode == 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            if(!empty($data)) {
                $currentPage = $data->page;
                $pageCount = $data->page_count;
                $this->previousPage = $currentPage - 1;
                //no next page
                if($currentPage != $pageCount) {
                    $this->nextPage = $currentPage + 1;
                } else {
                    $this->nextPage = 0;
                }


                foreach($data->_embedded as $key => $events) {
                    foreach ($events as $eventKey => $event) {
                        $eventsJSON[] = array(
                            "id" => $event->id,
                            "label" => $event->label,
                            "date" => date('l jS \of F Y \a\t h:i:s A', strtotime($event->datetime)),
                            "costing_capacity" => $event->costing_capacity,
                            "currency" => $event->currency,
                            "venue_id" => $event->venue_id,
                            "venue_label" => $venues[$event->venue_id]["label"],
                            "venue_city" => $venues[$event->venue_id]["city"],
                            "venue_country" => $venues[$event->venue_id]["country_code"],
                            "sold_out_date" => $event->sold_out_date
                        );
                    }
                }
            }
        }

        $this->events = $eventsJSON;
    }

    public function getEvents(){
        return $this->events;
    }

    private function setVenues() {
        $venuesInfo = array();

        $url = 'https://sandbox.pims.io/api/v1/venues';
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
            $allItemsNum = $data->total_items;

            $url = 'https://sandbox.pims.io/api/v1/venues?page_size=' . $allItemsNum;
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
                    foreach ($data->_embedded as $venues) {
                        foreach ($venues as $venue) {
                            $venuesInfo[$venue->id] = [ "label" => $venue->label, "city" => $venue->city, "country_code" => $venue->country_code ];
                        }
                    }
                }
            }
        }

        $this->venues = $venuesInfo;
    }

    public function getVenues(){
        return $this->venues;
    }

    public function getNextPage(){
        return $this->nextPage;
    }

    public function getPreviousPage(){
        return $this->previousPage;
    }

    public function getResponseCode(){
        return $this->responseCode;
    }
}