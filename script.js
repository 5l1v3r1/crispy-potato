/**
 * Muestra los elementos que estaban escondidos.
 */
function showElems () {

	var content = $("#content");

	$(".main_title").addClass("change_to_small");
	$(".generated_by").addClass("change_to_small");
	$("body").addClass("different_bg");

	/* Permite que el contenido aparezca suavemente */
	$("#content").slideDown ("slow");
}

/**
 * Elimina los elementos que se habían añadido en showElems()
 */
function hideElems () {

	var content = $("#content");

	$(".main_title").removeClass("change_to_small");
	$(".generated_by").removeClass("change_to_small");
	$("body").removeClass("different_bg");

	/* El contenido se esconde barriendo hacia arriba */
	$("#content").slideUp ("slow");
}

/**
 * Si se pulsa la tecla de navegación (flecha arriba y flecha abajo)
 * o la barra espaciadora, se activa el evento para hacer la transición
 * para mostrar el contenido.
 */
$('html').bind('keydown', function (e) {

	/* Se pulsan la barra espaciadora (32) o la flecha hacia abajo (40) */
	if (e.keyCode == 32 || e.keyCode == 40){

		showElems ();

	} else if (e.keyCode == 38){

		/* Se pulsa la flecha hacia arriba (38), se vuelve a la pantalla inicial */
		hideElems ();
	}
});

// detecta el movimiento de la rueda
$('html').bind('mousewheel DOMMouseScroll', function (e) {

       	var delta = (e.originalEvent.wheelDelta || -e.originalEvent.detail),
	    pos = $(document).scrollTop ();

	if (delta < 0) {

		showElems ();

	} else if (delta > 0) {

		/* Sólo oculta los elementos si se encuentra en lo alto de la página */
		if (pos <= 1) {

			hideElems ();
		}
	}
});

