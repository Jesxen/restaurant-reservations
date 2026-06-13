¡Hola {{ $reserva->nombre }}!

@if ($reserva->estado === 'confirmada')
Tu reserva ha sido CONFIRMADA. ¡Te esperamos!
@else
Lamentamos informarte de que tu reserva ha sido CANCELADA.
@endif

Localizador: {{ $reserva->localizador }}
Fecha:       {{ $reserva->fecha?->format('d/m/Y') }}
Hora:        {{ substr((string) $reserva->hora, 0, 5) }}
Personas:    {{ $reserva->personas }}

@if ($reserva->estado === 'cancelada')
Si crees que se trata de un error o quieres reservar de nuevo,
ponte en contacto con nosotros indicando tu localizador.
@endif

Gracias por tu confianza.
