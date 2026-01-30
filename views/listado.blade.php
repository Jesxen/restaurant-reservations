@extends('layout')

@section('title', 'Listado de reservas')

@section('content')
<div>
  <h2 class="text-3xl font-bold mb-6 text-gray-900">Reservas realizadas</h2>
  <p class="text-gray-600 mb-6">Aquí puedes consultar todas las reservas registradas en el sistema.</p>

  <div class="overflow-x-auto">
    <table class="w-full text-left bg-white border border-gray-200 rounded-xl shadow-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-3 font-semibold text-gray-700"> Usuario</th>
          <th class="px-4 py-3 font-semibold text-gray-700"> Fecha</th>
          <th class="px-4 py-3 font-semibold text-gray-700"> Hora</th>
          <th class="px-4 py-3 font-semibold text-gray-700"> Personas</th>
        </tr>
      </thead>
      <tbody>
        @foreach($reservas as $r)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 border-t border-gray-200">{{ $r['usuario'] }}</td>
          <td class="px-4 py-3 border-t border-gray-200">{{ $r['fecha'] }}</td>
          <td class="px-4 py-3 border-t border-gray-200">{{ $r['hora'] }}</td>
          <td class="px-4 py-3 border-t border-gray-200">{{ $r['personas'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
