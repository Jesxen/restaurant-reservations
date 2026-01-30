@extends('layout')

@section('title', 'Mapa')

@section('content')
<div>
  
  <h2 class="text-3xl font-bold mb-6 text-gray-900">Ubicación del restaurante</h2>
 
  <p class="text-gray-600 mb-6">
    Encuéntranos en La Laguna y disfruta de una experiencia gastronómica única.
  </p>

  
  <div class="rounded-xl overflow-hidden border border-gray-200 shadow-md">
    <!-- Aquí Leaflet renderiza el mapa -->
    <div id="map" class="h-96 w-full"></div>
  </div>
</div>

<script>
  // Inicializamos el mapa centrado en La laguna
  var map = L.map('map').setView([28.4874, -16.3159], 15);


  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map);

  // Y añadimos un marcador en la ubicación del restaurante
  L.marker([28.4874, -16.3159]).addTo(map)
    .bindPopup('<b>Restaurante La Laguna</b><br>Tu experiencia gastronómica en Canarias.')
    .openPopup();
</script>
@endsection
