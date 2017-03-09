( function( $ ) {

	$( document ).ready( function() {

		if ( $( '.post-type-mapper' ).length == 0 ) return false;

		$( document ).on( 'click touched', '.post-type-mapper .execute', function() {
			
			$( this ).attr( 'disabled', true );
			$( this ).val( 'Loading...' );
			
			var postTypes = $( this ).data( 'post_types' ).split( ',' );
			
			for ( var index in postTypes ) {

				$.ajax( {
					'type' : 'POST',
					'url' : '/wp-json/rbm-ptm/v1/submit',
					'async' : false,
					'data' : {
						'post_type' : postTypes[ index ],
					},
					success : function( response ) {

						$( '.post-type-mapper .results pre' ).append( response );

					},
					error : function( request, status, error ) {
						
						console.log( status );
						console.log( error );

					}
				} );
				
			}
			
			$( this ).remove();
			$( '.post-type-mapper .results' ).removeClass( 'hidden' );

		} );

	} );

} )( jQuery );