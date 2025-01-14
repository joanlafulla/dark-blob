const seccionesPagina = new fullpage('#fullpage', {
	// ──────────────────────────────────────────────────
	//   :::::: Opciones Basicas
	// ──────────────────────────────────────────────────
	autoScrolling: false, // Se activa el scroll.
	fitToSection: false, // Acomoda el scroll automaticamente para que la seccion se muestre en pantalla.
	fitToSectionDelay: 300, // Delay antes de acomodar la seccion automaticamente.
	easing: 'easeInOutCubic', // Funcion de tiempo de la animacion.
	scrollingSpeed: 700, // Velocidad del scroll. Valores: en milisegundos.
	css3: true, // Si usara CSS3 o javascript.
	easingcss3: 'ease-out', // Curva de velocidad del efecto.
	loopBottom: false,

	// ──────────────────────────────────────────────────
	//   :::::: Barra de navegación
	// ──────────────────────────────────────────────────
	navigation: true, // Muesta la barra de navegación.
	menu: '#menu', // Menu de navegación.
	anchors: ['inicio', 'quiz', 'julia', 'works', 'joan', 'contactar'], // Anclas, las usamos para identificar cada seccion y poder acceder a ellas con el menu.
	navigationTooltips: ['Inicio', 'Quiz', 'Júlia', 'Works', 'Joan', 'Contacto'], // Tooltips que mostrara por cada boton.
	showActiveTooltip: false, // Mostrar tooltip activa.
	controlArrows: true, // Flechas del slide
	slidesNavigation: false, // Indicadores del slide

	afterLoad: function(origin, destination){
		if(destination.anchor == 'julia'){
			var julia = document.getElementById("sectionJulia");
			var hasClass = julia.classList.contains('active');
			var hasClass2 = julia.classList.contains('estoyactivo');
				if (hasClass) {
					//console.log("estoy activo");
					if (hasClass2) {
						//console.log("tengo el estoy activo");
					} else {
						julia.classList.add('estoyactivo');
						//console.log("NOoooo tengo el estoy activo y lo añado");
					}
				}
		}

		if(destination.anchor == 'joan'){
			var joan = document.getElementById("sectionJoan");
			var hasClass = joan.classList.contains('active');
			var hasClass2 = joan.classList.contains('estoyactivo');
				if (hasClass) {
					//console.log("estoy activo");
					if (hasClass2) {
						//console.log("tengo el estoy activo");
					} else {
						joan.classList.add('estoyactivo');
						//console.log("NOoooo tengo el estoy activo y lo añado");
					}
				}
		}
	},

	afterSlideLoad: function( section, origin, destination, direction, trigger){
		var loadedSlide = this;

		//first slide of the first section
		if(section.anchor == 'works' && destination.index == 1){
			var camisetas = document.getElementById("slideCamisetas");
			var hasClass = camisetas.classList.contains('active');
			var hasClass2 = camisetas.classList.contains('estoyactivocamisetas');
			if (hasClass) {
				//console.log("estoy activo camisetas");
				if (hasClass2) {
					//console.log("tengo el estoy activo camisetas");
				} else {
					camisetas.classList.add('estoyactivocamisetas');
					//console.log("NOoooo tengo el estoy activo camisetas y lo añado");
				}
			}
		}

		//second slide of the first section
		if(section.anchor == 'works' && destination.index == 2){
			var boxeo = document.getElementById("slideBoxeo");
			var hasClass = boxeo.classList.contains('active');
			var hasClass2 = boxeo.classList.contains('estoyactivoboxeo');
			if (hasClass) {
				//console.log("estoy activo boxeo");
				if (hasClass2) {
					//console.log("tengo el estoy activo boxeo");
				} else {
					boxeo.classList.add('estoyactivoboxeo');
					//console.log("NOoooo tengo el estoy activo boxeo y lo añado");
				}
			}
		}

		//third slide of the first section
		if(section.anchor == 'works' && destination.index == 3){
			var malapell = document.getElementById("slideMalapell");
			var hasClass = malapell.classList.contains('active');
			var hasClass2 = malapell.classList.contains('estoyactivomalapell');
			if (hasClass) {
				//console.log("estoy activo malapell");
				if (hasClass2) {
					//console.log("tengo el estoy activo malapell");
				} else {
					malapell.classList.add('estoyactivomalapell');
					//console.log("NOoooo tengo el estoy activo malapell y lo añado");
				}
			}
		}
	}
});