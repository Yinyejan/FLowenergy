<?php
/**
 * recibir_datos.php
 * 
 * Archivo de datos estáticos del proyecto Flownergy.
 * Contiene la información institucional, misión, visión y valores
 * que se muestran en las secciones públicas del sitio.
 * 
 * Incluido por views/index.php al inicio de cada carga.
 * Puedes editar los textos aquí sin tocar la vista.
 */

// ── Identidad del proyecto ────────────────────────────────────────────
$proyecto = [

    'nombre' => 'Flownergy',

    'tagline' => 'Energía que Fluye',

    'descripcion' => 'Sistema IoT de monitoreo eléctrico para micro y medianas empresas de Yumbo, Valle del Cauca. Detecta picos, bajos de consumo y genera alertas de mantenimiento preventivo usando ESP32 y PZEM-004T.',

    // ── EDITA AQUÍ TU MISIÓN ──────────────────────────────────────────
    'mision' => 'Brindar soluciones tecnológicas accesibles para que las microempresas de Yumbo y la región optimicen su consumo energético mediante monitoreo inteligente, previniendo daños mayores como incendios y reduciendo costos operativos.',

    // ── EDITA AQUÍ TU VISIÓN ─────────────────────────────────────────
    'vision' => 'Para el 2030, ser líderes en gestión de energía para micro y medianas empresas colombianas, integrando hardware de bajo costo, alta precisión e inteligencia predictiva que contribuya a la sostenibilidad industrial regional.',

    // ── VALORES DEL PROYECTO ──────────────────────────────────────────
    'valores' => [
        [
            'tag'  => 'Innovación',
            'desc' => 'Usamos ESP32 y PZEM-004T para monitoreo eléctrico de alta precisión a bajo costo.',
            'icono'=> '🔬',
        ],
        [
            'tag'  => 'Prevención',
            'desc' => 'Nuestras alertas anticipadas evitan daños, incendios y paros de producción.',
            'icono'=> '🛡️',
        ],
        [
            'tag'  => 'Accesibilidad',
            'desc' => 'Tecnología IoT al alcance de la microempresa colombiana.',
            'icono'=> '🤝',
        ],
        [
            'tag'  => 'Sostenibilidad',
            'desc' => 'Menos consumo innecesario significa menor impacto ambiental y más ahorro.',
            'icono'=> '🌿',
        ],
        [
            'tag'  => 'Precisión',
            'desc' => 'Datos reales del sensor PZEM-004T cada 5 minutos para decisiones críticas.',
            'icono'=> '📊',
        ],
    ],

    // ── ODS ATENDIDOS ─────────────────────────────────────────────────
    // Objetivos de Desarrollo Sostenible de la ONU
    'ods' => [
        [
            'num'    => '07',
            'titulo' => 'Energía asequible y no contaminante',
            'color'  => '#FCC30B',
            // EDITA AQUÍ TU ANÁLISIS DEL ODS 7
            'descripcion' => 'Flownergy facilita el uso eficiente de la energía eléctrica en la industria local, reduciendo el consumo innecesario y optimizando los procesos productivos.',
        ],
        [
            'num'    => '09',
            'titulo' => 'Industria, innovación e infraestructura',
            'color'  => '#FD6925',
            // EDITA AQUÍ TU ANÁLISIS DEL ODS 9
            'descripcion' => 'Promovemos la digitalización e innovación tecnológica en micro y medianas empresas de Yumbo, Valle del Cauca, democratizando el acceso a soluciones IoT industriales.',
        ],
        [
            'num'    => '11',
            'titulo' => 'Ciudades y comunidades sostenibles',
            'color'  => '#FD9D24',
            // EDITA AQUÍ TU ANÁLISIS DEL ODS 11
            'descripcion' => 'Contribuimos a que los negocios locales de Yumbo operen de manera más segura y sostenible, previniendo accidentes eléctricos que afectan a la comunidad.',
        ],
        [
            'num'    => '12',
            'titulo' => 'Producción y consumo responsables',
            'color'  => '#BF8B2E',
            // EDITA AQUÍ TU ANÁLISIS DEL ODS 12
            'descripcion' => 'Identificamos consumos anormales para que las empresas tomen decisiones basadas en datos, eliminando desperdicios y promoviendo procesos productivos más responsables.',
        ],
        [
            'num'    => '13',
            'titulo' => 'Acción por el clima',
            'color'  => '#3F7E44',
            // EDITA AQUÍ TU ANÁLISIS DEL ODS 13
            'descripcion' => 'Al reducir el consumo energético innecesario, reducimos la huella de carbono de las empresas participantes, contribuyendo a la lucha contra el cambio climático.',
        ],
    ],

    // ── EQUIPO ───────────────────────────────────────────────────────
    // Agrega o edita los integrantes aquí
    'equipo' => [
        [
            'nombre' => 'Alejandro Díaz Ramírez',
            'rol'    => 'Desarrollador Líder & IoT Engineer',
            'icono'  => '👨‍💻',
            'contacto' => 'alejandrodiazramirez2020@gmail.com',
        ],
        // Agrega más integrantes copiando el bloque anterior
    ],

    // ── HARDWARE ──────────────────────────────────────────────────────
    'hardware' => [
        ['nombre' => 'ESP32 WROOM-32',   'rol' => 'Microcontrolador + Bluetooth Classic SPP'],
        ['nombre' => 'PZEM-004T v3.0',   'rol' => 'Sensor: Voltaje, Corriente, Potencia, kWh, Hz, cosφ'],
        ['nombre' => 'LCD I2C 16x2',     'rol' => 'Display de valores en tiempo real'],
        ['nombre' => 'Módulo Relé 5V',   'rol' => 'Control de carga (corte automático por sobrecarga)'],
    ],

    // ── CONTACTO ──────────────────────────────────────────────────────
    'contacto' => [
        'whatsapp' => '573225200707',           // Número con código de país (57 = Colombia)
        'email'    => 'alejandrodiazramirez2020@gmail.com',
        'ciudad'   => 'Yumbo, Valle del Cauca, Colombia',
    ],
];
