(function( $ ) {

	/*
	The AdLayersDFPAPI class implements functionality specific to DFP For the AdLayersAPI.
	*/
	AdLayersDFPAPI = function() {}

	// Refreshes a specific ad unit
	AdLayersDFPAPI.prototype.refresh = function( ad_unit ) {
		if ( 'undefined' !== typeof dfpAdUnits[ ad_unit ] ) {
			googletag.pubads().refresh( [ dfpAdUnits[ ad_unit ] ] );
		}
	}

	// Refreshes all ad units
	AdLayersDFPAPI.prototype.refreshAll = function() {
		if ( false === $.isEmptyObject( dfpAdUnits ) ) {
			// DFP needs a numerical indexed array
			var unitsToRefresh = new Array;
			for ( var adUnit in dfpAdUnits ) {
				unitsToRefresh.push( dfpAdUnits[ adUnit ] );
			}
			googletag.pubads().refresh( unitsToRefresh );
		}
	}

	// Switches sizes in debug mode
	AdLayersDFPAPI.swapSizes = function( $size ) {
		// Unselect all other sizes and set this one
		$size.siblings().removeClass( 'selected' );
		$size.addClass( 'selected' );

		// Set the width and height
		$size.parents( '.dfp-ad' ).width( $size.data( 'width' ) );
		$size.parents( '.dfp-ad' ).height( $size.data( 'height' ) );

		// Center the debug container vertically
		$size.parents( '.dfp-debug-container' ).css({
			top: ( $size.data( 'height' ) - $size.parents( '.dfp-debug-container' ).outerHeight() )/2,
		});
	}

	// Enables debug mode
	AdLayersDFPAPI.prototype.debug = function() {
		// Iterate through all of the ad units and display them in debug mode
		$( '.dfp-ad' ).each(function( index ) {
			// Get the ad slot sizes for the current breakpoint
			var $adDiv = $( this );
			var adSlot = $( this ).data( 'adUnit' );
			if ( 'undefined' !== dfpSizeMapping[ adSlot ] ) {
				// Get the appropriate sizes for this breakpoint
				var adSizes = [];
				var maxWidth = -1;
				var maxHeight = -1;
				$.each( dfpSizeMapping[ adSlot ], function( index, value ) {
					if ( $( window ).width() > value[0][0]
						&& $( window ).height() > value[0][1]
						&& value[0][0] > maxWidth
						&& value[0][1] > maxHeight
					) {
						maxWidth = value[0][0];
						maxHeight = value[0][1];
						adSizes = value[1];
					}
				});

				// Set the background
				$( this ).addClass( 'dfp-debug' );

				// Create a container for the ad data
				$container = $( '<div>' )
					.addClass( 'dfp-debug-container' );

				// Add a label
				$label = $( '<div>' )
					.addClass( 'dfp-debug-unit' )
					.text( adSlot );
				$container.append( $label );

				// Add additional sizes for selection
				$.each( adSizes, function( index, value ) {
					$link = $( '<a>' )
						.attr( 'href', '#' )
						.data( 'width', value[0] )
						.data( 'height', value[1] )
						.text( value[0] + 'x' + value[1] )
						.addClass( 'dfp-debug-size' );

					$container.append( $link );
				});

				// Add to the ad div
				$adDiv.append( $container );

				// Set to the first size
				AdLayersDFPAPI.swapSizes( $adDiv.find( 'a' ).first() );
			}
		});

		// Add a debug bar with general layer information and a DFP console toggle
		$layerTitle = $( '<div>' )
				.addClass( 'dfp-ad-layer' )
				.text( adLayersDFP.layerDebugLabel + ': ' + dfpAdLayer.title );

		$googleConsole = $( '<a>' )
				.addClass( 'dfp-console' )
				.attr( 'href', window.location.href.replace( 'adlayers_debug', 'googfc' ) )
				.text( adLayersDFP.consoleDebugLabel );

		$debugBar = $( '<div>' )
			.attr( 'id', 'dfp-debug-bar' )
			.addClass( 'dfp-debug' )
			.append( $layerTitle )
			.append( $googleConsole );

		$( 'body' ).append( $debugBar );

		// If the WordPress admin bar exists, push it down
		if ( $( '#wpadminbar' ).length ) {
			$( '#dfp-debug-bar' ).css( 'top', '32px' );
		}
	}

	// Handle click actions for swapping ad unit sizes
	$( document ).ready(function() {
		$( 'body' ).on( 'click', 'a.dfp-debug-size', function( e ) {
			e.preventDefault();
			AdLayersDFPAPI.swapSizes( $( this ) );
		});
	});
})( jQuery );
