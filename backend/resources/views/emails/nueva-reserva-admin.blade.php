Nueva reserva recibida

Localizador: {{ $reserva->localizador }}
Nombre:      {{ $reserva->nombre }}
Email:       {{ $reserva->email }}
Fecha:       {{ $reserva->fecha?->format('d/m/Y') }}
Hora:        {{ substr((string) $reserva->hora, 0, 5) }}
Personas:    {{ $reserva->personas }}
Estado:      {{ ucfirst($reserva->estado) }}
@if ($reserva->notas)

Notas del cliente:
{{ $reserva->notas }}
@endif

Revisa el panel de administración para confirmarla.
