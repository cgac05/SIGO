Especificaciones Técnicas: Nueva Pantalla de Bienvenida SIGO

Este documento detalla los requerimientos para sustituir la vista welcome.blade.php por una interfaz institucional moderna para el sistema SIGO.

1. Concepto Visual y Estructura

La interfaz debe ser totalmente responsiva y utilizar la paleta de colores institucional (Guinda, Blanco y Dorado/Arena). Se debe estructurar en las siguientes secciones:

Hero Section: Bienvenida impactante con llamado a la acción (CTA) para Login/Registro.

¿Quiénes Somos?: Descripción del INJUVE Nayarit.

Misión y Objetivos: Qué hace la institución por los jóvenes.

Sección de Apoyos: Invitación dinámica a integrarse a los programas SIGO.

Contacto, Ubicación y Redes: Datos oficiales, mapa conceptual y canales de comunicación.

2. Contenido Institucional (Referenciado APA)

Descripción e Identidad

El Instituto Nayarita de la Juventud (INJUVE) es un organismo público descentralizado que funge como el ente rector de las políticas públicas destinadas al desarrollo integral de la población joven en el estado de Nayarit (Ley del Instituto Nayarita de la Juventud, 2004). Su objetivo primordial es diseñar y ejecutar acciones que propicien la superación física, intelectual y económica de los jóvenes, garantizando su incorporación plena al desarrollo estatal (Gobierno del Estado de Nayarit, 2024).

Ubicación y Contacto

Dirección: Calle Jiquilpan No. 137, Colonia Lázaro Cárdenas, C.P. 63190, Tepic, Nayarit.

Teléfono: 311 169 3151.

Horario de Atención: Lunes a viernes de 8:00 AM a 4:00 PM.

Correo Electrónico: direccion.injuve.nay@gmail.com.

Redes Sociales Oficiales

Como parte de la estrategia de vinculación digital del instituto (Gobierno del Estado de Nayarit, 2024), se deben integrar los siguientes canales oficiales en el pie de página:

Facebook: INJUVE Nayarit

Instagram: @injuvenayarit

X (Twitter): @INJUVENayarit

3. Requerimientos de Diseño (Tailwind CSS)

Hero Section

Título: "Bienvenido a SIGO: Tu Portal de Apoyos Juveniles".

Subtítulo: "Transformando el presente de la juventud nayarita".

Botones: "Iniciar Sesión" (Primary) y "Regístrate ahora" (Outline/Secondary).

Sección "Nuestro Impacto"

Utilizar iconos para representar las áreas de atención: Empleo, Educación, Cultura y Salud.

Incluir un contador dinámico (mockup) de "Jóvenes Beneficiados".

Invitación a Apoyos (Sección SIGO)

Texto: "¿Tienes entre 12 y 29 años? SIGO es la herramienta diseñada para que accedas de forma transparente a los apoyos económicos y en especie que el Gobierno de Nayarit tiene para ti."

Pie de Página (Footer)

Fondo en color institucional Guinda (bg-[#611232] o similar).

Logotipos del Gobierno del Estado de Nayarit e INJUVE en versiones simplificadas/blancas.

Bloque de Redes Sociales: Iconos minimalistas (Lucide o FontAwesome) alineados a la derecha o al centro, con efectos de opacidad al pasar el cursor.

Enlaces legales: Aviso de Privacidad y Términos de Servicio.

4. Instrucciones para la IA de Desarrollo

Framework: Utilizar componentes de Laravel Breeze (x-guest-layout) si están disponibles para mantener la consistencia.

Imágenes: Usar placeholders de alta calidad relacionados con jóvenes estudiantes y emprendedores en Nayarit.

Navegación: Mantener los enlaces de auth.login y auth.register visibles en el Navbar superior.

Interactividad: Agregar efectos de hover suaves en las tarjetas de información y transiciones de entrada.

5. Referencias Bibliográficas (APA)

Gobierno del Estado de Nayarit. (2024). Descripción ciudadana - Registro de trámites y servicios: Instituto Nayarita de la Juventud. https://tramites.nayarit.gob.mx/ciudadano/ficha/535

Ley del Instituto Nayarita de la Juventud. (2004). Periódico Oficial del Estado de Nayarit. http://transparenciafiscal.tepic.gob.mx/docs/leyes/49_instituto_juventud.pdf

6. Preguntas para el Usuario (Desarrollador)

¿Deseas que la pantalla incluya una sección de "Noticias de última hora" o avisos sobre convocatorias cerrando pronto?

¿Tienes imágenes reales de las instalaciones o de eventos del INJUVE para sustituir los placeholders?

¿Prefieres que el mapa de ubicación sea un enlace directo a Google Maps o una integración de API de mapas estática?