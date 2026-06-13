¡Hola {{ $reserva->nombre }}!

Te recordamos que tienes una reserva con nosotros mañana:

Localizador: {{ $reserva->localizador }}
Fecha:       {{ $reserva->fecha?->format('d/m/Y') }}
Hora:        {{ substr((string) $reserva->hora, 0, 5) }}
Personas:    {{ $reserva->personas }}

Si no puedes acudir, por favor cancela tu reserva con antelación
indicándonos tu localizador. ¡Te esperamos!
