
/*
 *  Javascript associated with googlemaps plugin
 */


function in_array(needle, haystack) {
  for (var i=0; i<haystack.length; i++)
    if (haystack[i] == needle) return true;

  return false;
}

// Creates a marker at the given point with the given number label
// from http://www.google.com/apis/maps/documentation/#Display_Info_Windows_Above_Markers
// with minor modifications
function create_marker(point, text) {
  var marker = new GMarker(point);
  GEvent.addListener(marker, "click", function() {
    marker.openInfoWindowHtml(text);
  });
  return marker;
}

function init_googlemaps() {

  // nothing to do?
  if (googlemap.length == 0) return;

  var maptypes = { map : G_NORMAL_MAP,
                   normal : G_NORMAL_MAP,
                   hybrid : G_HYBRID_MAP,
                   satellite : G_SATELLITE_MAP
                 };

  // retrieve all google map containers
  var nodes = document.body.getElementsByTagName('div');

  var i=0;
  for (var j=0; j<nodes.length; j++) {
    if (nodes[j].className.match(/\bgooglemap\b/)) {
      googlemap[i++].node = nodes[j];
    }
  }

  // iterate through all the map containers and set up each map
  for (i=0; i<googlemap.length; i++) {
    googlemap[i].map = new GMap2(googlemap[i].node);

    with (googlemap[i]) {
      if (controls == 'on') {
        map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());
      }
      map.setCenter(new GLatLng(lat, lon), zoom);  

      var supported = map.getMapTypes();
      var requested = maptypes[type];

      map.setMapType(in_array(requested,supported) ? requested : supported[0]);

      if (googlemap[i].overlay && overlay.length > 0) {
        for (j=0; j<overlay.length; j++) {
          map.addOverlay(create_marker(new GLatLng(overlay[j].lat,overlay[j].lon),overlay[j].txt));
        }
      }
      if (kml != 'off') {
        var geoXml = new GGeoXml(kml);
        map.addOverlay(geoXml);
      }
      
    }
  }


  addEvent(document.body, 'unload', GUnload);
}


var googlemap = new Array();
addInitEvent(init_googlemaps);
