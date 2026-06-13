¡Hola {{ $reserva->nombre }}!

Hemos recibido tu solicitud de reserva. Estos son los datos:

Localizador: {{ $reserva->localizador }}
Fecha:       {{ $reserva->fecha?->format('d/m/Y') }}
Hora:        {{ substr((string) $reserva->hora, 0, 5) }}
Personas:    {{ $reserva->personas }}
Estado:      Pendiente de confirmación

Te avisaremos por correo en cuanto la confirmemos. Si necesitas
modificarla o cancelarla, indícanos tu localizador.

Gracias por elegirnos.
