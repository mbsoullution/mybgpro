(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.mybgCityMap = {
    attach(context) {
      once('mybg-city-map', '#mybg-city-map', context).forEach((element) => {
        const settings = drupalSettings.mybgMatrixMap || {};
        const center = settings.center || { lat: 49.5467, lng: 30.8744 };
        const markers = settings.markers || [];

        const loadLeaflet = () => {
          if (typeof L === 'undefined') {
            return;
          }
          const map = L.map(element).setView([center.lat, center.lng], 13);
          L.tileLayer(settings.tileUrl, {
            attribution: settings.attribution,
            maxZoom: 19,
          }).addTo(map);

          markers.forEach((marker) => {
            L.marker([marker.lat, marker.lng])
              .addTo(map)
              .bindPopup(`<a href="${marker.url}">${marker.title}</a>`);
          });
        };

        if (typeof L !== 'undefined') {
          loadLeaflet();
          return;
        }

        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(css);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = loadLeaflet;
        document.head.appendChild(script);
      });
    },
  };
})(Drupal, drupalSettings, once);
