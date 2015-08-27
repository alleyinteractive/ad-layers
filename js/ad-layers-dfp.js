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
})( jQuery );
