A simple and elegant web application for managing restaurant reservations. Built with **PHP**, **MySQL**, **Blade**, **TailwindCSS**, and **JavaScript**, it offers a modern, user-friendly experience.

---

## Features

* Dynamic reservation form with instant confirmation
* Reservation listing and management
* Interactive map showing the restaurant location
* Responsive design with TailwindCSS
* Duplicate reservation prevention

---

## Files Overview

* **reservaController.php** – Handles form submissions and inserts reservations into the database.
* **reservas.js** – Sends form data dynamically via POST and displays confirmation without page reload.
* **index.php** – Loads views based on URL: reservation form, listings, or map.
* **validarReserva.php** – Validates the form to prevent duplicate reservations.

### Blade Templates

* **layout.blade.php** – Main layout with header, navigation, content area, image gallery, and footer.
* **listado.blade.php** – Shows all reservations in a table.
* **mapa.blade.php** – Displays the restaurant location using Leaflet.
* **reservas.blade.php** – Reservation form with modern styling and dynamic submission.
