import './style.scss';

// TODO: Refactor to remove jquery dependency.
/* eslint-disable func-names, no-undef, no-array-constructor, no-param-reassign, consistent-return, prefer-destructuring, max-len */
(function ($) {
  /*
  The AdLayersDFPAPI class implements functionality specific to DFP For the AdLayersAPI.
  */
  AdLayersDFPAPI = function () {};

  // Refreshes a specific ad unit
  AdLayersDFPAPI.prototype.refresh = function (adUnit) {
    if (typeof dfpAdUnits[adUnit] !== 'undefined') {
      googletag.pubads().refresh([dfpAdUnits[adUnit]]);
    }
  };

  // Refreshes all ad units
  AdLayersDFPAPI.prototype.refreshAll = function () {
    if ($.isEmptyObject(dfpAdUnits) === false) {
      // DFP needs a numerical indexed array
      const unitsToRefresh = new Array();
      for (const adUnit in dfpAdUnits) {
        if (dfpAdUnits[adUnit]) {
          unitsToRefresh.push(dfpAdUnits[adUnit]);
        }
      }
      googletag.pubads().refresh(unitsToRefresh);
    }
  };

  AdLayersDFPAPI.prototype.buildAd = function (slotName, path, sizes, targets, sizeMapping) {
    if (AdLayersAPI.isDebug()) {
      let adSizes = [];

      if (sizeMapping) {
        // Get the appropriate sizes for this breakpoint
        let maxWidth = -1;
        let maxHeight = -1;
        $.each(sizeMapping, (index, value) => {
          if ($(window).width() >= value[0][0]
            && $(window).height() >= value[0][1]
            && value[0][0] >= maxWidth
            && value[0][1] >= maxHeight
          ) {
            maxWidth = value[0][0];
            maxHeight = value[0][1];
            adSizes = value[1];
          }
        });
      } else if (sizes && sizes[0]) {
        // Ensure sizes is a two-dimensional array
        if (!sizes[0][0]) {
          sizes = [sizes];
        }
        adSizes = sizes;
      }

      AdLayersDFPAPI.addDebugPlaceholder($(`#${adLayersDFP.adUnitPrefix}${slotName}`), adSizes);
    } else {
      return googletag.cmd.push(() => {
        let key;
        let value;
        const divId = adLayersDFP.adUnitPrefix + slotName;
        dfpAdUnits = dfpAdUnits || {};
        dfpAdUnits[slotName] = googletag.defineSlot(path, sizes, divId);
        if (targets) {
          for (key in targets) {
            if (targets[key]) {
              value = targets[key];
              dfpAdUnits[slotName].setTargeting(key, value);
            }
          }
        }
        if (sizeMapping) {
          dfpAdUnits[slotName].defineSizeMapping(sizeMapping);
        }
        dfpAdUnits[slotName].addService(googletag.pubads());
        googletag.display(divId);
      });
    }
  };

  AdLayersDFPAPI.prototype.lazyLoadAd = function (args) {
    if (!args.slotName) {
      return;
    }

    if (args.format) {
      if (!(dfpAdDetails && dfpAdDetails[args.format])) {
        return;
      }
      if (!args.path) {
        args.path = dfpAdDetails[args.format].path;
      }
      if (!args.sizes) {
        args.sizes = dfpAdDetails[args.format].sizes;
      }
      if (!args.targeting) {
        args.targeting = dfpAdDetails[args.format].targeting;
      }
      if (!args.sizeMapping) {
        if (dfpBuiltMappings && dfpBuiltMappings[args.format]) {
          args.sizeMapping = dfpBuiltMappings[args.format];
        } else {
          args.sizeMapping = null;
        }
      }
    }
    return this.buildAd(args.slotName, args.path, args.sizes, args.targeting, args.sizeMapping);
  };

  // Switches sizes in debug mode
  AdLayersDFPAPI.swapSizes = function ($size) {
    // Unselect all other sizes and set this one
    $size.siblings().removeClass('selected');
    $size.addClass('selected');

    // Set the width and height
    $size.parents('.dfp-ad').width($size.data('width'));
    $size.parents('.dfp-ad').height($size.data('height'));

    // Center the debug container vertically
    $size.parents('.dfp-debug-container').css({
      top: ($size.data('height') - $size.parents('.dfp-debug-container').outerHeight()) / 2,
    });
  };

  AdLayersDFPAPI.addDebugPlaceholder = function ($adDiv, adSizes) {
    // Get the ad slot sizes for the current breakpoint
    const adSlot = $adDiv.data('adUnit');

    // Set the background
    $adDiv.addClass('dfp-debug');

    // Create a container for the ad data
    $container = $('<div>')
      .addClass('dfp-debug-container');

    // Add a label
    $label = $('<div>')
      .addClass('dfp-debug-unit')
      .text(adSlot);
    $container.append($label);

    // Add additional sizes for selection
    $.each(adSizes, (index, value) => {
      $link = $('<a>')
        .attr('href', '#')
        .data('width', value[0])
        .data('height', value[1])
        .text(`${value[0]}x${value[1]}`)
        .addClass('dfp-debug-size');

      $container.append($link);
    });

    // Add to the ad div
    $adDiv.append($container);

    // Set to the first size
    AdLayersDFPAPI.swapSizes($adDiv.find('a').first());
  };

  // Enables debug mode
  AdLayersDFPAPI.prototype.debug = function () {
    // Iterate through all of the ad units and display them in debug mode
    $('.dfp-ad').each(function () {
      const $adDiv = $(this);
      const adSlot = $adDiv.data('adUnit');

      if (adSlot && dfpSizeMapping[adSlot] !== 'undefined') {
        // Get the appropriate sizes for this breakpoint
        let adSizes = [];
        let maxWidth = -1;
        let maxHeight = -1;
        $.each(dfpSizeMapping[adSlot], (index, value) => {
          if ($(window).width() >= value[0][0]
            && $(window).height() >= value[0][1]
            && value[0][0] >= maxWidth
            && value[0][1] >= maxHeight
          ) {
            maxWidth = value[0][0];
            maxHeight = value[0][1];
            adSizes = value[1];
          }
        });

        AdLayersDFPAPI.addDebugPlaceholder($(this), adSizes);
      }
    });

    // Add a debug bar with general layer information and a DFP console toggle
    $layerTitle = $('<div>')
      .addClass('dfp-ad-layer')
      .text(`${adLayersDFP.layerDebugLabel}: ${dfpAdLayer.title}`);

    $googleConsole = $('<a>')
      .addClass('dfp-console')
      .attr('href', window.location.href.replace('adlayers_debug', 'googfc'))
      .text(adLayersDFP.consoleDebugLabel);

    $debugBar = $('<div>')
      .attr('id', 'dfp-debug-bar')
      .addClass('dfp-debug')
      .append($layerTitle)
      .append($googleConsole);

    $('body').append($debugBar);

    // If the WordPress admin bar exists, push it down
    if ($('#wpadminbar').length) {
      $('#dfp-debug-bar').css('top', '32px');
    }
  };

  // Handle click actions for swapping ad unit sizes
  $(document).ready(() => {
    $('body').on('click', 'a.dfp-debug-size', function (e) {
      e.preventDefault();
      AdLayersDFPAPI.swapSizes($(this));
    });
  });
}(jQuery));
