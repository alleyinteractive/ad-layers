import './style.scss';

// TODO: Refactor to remove jQuery dependency.
/* eslint-disable func-names, no-undef */
(function ($) {
  /*
  The AdLayersAPI class provides a suite of functions for manipulating ads on the client side.
  This abstracts the functionality from the specific ad server in use.
  Each ad server will be required to provide functionality for the below functions.
  If any of the functions do not exist, they will simply do nothing.
  */
  AdLayersAPI = function () {
    // Create an object for the active ad server
    this.adServer = null;

    // Create an instance of the ad server class, if it exists
    if (typeof adLayersAdServer.jsAPIClass === 'string' && typeof window[adLayersAdServer.jsAPIClass] === 'function') {
      this.adServer = new window[adLayersAdServer.jsAPIClass]();
    }
  };

  // Refreshes a specific ad unit
  AdLayersAPI.prototype.refresh = function (adUnit) {
    if (this.functionExists('refresh')) {
      this.adServer.refresh(adUnit);
    }
  };

  // Refreshes all ad units
  AdLayersAPI.prototype.refreshAll = function () {
    if (this.functionExists('refreshAll')) {
      this.adServer.refreshAll();
    }
  };

  // Lazy load an ad
  AdLayersAPI.prototype.lazyLoadAd = function (args) {
    if (this.functionExists('lazyLoadAd')) {
      return this.adServer.lazyLoadAd(args);
    }
    return false;
  };

  // Enables debug mode
  AdLayersAPI.prototype.debug = function () {
    if (this.functionExists('debug')) {
      this.adServer.debug();
    }
  };

  // Determines if debug mode has been specified
  AdLayersAPI.isDebug = function () {
    return (window.location.href.indexOf('?adlayers_debug') !== -1);
  };

  // Determines if the enabled ad server has implemented a function
  AdLayersAPI.prototype.functionExists = function (name) {
    return (this.adServer !== null && name in this.adServer);
  };

  // Automatically enable debug mode if the URL parameter is present
  $(document).ready(() => {
    if (AdLayersAPI.isDebug()) {
      const adLayers = new AdLayersAPI();
      adLayers.debug();
    }
  });
}(jQuery));
