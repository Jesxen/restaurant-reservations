¡Hola {{ $entry->nombre }}!

Buenas noticias: se ha liberado una mesa para la fecha y hora que querías.

Fecha:    {{ $entry->fecha?->format('d/m/Y') }}
Hora:     {{ substr((string) $entry->hora, 0, 5) }}
Personas: {{ $entry->personas }}

Las plazas son limitadas y se asignan por orden de llegada, así que te
recomendamos completar tu reserva cuanto antes desde nuestra web.

¡Te esperamos!
