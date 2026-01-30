
document.getElementById("formReserva").addEventListener("submit", async (e) => {

  e.preventDefault();

  // Recogemos todos los datos del formulario
  const datos = new FormData(e.target);

  //enviamos los datos del controlador con fetch
  const res = await fetch("../src/ReservaController.php", {
    method: "POST",
    body: datos
  });

  // Convertimos la respuesta en texto
  const texto = await res.text();

  // Con la constante que creamos con el texto, lo inserta en el campo con la id resultado
  document.getElementById("resultado").innerHTML = texto;
});
