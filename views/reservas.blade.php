@extends('layout')

@section('title', 'Reservas')

@section('content')
<div class="max-w-lg">
  <h2 class="text-3xl font-bold mb-6 text-gray-900">Haz tu reserva</h2>
  <p class="text-gray-600 mb-6">Selecciona tus datos y asegura tu mesa con nosotros.</p>

  <form id="formReserva" class="space-y-5">
    <!-- Nombre -->
    <div>
      <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
      <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required
             class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
    </div>

    <!-- Correo -->
    <div>
      <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
      <input type="email" id="email" name="email" placeholder="Tu correo" required
             class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
    </div>

    <!-- Fecha -->
    <div>
      <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
      <input type="date" id="fecha" name="fecha" required
             class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
    </div>

    <!-- Hora -->
    <div>
      <label for="hora" class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
      <input type="time" id="hora" name="hora" required
             class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
    </div>

    <!-- Personas -->
    <div>
      <label for="personas" class="block text-sm font-medium text-gray-700 mb-1">Número de personas</label>
      <input type="number" id="personas" name="personas" min="1" required
             class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400">
    </div>

    <!-- Botón para reservar-->
    <button type="submit"
            class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 py-3 rounded-lg font-semibold shadow-md transition">
      Reservar
    </button>
  </form>

  <!-- Mensaje devuelto-->
  <div id="resultado" class="mt-6 text-green-600 font-semibold"></div>
</div>

<script src="reservas.js"></script>
@endsection