(function($) {
    // Initialize datepicker from jQuery UI
    $( ".datepicker" ).datepicker();

    /**
     * On load page show filtered events. Filters are stored in browser local storage
     */
    $(window).on('load', function (){
        $( "#events__list" ).remove();

        let dateTo = window.localStorage.getItem('dateTo');
        let dateFrom = window.localStorage.getItem('dateFrom');
        let sort = window.localStorage.getItem('sort');
        let page = window.localStorage.getItem('page');
        let pageSize = window.localStorage.getItem('pageSize');

        $(".datepicker").each(function (index, value){
            if($(value).data('datedirection') === 'to') {
                $(value).attr('value', dateTo);
            } else if($(value).data('datedirection') === 'from') {
                $(value).attr('value', dateFrom);
            }
        });

        $("#sort-by > option").each(function (index, value) {
            if($(value).val() === sort) {
                $(value).attr('selected', 'selected');
            }
        });

        $("#pims__page_size").attr('value', pageSize);

        makeAjaxRequest(dateTo, dateFrom, sort, page, pageSize);
    });

    /**
     * Pick From and To date to filter events in specific date range
     * Load dateTo and dateFrom from Browser Storage if some date is not picked, save new data to browser storage
     * Send date range filters to events script
     */
    $(".datepicker").on('change', function () {
        $(this).datepicker("option", "dateFormat", "yy-mm-dd");
        let date = $(this).val();

        let dateDirection = $(this).data('datedirection');
        let dateTo = window.localStorage.getItem('dateTo');
        let dateFrom = window.localStorage.getItem('dateFrom');
        let sort = window.localStorage.getItem('sort');
        let page = window.localStorage.getItem('page');
        let pageSize = window.localStorage.getItem('pageSize');


        if(dateDirection === 'to') {
            dateTo = date;
            window.localStorage.setItem('dateTo', date);
        } else if(dateDirection === 'from') {
            dateFrom = date;
            window.localStorage.setItem('dateFrom', date)
        }

        if( date && dateDirection ) {
            makeAjaxRequest(dateTo, dateFrom, sort, page, pageSize);
        }
    });

    /**
     * Set number of events shown on page
     * Save number of events to show in browser local storage
     * Send number of listed events filter to events script
     */
    $('#pims__page_size').on('focusout', function (){
        let pageSize = $(this).val();
        window.localStorage.setItem('pageSize', pageSize);
        let sort = window.localStorage.getItem('sort');
        let dateTo = window.localStorage.getItem('dateTo');
        let dateFrom = window.localStorage.getItem('dateFrom');
        let page = window.localStorage.getItem('page');

        makeAjaxRequest(dateTo, dateFrom, sort, page, pageSize);
    });

    /**
     * Sorting Items
     * Save sort in browser local storage
     * Send sort filter to events script
     */
    $("select[id=sort-by]").on('change', function (e)
    {
        e.preventDefault;

        let sortDirection = $(this).val();
        window.localStorage.setItem('sort', sortDirection);
        let dateTo = window.localStorage.getItem('dateTo');
        let dateFrom = window.localStorage.getItem('dateFrom');
        let page = window.localStorage.getItem('page');
        let pageSize = window.localStorage.getItem('pageSize');

        makeAjaxRequest(dateTo, dateFrom, sortDirection, page, pageSize);
    });

    /**
     * Events Pagination script
     * Save page number in browser local storage
     * Send page number filter to events script
     */
    $("button[class^=pims__pagination]").on('click', function (e)
    {
        e.preventDefault;
        let page = $(this).data('page');
        window.localStorage.setItem('page', page);
        let dateTo = window.localStorage.getItem('dateTo');
        let dateFrom = window.localStorage.getItem('dateFrom');
        let sort = window.localStorage.getItem('sort');
        let pageSize = window.localStorage.getItem('pageSize');

        makeAjaxRequest(dateTo, dateFrom, sort, page, pageSize);
    });

    /**
     * Print Events Template
     * @param events
     */
    function pimsEventsTemplate(events)
    {
        let userID = $("#pims__events").data('userid');

        let template = '';

        events.events.forEach(function (event, key) {

            template += '<div class="event" data-id="' + event.id + '">' +
                '<div>' + event.label + '</div>' +
                '<div>' + event.date + '</div>' +
                '<div>' + event.costing_capacity + ' ' + event.currency + '</div>';

            if(event.sold_out_date) {
                template += '<div class="sold-out">SOLD OUT</div>';
            }

            template += '<div class="venue" data-venueid="' + event.venue_id + '">' +
                '<div>' + event.venue_label + '</div>' +
                '<div>' + event.venue_city + '</div>' +
                '<div>' + event.venue_country + '</div>' +
                '</div>';

            if(userID) {
                template += '<button class="saveEvent">Save Event</button>';
            }

            template += '</div>';
        });


        $("#pims__events").append('<div id="events__list">' + template + '</div>');

        $("button[class^=pims__pagination]").each(function (index, value) {
            if($(value).attr('class').includes('prev')) {
                $(value).data('page', events.prev_page);
                $(value).removeAttr('disabled');
                if(events.prev_page === 0) {
                    $(value).attr('disabled', 'disabled');
                }
            } else if($(value).attr('class').includes('next')) {
                $(value).data('page', events.next_page);
                $(value).removeAttr('disabled');
                if(events.next_page === 0) {
                    $(value).attr('disabled', 'disabled');
                }
            }
        });

        /**
         * If user is logged in events template will have 'Save Event' button below each event.
         * This function will allow event to be saved for logged in user
         * Function will be loaded only if node with id #pims__events have data-userid attribute -> UserId local var
         * -> (that means a user is logged in)
         */
        if(userID) {
            $('.saveEvent').on('click', function (e) {
                e.preventDefault();

                let thisItem = $(this);
                let eventID = $(this).parents('.event').data('id');
                let thisItemParent = $(this).parents('.event');

                $.ajax({
                    url: '/wp-content/plugins/pims-events/ajax/events-save.php',
                    dataType: "json",
                    type: 'POST',
                    data: {
                        eventId : eventID
                    },
                    success: function (data) {
                        if (data === null) {
                            return '';
                        }

                        if(data.events && data.venue && data.userEvents) {
                            if(data.userEvents.saved) {
                                thisItemParent.append('<div class="message success">SUCSESS!</div>');
                            } else {
                                thisItemParent.append('<div class="message info">You already saved this event!</div>');
                            }

                        } else {
                            thisItemParent.append('<div class="message error">Something went wrong! Contact your developer</div>');
                        }

                        setTimeout(function(){ $('.message').remove(); }, 3000);

                    },
                    error: function () {
                        thisItemParent.append('<div class="message error">Something went wrong! Contact your developer</div>');
                    }
                });
            } );
        }
    }

    /**
     * Ajax request function. Sends request to plugin/ajax/events.php script
     * @param dateTo
     * @param dateFrom
     * @param sort
     * @param page
     * @param pageSize
     */
    function makeAjaxRequest(dateTo, dateFrom, sort, page, pageSize) {
        var settings = {
            "url": "/wp-content/plugins/pims-events/ajax/events.php",
            "method": "POST",
            data: {
                dateTo: dateTo,
                dateFrom: dateFrom,
                sort: sort,
                page: page,
                pagesize: pageSize
            },
            beforeSend: function() {
                $( "#events__list" ).hide();
                $('#loader').show();
            },
            complete: function ()
            {
                $('#loader').hide();
            },
            success: function () {
                $( "#events__list" ).remove();
            },
            error: function (){
                $( "#events__list" ).show();
            }
        };

        $.ajax(settings).done(function (response) {
            if(!response) {
                return;
            }

            let eventsResponse = JSON.parse(response);
            pimsEventsTemplate(eventsResponse);
        });
    }

    /**
     * This function allows user to reset all filters by clearing all browser local storage and requesting new default list of events
     */
    $("#pims_resetFilter").on('click', function (e){
        e.preventDefault();

        let sort = window.localStorage.getItem('sort');

        $(".datepicker").each(function (index, value){
            $(value).val('');
        });

        $("#sort-by > option").each(function (index, value) {
            if($(value).val() === sort) {
                $(value).removeAttr('selected');
            }
        });

        $("#pims__page_size").val('');

        window.localStorage.clear();

        var settings = {
            "url": "/wp-content/plugins/pims-events/ajax/events.php",
            "method": "POST",
            beforeSend: function() {
                $( "#events__list" ).hide();
                $('#loader').show();
            },
            complete: function ()
            {
                $('#loader').hide();
            },
            success: function (){
                $( "#events__list" ).remove();
            },
            error: function (){
                $( "#events__list" ).show();
            }
        };

        $.ajax(settings).done(function (response) {

            if(!response) {
                return;
            }

            let eventsResponse = JSON.parse(response);
            pimsEventsTemplate(eventsResponse);

        });
    });
}(jQuery, document, window));




