(function( $ ) {

	/*
	The AdLayersAPI class provides a suite of functions for manipulating ads on the client side.
	This abstracts the functionality from the specific ad server in use.
	Each ad server will be required to provide functionality for the below functions.
	If any of the functions do not exist, they will simply do nothing.
	*/
	AdLayersAPI = function() {
		// Create an object for the active ad server
		this.adServer = null;

		// Create an instance of the ad server class, if it exists
		if ( 'string' === typeof adLayersAdServer.jsAPIClass && 'function' === typeof window[ adLayersAdServer.jsAPIClass ] ) {
			this.adServer = new window[ adLayersAdServer.jsAPIClass ];
		}
	}

	// Refreshes a specific ad unit
	AdLayersAPI.prototype.refresh = function( ad_unit ) {
		if ( this.functionExists( 'refresh' ) ) {
			this.adServer.refresh( ad_unit );
		}
	}

	// Refreshes all ad units
	AdLayersAPI.prototype.refreshAll = function() {
		if ( this.functionExists( 'refreshAll' ) ) {
			this.adServer.refreshAll();
		}
	}

	// Lazy load an ad
	AdLayersAPI.prototype.lazyLoadAd = function( args ) {
		if ( this.functionExists( 'lazyLoadAd' ) ) {
			return this.adServer.lazyLoadAd( args );
		}
	};

	// Enables debug mode
	AdLayersAPI.prototype.debug = function() {
		if ( this.functionExists( 'debug' ) ) {
			this.adServer.debug();
		}
	}

	// Determines if debug mode has been specified
	AdLayersAPI.isDebug = function() {
		return ( -1 != window.location.href.indexOf( '?adlayers_debug' ) );
	}

	// Determines if the enabled ad server has implemented a function
	AdLayersAPI.prototype.functionExists = function( name ) {
		return ( null !== this.adServer && name in this.adServer );
	}

	// Automatically enable debug mode if the URL parameter is present
	$( document ).ready(function() {
		if ( AdLayersAPI.isDebug() ) {
			var adLayers = new AdLayersAPI();
			adLayers.debug();
		}
	});
})( jQuery );
