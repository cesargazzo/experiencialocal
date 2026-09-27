// Datos demostrativos del prototipo. En una versión real vendrían de la API.
window.ANFITRION = {
  planes: [
    { id: "free", nombre: "Free", tipo: "Para probar", precio: 0, comision: 0.18,
      items: ["Perfil público", "Una experiencia activa", "Reservas y pagos", "Calificaciones"] },
    { id: "impulso", nombre: "Impulso", tipo: "Para crecer", precio: 19900, comision: 0.12, destacado: true,
      items: ["Hasta cinco experiencias", "Mejor posición en búsquedas", "Estadísticas", "Fechas y cupos recurrentes"] },
    { id: "pro", nombre: "Pro", tipo: "Para profesionales", precio: 49900, comision: 0.08,
      items: ["Experiencias sin límite", "Calendario y equipo", "Soporte prioritario", "Facturación integrada"] }
  ],

  categorias: [
    { id: "todas", nombre: "Todas" },
    { id: "comida", nombre: "Comidas" },
    { id: "cocina", nombre: "Clases de cocina" },
    { id: "paseo", nombre: "Paseos y viajes" },
    { id: "taller", nombre: "Talleres" }
  ],

  experiencias: [
    {
      id: "sabores-riojanos",
      categoria: "comida",
      tipo: "Cocina regional",
      lugar: "La Rioja",
      titulo: "Sabores riojanos en el patio",
      resumen: "Empanadas, cabrito y sobremesa con recetas de familia.",
      descripcion: "Una cena en el patio de una casa de barrio, con recetas que pasaron por tres generaciones. Cocinamos juntos las empanadas, compartimos el cabrito al horno de barro y cerramos con dulces caseros y una guitarra si se da.",
      precio: 40000,
      duracion: "3 h 30",
      cupos: 8,
      rating: 4.9,
      opiniones: 28,
      anfitrion: { nombre: "Marta Quiroga", desde: 2024, bio: "Cocinera de familia, docente jubilada. Recibo en mi casa del barrio San Martín desde hace dos años." },
      imagen: "https://images.unsplash.com/photo-1529543544282-ea669407fca3?auto=format&fit=crop&w=1200&q=70",
      incluye: [["Recepción", "Vermú con aceitunas y quesos regionales"], ["Principal", "Empanadas riojanas y cabrito al horno de barro"], ["Postre", "Dulce de cayote con nuez y café de olla"], ["Bebida", "Vino torrontés de la zona, sin límite"]],
      fechas: ["Sáb 3 oct · 20:30", "Sáb 10 oct · 20:30", "Vie 16 oct · 21:00"],
      reviews: [
        { nombre: "Lucía P.", fecha: "Septiembre 2026", rating: 5, texto: "La comida increíble, pero lo mejor fue la charla. Nos fuimos a la una de la mañana." },
        { nombre: "Tomás R.", fecha: "Agosto 2026", rating: 5, texto: "Vinimos de Córdoba y fue el punto alto del viaje. Marta es una anfitriona de otra época." },
        { nombre: "Ana M.", fecha: "Agosto 2026", rating: 4, texto: "Muy buena experiencia. Las empanadas, de las mejores que probé." }
      ]
    },
    {
      id: "pastas-nonna",
      categoria: "cocina",
      tipo: "Clase de cocina italiana",
      lugar: "La Rioja",
      titulo: "Pastas de la nonna",
      resumen: "Preparación artesanal, cena compartida y una historia familiar.",
      descripcion: "Amasamos desde cero tallarines, ravioles y ñoquis con la receta de la nonna Rosa, que llegó de Calabria en 1952. Después cenamos lo que hicimos, con salsa de tomate de la huerta y vino de la casa.",
      precio: 35000,
      duracion: "4 h",
      cupos: 6,
      rating: 4.8,
      opiniones: 16,
      anfitrion: { nombre: "Giuliana Ferrero", desde: 2025, bio: "Nieta de inmigrantes calabreses. Doy clases de pasta en la cocina de mi abuela, que sigue siendo la misma." },
      imagen: "https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=1200&q=70",
      incluye: [["Clase", "Masa, relleno y corte de tres tipos de pasta"], ["Cena", "Lo que cocinamos, con salsas caseras"], ["Para llevar", "Recetario impreso y medio kilo de pasta fresca"], ["Bebida", "Vino de la casa y agua"]],
      fechas: ["Dom 4 oct · 11:00", "Dom 11 oct · 11:00", "Dom 18 oct · 11:00"],
      reviews: [
        { nombre: "Federico L.", fecha: "Septiembre 2026", rating: 5, texto: "Aprendí más en cuatro horas que en años de videos. Y la cena fue una fiesta." },
        { nombre: "Carla D.", fecha: "Julio 2026", rating: 5, texto: "Giuliana explica con paciencia y cariño. Volvería sin dudar." }
      ]
    },
    {
      id: "ceviche-criolla",
      categoria: "comida",
      tipo: "Cocina peruana",
      lugar: "La Rioja",
      titulo: "Ceviche y cocina criolla",
      resumen: "Una cena guiada para conocer ingredientes, técnicas y cultura.",
      descripcion: "Una mesa de seis pasos que recorre la cocina peruana: ceviche clásico, causa limeña, lomo saltado y suspiro a la limeña. Cada plato viene con su historia y con la explicación de qué ingredientes se consiguen acá y cuáles traigo de Lima.",
      precio: 45000,
      duracion: "3 h",
      cupos: 10,
      rating: 5.0,
      opiniones: 11,
      anfitrion: { nombre: "Rodrigo Salas", desde: 2025, bio: "Limeño, cocinero de profesión. Hace cinco años que vivo en La Rioja y extraño el ceviche, así que lo hago yo." },
      imagen: "https://images.unsplash.com/photo-1535399831218-d5bd36d1a6b3?auto=format&fit=crop&w=1200&q=70",
      incluye: [["Entrada", "Ceviche clásico y causa limeña"], ["Principal", "Lomo saltado y ají de gallina"], ["Postre", "Suspiro a la limeña"], ["Bebida", "Pisco sour de bienvenida y chicha morada"]],
      fechas: ["Vie 2 oct · 21:00", "Vie 9 oct · 21:00", "Sáb 17 oct · 21:00"],
      reviews: [
        { nombre: "Malena G.", fecha: "Septiembre 2026", rating: 5, texto: "El mejor ceviche que comí fuera de Perú. Rodrigo cuenta cada plato como si fuera un cuento." }
      ]
    },
    {
      id: "cuesta-de-miranda",
      categoria: "paseo",
      tipo: "Paseo de día completo",
      lugar: "Chilecito",
      titulo: "Cuesta de Miranda con almuerzo de campo",
      resumen: "Un día por la cuesta, la mina y una mesa larga en una finca.",
      descripcion: "Salimos temprano desde Chilecito, recorremos la Cuesta de Miranda con paradas para fotos y caminatas cortas, visitamos el cable carril y almorzamos en la finca de la familia de Pablo con productos de la huerta y asado al asador.",
      precio: 65000,
      duracion: "9 h",
      cupos: 12,
      rating: 4.9,
      opiniones: 34,
      anfitrion: { nombre: "Pablo Herrera", desde: 2024, bio: "Nací en Chilecito y conozco cada curva de la cuesta. Guía habilitado y productor de aceite de oliva." },
      imagen: "https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=1200&q=70",
      incluye: [["Traslado", "Camioneta desde Chilecito, ida y vuelta"], ["Recorrido", "Cuesta de Miranda y cable carril con guía"], ["Almuerzo", "Asado al asador y verduras de huerta en la finca"], ["Bebida", "Vino de bodega familiar y agua"]],
      fechas: ["Sáb 3 oct · 08:00", "Sáb 10 oct · 08:00", "Dom 18 oct · 08:00"],
      reviews: [
        { nombre: "Ignacio B.", fecha: "Septiembre 2026", rating: 5, texto: "El almuerzo en la finca es lo que uno se imagina cuando piensa en el norte. Impecable." },
        { nombre: "Sofía A.", fecha: "Agosto 2026", rating: 5, texto: "Pablo sabe de todo y no apura a nadie. El paisaje habla solo." }
      ]
    },
    {
      id: "pan-de-masa-madre",
      categoria: "taller",
      tipo: "Taller de panadería",
      lugar: "La Rioja",
      titulo: "Pan de masa madre en horno de barro",
      resumen: "Amasado, fermentación y horneado. Te llevás tu pan y tu masa madre.",
      descripcion: "Un taller de mañana para entender la masa madre sin misterio: alimentación, plegados, formado y horneado en horno de barro. Desayunamos con lo que salió del horno y te llevás un frasco de masa madre viva.",
      precio: 28000,
      duracion: "5 h",
      cupos: 8,
      rating: 4.7,
      opiniones: 22,
      anfitrion: { nombre: "Julieta Moreno", desde: 2025, bio: "Panadera autodidacta. Empecé en pandemia y hoy vivo de esto. Mi horno de barro lo construimos con mi papá." },
      imagen: "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=70",
      incluye: [["Taller", "Masa madre, plegados, formado y horneado"], ["Desayuno", "Pan recién salido, manteca y dulces caseros"], ["Para llevar", "Un pan y un frasco de masa madre activa"], ["Material", "Guía impresa con tiempos y temperaturas"]],
      fechas: ["Sáb 3 oct · 09:00", "Sáb 17 oct · 09:00"],
      reviews: [
        { nombre: "Ramiro C.", fecha: "Septiembre 2026", rating: 5, texto: "Por fin entendí la masa madre. Ya hice tres panes en casa y salieron bien." },
        { nombre: "Valentina S.", fecha: "Agosto 2026", rating: 4, texto: "Muy completo. Cinco horas que pasan volando." }
      ]
    },
    {
      id: "vendimia-bodega",
      categoria: "paseo",
      tipo: "Visita y cata",
      lugar: "Nonogasta",
      titulo: "Cata en bodega familiar al atardecer",
      resumen: "Recorrido por la viña, cata de cuatro vinos y picada entre los parrales.",
      descripcion: "Caminamos la viña con Elena, que la trabaja con su familia desde 1978. Probamos cuatro vinos directamente en la bodega y cerramos con una picada de quesos, fiambres y aceitunas de la zona mientras baja el sol.",
      precio: 32000,
      duracion: "3 h",
      cupos: 14,
      rating: 4.8,
      opiniones: 19,
      anfitrion: { nombre: "Elena Ruiz", desde: 2024, bio: "Tercera generación en la bodega. Hago los vinos con mi hermano y recibo a quienes quieran conocerlos." },
      imagen: "https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=1200&q=70",
      incluye: [["Recorrido", "Viña, bodega y sala de barricas"], ["Cata", "Cuatro vinos con explicación"], ["Picada", "Quesos, fiambres y aceitunas regionales"], ["Para llevar", "Una botella a elección con descuento"]],
      fechas: ["Vie 2 oct · 18:00", "Sáb 10 oct · 18:00", "Vie 16 oct · 18:00"],
      reviews: [
        { nombre: "Martín O.", fecha: "Septiembre 2026", rating: 5, texto: "El atardecer entre los parrales no tiene precio. Y el torrontés tampoco." }
      ]
    }
  ],

  testimonios: [
    { texto: "Empecé con una cena por mes para amigos de amigos. Hoy recibo tres veces por semana y es mi ingreso principal.", nombre: "Marta Quiroga", rol: "Anfitriona · Cocina regional" },
    { texto: "Lo que más valoro es que el cobro está resuelto. Yo me ocupo de cocinar, no de perseguir transferencias.", nombre: "Rodrigo Salas", rol: "Anfitrión · Cocina peruana" },
    { texto: "Reservé para sorprender a mi pareja y terminamos siendo amigos de la anfitriona. Eso no pasa en un restaurante.", nombre: "Lucía P.", rol: "Comensal · La Rioja" }
  ]
};
