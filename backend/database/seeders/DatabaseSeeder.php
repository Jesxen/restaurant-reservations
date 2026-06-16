<?php

namespace Database\Seeders;

use App\Models\BlackoutDate;
use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Plato;
use App\Models\Reserva;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: only seed an empty database. Lets the deploy run
        // `db:seed` on every boot without duplicating data or overwriting
        // changes made later from the admin panel.
        if (User::query()->exists()) {
            return;
        }

        // Remove any partial settings row created by migrations so the
        // singleton below is the only one.
        Setting::query()->delete();

        // --- Users ---------------------------------------------------------
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@laguna.com',
            'phone' => '+34 600 000 001',
            'role' => 'admin',
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Camarero Demo',
            'email' => 'staff@laguna.com',
            'phone' => '+34 600 000 003',
            'role' => 'staff',
            'password' => 'password',
        ]);

        $cliente = User::create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@laguna.com',
            'phone' => '+34 600 000 002',
            'role' => 'client',
            'password' => 'password',
        ]);

        User::factory(6)->create();

        // --- Settings (singleton) -----------------------------------------
        Setting::create([
            'nombre_restaurante' => 'Restaurante La Laguna',
            'aforo' => null, // derive from active tables
            'ticket_medio' => 38.00,

            // Booking config.
            'intervalo_slots' => 30,
            'antelacion_min_horas' => 1,
            'max_personas_online' => 20,
            'dias_cierre' => [1], // closed on Mondays

            // Branding.
            'logo_url' => null,
            'color_primario' => '#a16207',
            'color_acento' => '#3a2a1a',

            // Contact (values previously hardcoded in the app).
            'email_contacto' => 'reservas@laguna.com',
            'telefono' => '+34 922 000 000',
            'direccion' => 'Calle La Carrera, 1',
            'ciudad' => 'San Cristóbal de La Laguna, Tenerife',
            'lat' => 28.4874,
            'lng' => -16.3159,

            // Social.
            'instagram_url' => 'https://instagram.com/restaurantelaguna',
            'facebook_url' => 'https://facebook.com/restaurantelaguna',
            'tiktok_url' => null,

            // Gallery.
            'galeria' => [
                'https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg',
                'https://images.pexels.com/photos/70497/pexels-photo-70497.jpeg',
                'https://images.pexels.com/photos/958545/pexels-photo-958545.jpeg',
                'https://images.pexels.com/photos/1267320/pexels-photo-1267320.jpeg',
            ],
        ]);

        // --- Blackout dates -----------------------------------------------
        BlackoutDate::create([
            'fecha' => Carbon::create(Carbon::now()->year, 12, 25)->toDateString(),
            'motivo' => 'Navidad',
        ]);
        BlackoutDate::create([
            'fecha' => Carbon::create(Carbon::now()->year, 1, 1)->addYear()->toDateString(),
            'motivo' => 'Año Nuevo',
        ]);

        // --- Mesas ---------------------------------------------------------
        $capacidades = [2, 2, 2, 4, 4, 4, 4, 6, 6, 8];
        foreach ($capacidades as $i => $cap) {
            Mesa::create(['numero' => $i + 1, 'capacidad' => $cap, 'activa' => true]);
        }

        // --- Menú ----------------------------------------------------------
        $menu = [
            'Entrantes' => [
                ['Croquetas caseras de jamón', 'Cremosas croquetas de jamón ibérico (6 uds).', 8.50],
                ['Pulpo a la brasa', 'Pulpo con papas arrugadas y mojo picón.', 16.00],
                ['Ensalada canaria', 'Verduras de la huerta, aguacate y queso fresco.', 9.50],
            ],
            'Principales' => [
                ['Solomillo a la pimienta', 'Solomillo de ternera con salsa de pimienta verde.', 22.00],
                ['Cherne encebollado', 'Pescado local con cebolla confitada y papas.', 19.50],
                ['Risotto de setas', 'Arroz cremoso con setas de temporada y parmesano.', 15.00],
                ['Cochino negro', 'Presa de cochino negro canario a baja temperatura.', 21.00],
            ],
            'Postres' => [
                ['Bienmesabe', 'Postre tradicional canario de almendra.', 6.50],
                ['Tarta de queso', 'Tarta de queso al horno con frutos rojos.', 6.00],
                ['Coulant de chocolate', 'Bizcocho de chocolate con interior fundente.', 7.00],
            ],
            'Bebidas' => [
                ['Vino tinto D.O. Tacoronte', 'Copa de vino tinto canario.', 4.50],
                ['Agua mineral', 'Botella 50cl.', 2.00],
            ],
        ];

        $orden = 1;
        foreach ($menu as $nombreCat => $platos) {
            $categoria = Categoria::create(['nombre' => $nombreCat, 'orden' => $orden++, 'activa' => true]);
            foreach ($platos as [$nombre, $descripcion, $precio]) {
                Plato::create([
                    'categoria_id' => $categoria->id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'disponible' => true,
                ]);
            }
        }

        // --- Reservas ------------------------------------------------------
        // Some belonging to the demo client (so the client panel has data).
        Reserva::factory()->create([
            'user_id' => $cliente->id,
            'nombre' => $cliente->name,
            'email' => $cliente->email,
            'fecha' => Carbon::today()->addDays(2)->toDateString(),
            'hora' => '21:00',
            'personas' => 2,
            'estado' => 'confirmada',
        ]);
        Reserva::factory()->create([
            'user_id' => $cliente->id,
            'nombre' => $cliente->name,
            'email' => $cliente->email,
            'fecha' => Carbon::today()->subDays(5)->toDateString(),
            'hora' => '14:00',
            'personas' => 4,
            'estado' => 'completada',
        ]);

        // Assorted reservations (guests + random users), some today.
        Reserva::factory(10)->create();
        Reserva::factory(4)->create([
            'fecha' => Carbon::today()->toDateString(),
            'estado' => 'confirmada',
        ]);
    }
}
