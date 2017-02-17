(function($) {
  var markers = [];
  var imagesPath = '/themes/custom/ample/images/';

  $.getJSON('/points', function(data) {
    for (var i = 0; i < data.features.length; i++) {
      var lat = data.features[i].geometry.coordinates[0];
      var lng = data.features[i].geometry.coordinates[1];
      var markerInfo = data.features[i].properties.description;
      var countryName = data.features[i].properties.name.toLowerCase();

      markers.push({'lat': lat, 'lng': lng, 'countryId': countryName});

      $('#map').after(markerInfo);
    }

    initMap();
  });

  function initMap() {
    var lightColoredMap = new google.maps.StyledMapType(lightStyles, {name: "Light colored"});
    var darkColoredMap = new google.maps.StyledMapType(darkStyles, {name: "Dark colored"});
    var mapCenter = {lat: 37.4430681, lng: 31.3592876};
    var mapOptions = {
      zoom: 3,
      center: mapCenter,
      scrollwheel: false,
      zoomControl: false,
      streetViewControl: false,
      mapTypeControlOptions: {
        mapTypeIds: ['dark_style', 'light_style'],
        position: google.maps.ControlPosition.RIGHT_BOTTOM
      }
    };

    var map = new google.maps.Map(document.getElementById('map'), mapOptions);
    map.mapTypes.set('light_style', lightColoredMap);
    map.setMapTypeId('light_style');
    map.mapTypes.set('dark_style', darkColoredMap);
    map.setMapTypeId('dark_style');

    var infoWindow = new google.maps.InfoWindow();
    var i = 0;
    var interval = setInterval(function() {
      var data = markers[i];
      var countryId = data.countryId;
      var myLatlng = new google.maps.LatLng(data.lat, data.lng);
      var marker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        icon: imagesPath + 'pin.svg',
        animation: google.maps.Animation.DROP,
        id: countryId
      });

      google.maps.event.addListener(marker, 'mouseover', function() {
        this.setIcon = imagesPath + 'calendar-icon.svg';
      });

      (function(marker, data) {
        google.maps.event.addListener(marker, "click", function(e) {
          //infoWindow.setContent(data.description);
          //infoWindow.open(map, marker);
          var elementId = $('#' + this.id);
          var markerInfo = $('.marker-info');

          if (!elementId.is('.active')) {
            markerInfo.removeClass('active');
            elementId.addClass('active');
          }
          else {
            elementId.removeClass('active');
          }
        });
      })(marker, data);

      i++;

      if (i == markers.length) {
        clearInterval(interval);
      }
    }, 300);
  }
})(jQuery);
