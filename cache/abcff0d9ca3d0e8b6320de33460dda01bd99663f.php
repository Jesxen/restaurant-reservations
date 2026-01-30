<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo $__env->yieldContent('title', 'Restaurante'); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-white text-gray-900 antialiased">
  <!-- Header  -->
  <header class="border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <!-- Logo -->
      <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">RL</div>
        <span class="text-lg font-semibold tracking-wide">Restaurante La Laguna</span>
      </div>

      <!-- Menu-->
      <nav class="hidden md:flex items-center gap-6 text-sm">
        
        <a href="index.php?vista=listado" class="hover:text-gray-700">Listado de reservas</a>
        <a href="index.php?vista=mapa" class="hover:text-gray-700">Mapa</a>
      </nav>

      <!-- Reservar -->
      <div class="flex items-center gap-4">
        
        <a href="index.php?vista=reservas"
           class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm px-4 py-2 rounded transition">
          Reservar
        </a>
      </div>
    </div>
  </header>

  <!-- Introducción de homepage-->
  <section class="max-w-6xl mx-auto px-6 pt-10">
    <div class="md:w-2/3">
      <h1 class="text-3xl md:text-4xl font-bold leading-tight">
        Reservar mesa para disfrutar con nosotros<br/>
        una experiencia gastronómica única
      </h1>
      <p class="text-gray-600 mt-3">
        Selecciona fecha, horario y número de personas. Te confirmaremos la disponibilidad al instante.
      </p>
    </div>
  </section>

  <!-- Contenido principal -->
  <main class="max-w-6xl mx-auto px-6 py-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
      
      <div>
        <?php echo $__env->yieldContent('content'); ?>
        
        <div class="mt-6 border-t border-gray-200 pt-4">
          <h3 class="text-sm font-semibold text-gray-900 mb-2">Menú o experiencia disponible</h3>
          <p class="text-sm text-gray-600">Actualmente en nuestro restaurante</p>
        </div>
      </div>

      <!-- Sección de galeria de imagenes-->
      <div class="hidden md:block">
    <figure class="rounded-2xl overflow-hidden border border-gray-200 shadow-sm">
  <img id="imagenPostre" alt="Postre elegante"
       class="w-full h-96 object-cover opacity-0 translate-y-10 transition-all duration-1000 ease-out">
</figure>

<script>
  //Array de imagenes
  const imagenes = [
    "https://images.pexels.com/photos/376464/pexels-photo-376464.jpeg",
    "https://images.pexels.com/photos/70497/pexels-photo-70497.jpeg",
    "https://images.pexels.com/photos/958545/pexels-photo-958545.jpeg"
  ];

  let i = 0;
  const img = document.getElementById("imagenPostre");

  //Animación para cuando cargue la página
  window.addEventListener("load", () => {
    img.src = imagenes[0]; 
    setTimeout(() => {
      img.classList.remove("opacity-0", "translate-y-10");
      img.classList.add("opacity-100", "translate-y-0");
    }, 100); 
  });

  //Rotación de imagenes con su intervalo de tiempo
  function cambiarImagen() {
    
    setTimeout(() => {
      img.src = imagenes[i];
      i = (i + 1) % imagenes.length;
      img.style.opacity = 1; 
    }, 500);
  }

  setInterval(cambiarImagen, 5000); 
</script>

  </main>

  <!-- Footer -->
  <footer class="fixed bottom-0 w-full bg-white/90 backdrop-blur border-t border-gray-200">
    <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between text-sm text-gray-700">
      <span>© 2025 Restaurante La Laguna - Jesxen</span>
      <div class="flex items-center gap-4">
        <a href="#" class="hover:text-gray-900">Privacidad</a>
        <a href="#" class="hover:text-gray-900">Términos</a>
        <a href="#" class="hover:text-gray-900">Contacto</a>
      </div>
    </div>
  </footer>
</body>
</html>
<?php /**PATH C:\Apache24\htdocs\restaurant-app\views/layout.blade.php ENDPATH**/ ?>