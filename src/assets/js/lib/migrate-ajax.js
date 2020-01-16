( function( $ ) {

    $( document ).on( 'submit', '#accessforall-inject-last-modified-date-form', function( event ) {

        event.preventDefault();

        $( 'input[type="submit"]' ).attr( 'disabled', true );

        $.ajax( {
            url: accessforallInjectLastModifiedDate.ajaxUrl,
            data: {
                action: 'accessforall_inject_last_modified_date_create_batch',
                nonce: $( '#accessforall_inject_last_modified_date_nonce' ).val(),
            },
            type: 'POST',
            success : function( response ) {

                $( '#batch-process-total' ).text( response.data.items.length );

                var $modal = $( '#accessforall-inject-last-modified-date-modal' );

                $modal.foundation( 'open' );

                var visibleInterval;
    
                visibleInterval = setInterval( function() {
                    
                    if ( $modal.css( 'display' ) !== 'none' ) {
                        $modal.trigger( 'reveal-visible' );
                        clearInterval( visibleInterval );
                    }
                    
                }, 10 );
                
            },
            error : function( request, status, error ) {
                $( 'input[type="submit"]' ).attr( 'disabled', false );
            }
        } );

    } );

    $( document ).on( 'reveal-visible', '#accessforall-inject-last-modified-date-modal', function() {

        $( '#batch-process-start' ).click();

        $( 'input[type="submit"]' ).attr( 'disabled', false );

    } );

    $( document ).on( 'itemprocessed', function() {
        // We want it to be stoppable at any time
        $( '#batch-process-stop' ).attr( 'disabled', false );
    } );

    $( document ).on( 'closed.zf.reveal', '#accessforall-inject-last-modified-date-modal', function() {
        $( '#batch-process-stop' ).attr( 'disabled', false ).click();
    } );

} )( jQuery );