<?php
/**
 * views/index.php  — Router principal de Flownergy
 * 
 * Secciones disponibles (?ver=):
 *   inicio    — Landing pública
 *   nosotros  — Visión, misión, ODS del proyecto
 *   contacto  — WhatsApp y correo
 *   acceder   — Login
 *   dashboard — Panel privado (requiere sesión)
 *   medicion  — Conexión BT y medición en tiempo real (requiere sesión + máquina)
 */

session_start();
require_once(dirname(__DIR__) . '/models/MaquinaModel.php');
require_once(dirname(__DIR__) . '/models/MedicionModel.php');

// ── Router ────────────────────────────────────────────────────────────
$seccion     = $_GET['ver'] ?? 'inicio';
$error_login = isset($_GET['error']);
$registrado  = isset($_GET['registrado']);

// Protección de secciones privadas
$privadas = ['dashboard', 'medicion'];
if (in_array($seccion, $privadas) && !isset($_SESSION['usuario'])) {
    header('Location: ?ver=acceder');
    exit();
}

// Cargar datos del usuario autenticado
$maquina    = null;
$historico  = [];
$alertas_db = [];
$serial     = null;

if (isset($_SESSION['usuario'])) {
    $maquina = MaquinaModel::obtenerPorUsuario((int)$_SESSION['usuario']);
    if ($maquina) {
        $serial     = $maquina['Id_serial'];
        $historico  = MedicionModel::obtenerHistorico($serial, 24); // últimas 24h
        $alertas_db = MedicionModel::obtenerAlertas($serial, 5);
        $_SESSION['maquina_serial'] = $serial;
    }
}

// Datos del proyecto (mantenidos del diseño original)
$proyecto = [
    'nombre'  => 'Flownergy',
    'mision'  => 'Brindar soluciones tecnológicas accesibles para que las microempresas de Yumbo y la región optimicen su consumo energético mediante monitoreo inteligente, previniendo daños y reduciendo costos.',
    'vision'  => 'Para el 2030, ser líderes en gestión de energía para micro y medianas empresas, integrando hardware de bajo costo, alta precisión e inteligencia predictiva.',
    'valores' => [
        ['tag' => 'Innovación',    'desc' => 'ESP32 + PZEM-004T para monitoreo de precisión.'],
        ['tag' => 'Prevención',    'desc' => 'Alertas anticipadas evitan daños e incendios.'],
        ['tag' => 'Accesibilidad', 'desc' => 'Tecnología IoT al alcance de la microempresa.'],
        ['tag' => 'Sostenibilidad','desc' => 'Menos consumo = menos impacto ambiental.'],
    ],
    'ods' => [
        ['num' => '07', 'titulo' => 'Energía asequible y no contaminante',       'color' => '#FCC30B'],
        ['num' => '09', 'titulo' => 'Industria, innovación e infraestructura',   'color' => '#FD6925'],
        ['num' => '11', 'titulo' => 'Ciudades y comunidades sostenibles',         'color' => '#FD9D24'],
        ['num' => '12', 'titulo' => 'Producción y consumo responsables',          'color' => '#BF8B2E'],
        ['num' => '13', 'titulo' => 'Acción por el clima',                        'color' => '#3F7E44'],
    ],
];

// Preparar datos históricos para Chart.js (JSON embebido)
$chart_labels   = [];
$chart_potencia = [];
$chart_voltaje  = [];
$chart_fp       = [];
foreach ($historico as $h) {
    $chart_labels[]   = $h['intervalo'];
    $chart_potencia[] = (float)$h['potencia'];
    $chart_voltaje[]  = (float)$h['voltaje'];
    $chart_fp[]       = (float)$h['fp'];
}
$json_labels   = json_encode($chart_labels);
$json_potencia = json_encode($chart_potencia);
$json_voltaje  = json_encode($chart_voltaje);
$json_fp       = json_encode($chart_fp);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Flownergy — Monitor de Energía Industrial</title>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ================================================================
   Variables y reset
================================================================ */
:root {
  --bg:       #07090c;
  --bg2:      #0d1118;
  --card:     rgba(255,255,255,0.03);
  --border:   rgba(255,255,255,0.07);
  --gold:     #FFD700;
  --gold2:    #FFA500;
  --green:    #39ff14;
  --red:      #ff3b3b;
  --blue:     #00e5ff;
  --text:     #e8edf2;
  --muted:    #5a6a7a;
  --radius:   20px;
  --font:     'Outfit', sans-serif;
  --mono:     'Share Tech Mono', monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{
  font-family:var(--font);
  background:var(--bg);
  color:var(--text);
  min-height:100vh;
  overflow-x:hidden;
}
/* Cuadrícula de fondo sutil */
body::before{
  content:'';position:fixed;inset:0;pointer-events:none;
  background-image:
    linear-gradient(rgba(255,215,0,0.025) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,215,0,0.025) 1px,transparent 1px);
  background-size:60px 60px;
  z-index:0;
}

/* ── Layout ─────────────────────────────────────────────────────── */
.wrap{
  position:relative;z-index:1;
  width:min(calc(100% - 40px),1140px);
  margin:0 auto;
}

/* ── Animaciones ─────────────────────────────────────────────────── */
@keyframes flowGold{0%{color:var(--gold);}50%{color:var(--gold2);}100%{color:var(--gold);}}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:none;}}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.4;}}
@keyframes glow{0%,100%{box-shadow:0 0 10px rgba(255,215,0,.3);}50%{box-shadow:0 0 25px rgba(255,215,0,.7);}}

/* ── Header / Nav ────────────────────────────────────────────────── */
header{
  display:flex;align-items:center;justify-content:space-between;
  padding:24px 0;border-bottom:1px solid var(--border);
  margin-bottom:0;
}
.logo{
  font-size:1.7rem;font-weight:900;letter-spacing:-1px;
  animation:flowGold 5s infinite;cursor:default;
}
nav a{
  color:var(--text);text-decoration:none;
  margin-left:22px;font-size:.9rem;font-weight:600;
  opacity:.45;transition:.25s;
}
nav a:hover,nav a.active{opacity:1;color:var(--gold);}
nav .btn-nav{
  border:1px solid var(--gold);padding:7px 18px;
  border-radius:12px;opacity:1!important;color:var(--gold)!important;
}
.user-badge{
  font-size:.8rem;color:var(--muted);font-family:var(--mono);
  border:1px solid var(--border);padding:5px 12px;border-radius:8px;
}

/* ── Cards ───────────────────────────────────────────────────────── */
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:28px;
  animation:fadeUp .5s ease both;
}
.card-sm{padding:20px;}
.grid-3{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.grid-4{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;}
@media(max-width:700px){.grid-2{grid-template-columns:1fr;}}

/* ── Botones ─────────────────────────────────────────────────────── */
.btn{
  display:inline-block;padding:13px 28px;border:none;border-radius:14px;
  font-family:var(--font);font-size:1rem;font-weight:700;cursor:pointer;
  text-decoration:none;text-align:center;transition:.25s;letter-spacing:.5px;
}
.btn-gold{background:var(--gold);color:#000;}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,215,0,.3);animation:glow 2s infinite;}
.btn-outline{background:transparent;border:1.5px solid var(--gold);color:var(--gold);}
.btn-outline:hover{background:rgba(255,215,0,.08);}
.btn-red{background:#ff3b3b;color:#fff;}
.btn-red:hover{opacity:.85;}
.btn-blue{background:var(--blue);color:#000;}
.btn-blue:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,229,255,.3);}
.btn-green{background:var(--green);color:#000;}
.btn-sm{padding:8px 18px;font-size:.85rem;}
.btn-full{width:100%;}

/* ── Formularios ─────────────────────────────────────────────────── */
.input-group{margin-bottom:14px;}
.input-group label{
  display:block;font-size:.7rem;letter-spacing:2px;
  color:var(--gold);font-weight:700;margin-bottom:6px;
  text-transform:uppercase;
}
.input-group input,.input-group select{
  width:100%;background:rgba(255,255,255,.05);
  border:1px solid var(--border);border-radius:10px;
  color:var(--text);padding:11px 14px;font-size:.95rem;
  font-family:var(--font);outline:none;transition:.2s;
}
.input-group input:focus,.input-group select:focus{
  border-color:var(--gold);box-shadow:0 0 0 3px rgba(255,215,0,.1);
}

/* ── Alertas ─────────────────────────────────────────────────────── */
.alerta{
  display:flex;align-items:center;gap:10px;
  padding:12px 18px;border-radius:12px;margin-bottom:10px;
  font-size:.88rem;font-weight:600;
}
.alerta-red{background:rgba(255,59,59,.1);border:1px solid rgba(255,59,59,.3);color:#ff7070;}
.alerta-yellow{background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.25);color:var(--gold);}
.alerta-blue{background:rgba(0,229,255,.08);border:1px solid rgba(0,229,255,.2);color:var(--blue);}
.alerta-green{background:rgba(57,255,20,.08);border:1px solid rgba(57,255,20,.2);color:var(--green);}

/* ── Métricas en tiempo real ─────────────────────────────────────── */
.metric-card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:16px;padding:18px 20px;position:relative;overflow:hidden;
  transition:border-color .3s;
}
.metric-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:var(--mc-color,var(--gold));
}
.metric-card:hover{border-color:var(--mc-color,var(--gold));}
.metric-label{font-size:.65rem;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin-bottom:8px;}
.metric-value{
  font-size:2.2rem;font-weight:700;font-family:var(--mono);
  color:var(--mc-color,var(--gold));
  text-shadow:0 0 20px currentColor;
  transition:all .3s;
}
.metric-unit{font-size:.8rem;color:var(--muted);margin-top:3px;}
.metric-icon{position:absolute;right:16px;top:14px;font-size:1.6rem;opacity:.15;}

/* ── Estado BT ───────────────────────────────────────────────────── */
.bt-status{
  display:inline-flex;align-items:center;gap:8px;
  padding:7px 16px;border-radius:8px;font-family:var(--mono);font-size:.8rem;
  border:1px solid var(--border);background:var(--bg2);transition:.3s;
}
.bt-dot{width:8px;height:8px;border-radius:50%;background:var(--muted);}
.bt-status.on .bt-dot{background:var(--green);box-shadow:0 0 8px var(--green);animation:pulse 2s infinite;}
.bt-status.on{border-color:var(--green);color:var(--green);}
.bt-status.err{border-color:var(--red);color:var(--red);}
.bt-status.err .bt-dot{background:var(--red);}

/* ── Consola ─────────────────────────────────────────────────────── */
.console{
  font-family:var(--mono);font-size:.75rem;color:var(--muted);
  height:90px;overflow-y:auto;
  display:flex;flex-direction:column-reverse;
  background:var(--bg);border-radius:10px;padding:10px;
}
.console-line{padding:2px 0;border-bottom:1px solid rgba(255,255,255,.03);}
.console-ok{color:var(--green);}
.console-err{color:var(--red);}
.console-warn{color:var(--gold);}

/* ── Sección hero ────────────────────────────────────────────────── */
.hero{text-align:center;padding:90px 0 60px;}
.hero h1{font-size:clamp(3.5rem,8vw,7rem);font-weight:900;letter-spacing:-4px;line-height:.9;}
.hero .sub{opacity:.4;font-weight:300;margin-top:18px;font-size:1.05rem;}

/* ── ODS ─────────────────────────────────────────────────────────── */
.ods-badge{
  display:inline-flex;align-items:center;gap:10px;
  padding:10px 18px;border-radius:12px;font-weight:700;
  font-size:.85rem;
}

/* ── Sección título ──────────────────────────────────────────────── */
.sec-title{
  font-size:2.2rem;font-weight:900;letter-spacing:-1px;
  animation:flowGold 6s infinite;margin-bottom:6px;
}
.sec-sub{color:var(--muted);font-size:.95rem;margin-bottom:32px;}

/* ── Footer ──────────────────────────────────────────────────────── */
footer{
  border-top:1px solid var(--border);padding:24px 0;margin-top:60px;
  text-align:center;color:var(--muted);font-size:.82rem;
}
</style>
</head>
<body>

<!-- ================================================================
     HEADER
================================================================ -->
<div class="wrap">
<header>
  <div class="logo">FLOWNERGY</div>
  <nav>
    <a href="?ver=inicio"    class="<?= $seccion==='inicio'    ? 'active':'' ?>">Inicio</a>
    <a href="?ver=nosotros"  class="<?= $seccion==='nosotros'  ? 'active':'' ?>">Nosotros</a>
    <a href="?ver=contacto"  class="<?= $seccion==='contacto'  ? 'active':'' ?>">Contacto</a>
    <?php if(isset($_SESSION['usuario'])): ?>
      <a href="?ver=dashboard" class="<?= $seccion==='dashboard'?'active':'' ?>">Dashboard</a>
      <?php if($maquina): ?>
        <a href="?ver=medicion"  class="<?= $seccion==='medicion' ?'active':'' ?>">Medición</a>
      <?php endif; ?>
      <span class="user-badge">ID: <?= $_SESSION['usuario'] ?></span>
      <a href="../api/cerrar.php" class="btn-nav" style="margin-left:12px;">Salir</a>
    <?php else: ?>
      <a href="?ver=acceder" class="btn-nav">Acceder</a>
    <?php endif; ?>
  </nav>
</header>
</div>

<!-- ================================================================
     CONTENIDO PRINCIPAL
================================================================ -->
<main class="wrap" style="padding-top:40px;">

<?php /* ─────────────────────────────────────────────────────────────
         INICIO
────────────────────────────────────────────────────────────────── */ ?>
<?php if($seccion === 'inicio'): ?>

<section class="hero">
  <h1>Energía que<br><span style="animation:flowGold 3s infinite;display:inline-block">Fluye.</span></h1>
  <p class="sub">Control IoT con ESP32 y PZEM-004T para la industria de Yumbo.<br>
  Detección de picos, alertas de mantenimiento y prevención de incendios.</p>
  <div style="margin-top:36px;display:flex;justify-content:center;gap:14px;flex-wrap:wrap;">
    <a href="?ver=acceder" class="btn btn-gold">Iniciar sesión</a>
    <a href="?ver=nosotros" class="btn btn-outline">Conoce el proyecto</a>
  </div>
</section>

<div class="grid-3" style="margin-bottom:40px;">
  <div class="card">
    <h3 style="animation:flowGold 4s infinite;margin-bottom:12px;">⚡ Hardware</h3>
    <p style="opacity:.7;line-height:1.7;font-size:.92rem;">
      Monitoreo en tiempo real con <strong>ESP32 WROOM-32</strong> conectado vía Bluetooth
      y sensor <strong>PZEM-004T</strong> para medición precisa de voltaje, corriente,
      potencia, energía, frecuencia y factor de potencia.
    </p>
  </div>
  <div class="card">
    <h3 style="animation:flowGold 5s infinite;margin-bottom:12px;">🔔 Alertas Inteligentes</h3>
    <p style="opacity:.7;line-height:1.7;font-size:.92rem;">
      Detectamos <strong>picos y bajos de consumo</strong> comparando contra el historial.
      Alertas de voltaje fuera de rango y <strong>recomendaciones de mantenimiento preventivo</strong>
      antes de que ocurran fallas graves.
    </p>
  </div>
  <div class="card">
    <h3 style="animation:flowGold 6s infinite;margin-bottom:12px;">📊 Valores del Proyecto</h3>
    <ul style="font-size:.9rem;opacity:.7;padding-left:16px;line-height:2;">
      <?php foreach($proyecto['valores'] as $v): ?>
        <li><strong><?= $v['tag'] ?></strong> — <?= $v['desc'] ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- Bloque de beneficios -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:40px;">
  <?php
  $beneficios = [
    ['🔥','Prevención de incendios','Detecta sobrecargas y desactiva el relé automáticamente.'],
    ['📈','Historial de consumo','Gráficas de 5 minutos, exportable a CSV con filtro de fechas.'],
    ['🔧','Mantenimiento predictivo','Factor de potencia bajo = señal de mantenimiento necesario.'],
    ['💰','Ahorro energético','Identifica consumos anormales para tomar acción a tiempo.'],
  ];
  foreach($beneficios as $b): ?>
  <div class="card card-sm" style="text-align:center;">
    <div style="font-size:2rem;margin-bottom:8px;"><?= $b[0] ?></div>
    <div style="font-weight:700;margin-bottom:6px;"><?= $b[1] ?></div>
    <div style="font-size:.85rem;opacity:.6;"><?= $b[2] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<?php /* ─────────────────────────────────────────────────────────────
         NOSOTROS
────────────────────────────────────────────────────────────────── */ ?>
<?php elseif($seccion === 'nosotros'): ?>

<div style="padding:20px 0;">
  <div class="sec-title">Nosotros</div>
  <div class="sec-sub">El equipo detrás de Flownergy y nuestra razón de ser</div>

  <div class="grid-2" style="margin-bottom:28px;">
    <div class="card">
      <h3 style="color:var(--gold);margin-bottom:14px;">🎯 Misión</h3>
      <p style="opacity:.8;line-height:1.8;"><?= $proyecto['mision'] ?></p>
    </div>
    <div class="card">
      <h3 style="color:var(--gold);margin-bottom:14px;">🚀 Visión</h3>
      <p style="opacity:.8;line-height:1.8;"><?= $proyecto['vision'] ?></p>
      <!-- Puedes editar este bloque desde recibir_datos.php para personalizar la visión -->
    </div>
  </div>

  <!-- Equipo -->
  <div class="card" style="margin-bottom:28px;">
    <h3 style="color:var(--gold);margin-bottom:20px;">👥 Equipo</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;">
      <!-- Agrega o edita los integrantes aquí -->
      <div style="text-align:center;padding:20px;">
        <div style="width:70px;height:70px;border-radius:50%;background:rgba(255,215,0,.1);border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 12px;">👤</div>
        <div style="font-weight:700;">Alejandro Díaz Ramírez</div>
        <div style="font-size:.85rem;opacity:.5;margin-top:4px;">Desarrollador & Líder IoT</div>
      </div>
      <!-- Agrega más integrantes aquí duplicando el bloque -->
    </div>
  </div>

  <!-- ODS -->
  <div class="card">
    <h3 style="color:var(--gold);margin-bottom:6px;">🌎 ODS que atendemos</h3>
    <p style="opacity:.5;font-size:.88rem;margin-bottom:20px;">
      Objetivos de Desarrollo Sostenible de la ONU alineados con Flownergy
    </p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
      <?php foreach($proyecto['ods'] as $ods): ?>
      <div class="ods-badge" style="background:<?= $ods['color'] ?>22;border:1px solid <?= $ods['color'] ?>55;color:<?= $ods['color'] ?>;">
        <span style="font-size:1.4rem;font-weight:900;"><?= $ods['num'] ?></span>
        <span style="font-size:.82rem;line-height:1.3;"><?= $ods['titulo'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Puedes ampliar la explicación de cada ODS aquí -->
    <div style="margin-top:24px;opacity:.65;font-size:.9rem;line-height:1.8;">
      <p>Flownergy contribuye directamente al <strong>ODS 7</strong> al facilitar el uso eficiente
      de la energía eléctrica en la industria local. A través del <strong>ODS 9</strong> promueve
      la digitalización de micro y medianas empresas en Yumbo, Valle del Cauca.
      El <strong>ODS 12</strong> se refleja en nuestro enfoque de producción consciente,
      y el <strong>ODS 13</strong> en la reducción de huella de carbono mediante menor desperdicio energético.</p>
      <!-- Añade tu análisis personal de cada ODS aquí -->
    </div>
  </div>
</div>

<?php /* ─────────────────────────────────────────────────────────────
         CONTACTO
────────────────────────────────────────────────────────────────── */ ?>
<?php elseif($seccion === 'contacto'): ?>

<div style="text-align:center;padding:80px 0 40px;">
  <div class="sec-title">¿Tienes dudas?</div>
  <p style="opacity:.45;margin-bottom:50px;">
    Escríbenos directamente. Te respondemos sobre el proyecto, la instalación o el equipo.
  </p>
  <div style="display:flex;justify-content:center;gap:18px;flex-wrap:wrap;margin-bottom:50px;">
    <!-- WhatsApp: número 3225200707 -->
    <a href="https://wa.me/573225200707?text=Hola%2C%20me%20interesa%20Flownergy"
       target="_blank" class="btn btn-gold" style="font-size:1.05rem;">
      💬 WhatsApp: 322 520 0707
    </a>
    <!-- Email directo -->
    <a href="mailto:alejandrodiazramirez2020@gmail.com?subject=Consulta%20Flownergy"
       class="btn btn-outline" style="font-size:1.05rem;">
      ✉️ Enviar correo
    </a>
  </div>
  <div class="card" style="max-width:480px;margin:0 auto;text-align:left;">
    <h4 style="color:var(--gold);margin-bottom:14px;">📍 Información de contacto</h4>
    <p style="opacity:.7;line-height:2;font-size:.93rem;">
      📱 WhatsApp: <strong>+57 322 520 0707</strong><br>
      ✉️ Email: <strong>alejandrodiazramirez2020@gmail.com</strong><br>
      📍 Yumbo, Valle del Cauca, Colombia
    </p>
  </div>
</div>

<?php /* ─────────────────────────────────────────────────────────────
         ACCEDER (Login)
────────────────────────────────────────────────────────────────── */ ?>
<?php elseif($seccion === 'acceder'): ?>

<div style="max-width:400px;margin:80px auto;">
  <div class="card" style="text-align:center;">
    <div style="font-size:2.5rem;margin-bottom:10px;">⚡</div>
    <h2 style="animation:flowGold 4s infinite;margin-bottom:4px;">Acceso Flownergy</h2>
    <p style="opacity:.45;font-size:.88rem;margin-bottom:28px;">Ingresa tu ID y contraseña</p>

    <?php if($error_login): ?>
    <div class="alerta alerta-red" style="margin-bottom:18px;">❌ ID o contraseña incorrectos</div>
    <?php endif; ?>

    <form action="../api/validar.php" method="POST">
      <div class="input-group" style="text-align:left;">
        <label>ID de usuario</label>
        <input type="number" name="usuario" placeholder="Ej: 110" required autofocus/>
      </div>
      <div class="input-group" style="text-align:left;margin-bottom:22px;">
        <label>Contraseña</label>
        <input type="password" name="password" placeholder="••••••" required/>
      </div>
      <button type="submit" class="btn btn-gold btn-full">ENTRAR AL PANEL</button>
    </form>
  </div>
</div>

<?php /* ─────────────────────────────────────────────────────────────
         DASHBOARD
────────────────────────────────────────────────────────────────── */ ?>
<?php elseif($seccion === 'dashboard'): ?>

<?php if($registrado): ?>
<div class="alerta alerta-green" style="margin-bottom:20px;">✅ Máquina registrada correctamente. ¡Ya puedes empezar a medir!</div>
<?php endif; ?>

<?php if(!$maquina): ?>
<!-- ── Sin máquina: mostrar formulario de registro ── -->
<div style="max-width:560px;margin:0 auto;">
  <div class="sec-title" style="text-align:center;">Registra tu máquina</div>
  <p class="sec-sub" style="text-align:center;">Es la primera vez que ingresas. Registra el equipo que vas a monitorear.</p>
  <div class="card">
    <form action="../api/registrar_maquina.php" method="POST">
      <div class="grid-2">
        <div class="input-group">
          <label>Número serial</label>
          <input type="text" name="id_serial" placeholder="Ej: MOT-001" required/>
        </div>
        <div class="input-group">
          <label>Tipo / nombre</label>
          <input type="text" name="nombre_tipo" placeholder="Ej: Motor 3HP" required/>
        </div>
        <div class="input-group">
          <label>Voltaje nominal (V)</label>
          <input type="number" name="voltaje" placeholder="110" value="110" min="1" required/>
        </div>
        <div class="input-group">
          <label>Versión del equipo</label>
          <input type="text" name="version" placeholder="1.0"/>
        </div>
      </div>
      <button type="submit" class="btn btn-gold btn-full" style="margin-top:8px;">REGISTRAR MÁQUINA</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ── Con máquina registrada: mostrar dashboard ── -->

<!-- Info de máquina -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:24px;">
  <div>
    <div class="sec-title" style="margin-bottom:2px;"><?= htmlspecialchars($maquina['Nombre_tipo']) ?></div>
    <div style="color:var(--muted);font-family:var(--mono);font-size:.85rem;">
      Serial: <?= $maquina['Id_serial'] ?> &nbsp;|&nbsp;
      <?= $maquina['Voltaje_nominal'] ?>V nominal &nbsp;|&nbsp;
      v<?= $maquina['Version'] ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="?ver=medicion" class="btn btn-green">▶ Empezar Medición</a>
    <button onclick="document.getElementById('csv-panel').style.display=document.getElementById('csv-panel').style.display==='none'?'block':'none'"
            class="btn btn-outline btn-sm">📥 Exportar CSV</button>
  </div>
</div>

<!-- Panel CSV (oculto por defecto) -->
<div id="csv-panel" style="display:none;margin-bottom:20px;">
  <div class="card card-sm">
    <h4 style="color:var(--gold);margin-bottom:14px;">📥 Exportar datos a CSV</h4>
    <form action="../api/exportar_csv.php" method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
      <input type="hidden" name="id_serial" value="<?= $serial ?>"/>
      <div class="input-group" style="margin:0;flex:1;min-width:150px;">
        <label>Desde</label>
        <input type="date" name="desde" value="<?= date('Y-m-d', strtotime('-7 days')) ?>"/>
      </div>
      <div class="input-group" style="margin:0;flex:1;min-width:150px;">
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= date('Y-m-d') ?>"/>
      </div>
      <button type="submit" class="btn btn-gold btn-sm">Descargar</button>
    </form>
  </div>
</div>

<!-- Alertas recientes de BD -->
<?php if(!empty($alertas_db)): ?>
<div style="margin-bottom:20px;">
  <h4 style="color:var(--muted);font-size:.75rem;letter-spacing:2px;margin-bottom:10px;">// ALERTAS RECIENTES</h4>
  <?php foreach(array_slice($alertas_db,0,3) as $al):
    $cls = in_array($al['Tipo'],['pico_potencia','sobrecarga','voltaje_alto']) ? 'alerta-red'
         : ($al['Tipo']==='fp_bajo'||$al['Tipo']==='mantenimiento' ? 'alerta-yellow' : 'alerta-blue');
    $ico = ['pico_potencia'=>'⚡','bajo_potencia'=>'📉','voltaje_alto'=>'🔺','voltaje_bajo'=>'🔻','fp_bajo'=>'🔧','mantenimiento'=>'🛠️','sobrecarga'=>'🔥'][$al['Tipo']] ?? '⚠️';
  ?>
  <div class="alerta <?= $cls ?>">
    <?= $ico ?> <?= htmlspecialchars($al['Mensaje']) ?>
    <span style="margin-left:auto;opacity:.5;font-size:.78rem;white-space:nowrap;"><?= date('d/m H:i', strtotime($al['Fecha_hora'])) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Gráfica histórica -->
<div class="card" style="margin-bottom:20px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
    <div>
      <div style="font-size:1rem;font-weight:700;color:var(--gold);">HISTORIAL DE CONSUMO — Últimas 24 horas</div>
      <div style="font-size:.78rem;color:var(--muted);">Mediciones agrupadas cada 5 minutos</div>
    </div>
    <div style="display:flex;gap:14px;font-size:.75rem;color:var(--muted);">
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--gold);margin-right:4px;"></span>Potencia (W)</span>
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--blue);margin-right:4px;"></span>Voltaje (V)</span>
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--green);margin-right:4px;"></span>Factor P. ×100</span>
    </div>
  </div>
  <?php if(empty($historico)): ?>
    <div style="text-align:center;padding:40px;opacity:.4;">
      No hay datos históricos aún. Inicia una medición para comenzar a registrar.
    </div>
  <?php else: ?>
    <canvas id="grafica-historico" height="200"></canvas>
  <?php endif; ?>
</div>

<!-- Últimas lecturas tabla -->
<?php if(!empty($historico)): ?>
<div class="card">
  <h4 style="color:var(--gold);margin-bottom:14px;">📋 Últimas lecturas registradas</h4>
  <div style="overflow-x:auto;">
  <table style="width:100%;font-size:.83rem;border-collapse:collapse;">
    <thead>
      <tr style="opacity:.5;text-align:left;border-bottom:1px solid var(--border);">
        <th style="padding:8px 12px;">Fecha/Hora</th>
        <th style="padding:8px 12px;">Voltaje</th>
        <th style="padding:8px 12px;">Corriente</th>
        <th style="padding:8px 12px;">Potencia</th>
        <th style="padding:8px 12px;">Energía</th>
        <th style="padding:8px 12px;">FP</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach(array_reverse(array_slice($historico,-8)) as $h): ?>
      <tr style="border-bottom:1px solid var(--border);transition:.2s;" onmouseover="this.style.background='rgba(255,215,0,.04)'" onmouseout="this.style.background=''">
        <td style="padding:8px 12px;font-family:var(--mono);"><?= $h['intervalo'] ?></td>
        <td style="padding:8px 12px;color:var(--blue);"><?= $h['voltaje'] ?> V</td>
        <td style="padding:8px 12px;"><?= $h['corriente'] ?> A</td>
        <td style="padding:8px 12px;color:var(--gold);font-weight:700;"><?= $h['potencia'] ?> W</td>
        <td style="padding:8px 12px;"><?= $h['energia'] ?> kWh</td>
        <td style="padding:8px 12px;color:<?= $h['fp']<0.85?'var(--red)':'var(--green)' ?>"><?= $h['fp'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php endif; // fin: con máquina ?>

<?php /* ─────────────────────────────────────────────────────────────
         MEDICIÓN EN TIEMPO REAL
────────────────────────────────────────────────────────────────── */ ?>
<?php elseif($seccion === 'medicion'): ?>

<?php if(!$maquina): ?>
<div class="alerta alerta-yellow">⚠️ Primero registra tu máquina en el <a href="?ver=dashboard" style="color:var(--gold);">Dashboard</a>.</div>
<?php else: ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:24px;">
  <div>
    <div class="sec-title" style="margin-bottom:2px;">Medición en Tiempo Real</div>
    <div style="color:var(--muted);font-size:.85rem;font-family:var(--mono);">
      <?= htmlspecialchars($maquina['Nombre_tipo']) ?> · Serial: <?= $maquina['Id_serial'] ?>
    </div>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="bt-status" id="bt-badge"><div class="bt-dot"></div><span id="bt-text">DESCONECTADO</span></div>
    <button class="btn btn-blue" id="btn-bt" onclick="toggleBluetooth()">🔗 Conectar Bluetooth</button>
  </div>
</div>

<!-- Alerta de estado del sistema -->
<div id="alerta-sistema" style="display:none;margin-bottom:16px;"></div>

<!-- Métricas en tiempo real: 6 tarjetas -->
<div class="grid-4" style="margin-bottom:20px;">
  <div class="metric-card" style="--mc-color:var(--blue);">
    <div class="metric-icon">🔋</div>
    <div class="metric-label">Voltaje</div>
    <div class="metric-value" id="m-voltaje">---</div>
    <div class="metric-unit">Voltios (V)</div>
  </div>
  <div class="metric-card" style="--mc-color:#ff8c42;">
    <div class="metric-icon">⚡</div>
    <div class="metric-label">Corriente</div>
    <div class="metric-value" id="m-corriente">---</div>
    <div class="metric-unit">Amperios (A)</div>
  </div>
  <div class="metric-card" style="--mc-color:var(--gold);">
    <div class="metric-icon">💡</div>
    <div class="metric-label">Potencia</div>
    <div class="metric-value" id="m-potencia">---</div>
    <div class="metric-unit">Vatios (W)</div>
  </div>
  <div class="metric-card" style="--mc-color:#bf5fff;">
    <div class="metric-icon">🔌</div>
    <div class="metric-label">Energía</div>
    <div class="metric-value" id="m-energia">---</div>
    <div class="metric-unit">kWh</div>
  </div>
  <div class="metric-card" style="--mc-color:#00ffaa;">
    <div class="metric-icon">〰️</div>
    <div class="metric-label">Frecuencia</div>
    <div class="metric-value" id="m-frecuencia">---</div>
    <div class="metric-unit">Hertz (Hz)</div>
  </div>
  <div class="metric-card" style="--mc-color:#ff79c6;">
    <div class="metric-icon">📊</div>
    <div class="metric-label">Factor Potencia</div>
    <div class="metric-value" id="m-fp">---</div>
    <div class="metric-unit">cos φ</div>
  </div>
</div>

<!-- Controles: START / STOP / temporizador -->
<div class="card card-sm" style="margin-bottom:20px;">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
      <div style="font-size:.75rem;color:var(--muted);margin-bottom:4px;">CONTROL DE MEDICIÓN</div>
      <div style="font-family:var(--mono);font-size:.85rem;">
        Próxima guardado en BD: <span id="countdown" style="color:var(--gold);">5:00</span>
        &nbsp;|&nbsp; Total guardados: <span id="total-guardados" style="color:var(--green);">0</span>
      </div>
    </div>
    <div style="display:flex;gap:10px;">
      <button class="btn btn-green btn-sm" id="btn-start" onclick="enviarComando('START')" disabled>▶ Iniciar</button>
      <button class="btn btn-red btn-sm"   id="btn-stop"  onclick="enviarComando('STOP')"  disabled>⏹ Detener</button>
      <button class="btn btn-outline btn-sm" onclick="if(confirm('¿Reiniciar contador de energía del PZEM?')) enviarComando('RESET_ENERGY')">🔄 Reset energía</button>
    </div>
  </div>
</div>

<!-- Alertas en tiempo real (generadas por el backend) -->
<div id="alertas-rt" style="margin-bottom:20px;"></div>

<!-- Gráfica en tiempo real -->
<div class="card" style="margin-bottom:20px;">
  <div style="margin-bottom:14px;">
    <div style="font-size:1rem;font-weight:700;color:var(--gold);">GRÁFICA EN TIEMPO REAL</div>
    <div style="font-size:.78rem;color:var(--muted);">Últimas 60 lecturas (1 por segundo)</div>
  </div>
  <canvas id="grafica-rt" height="180"></canvas>
</div>

<!-- Consola raw -->
<div class="card card-sm">
  <div style="font-size:.72rem;letter-spacing:2px;color:var(--muted);margin-bottom:10px;">// CONSOLA BLUETOOTH RAW</div>
  <div class="console" id="console-bt"></div>
</div>

<?php endif; // fin: con máquina en medición ?>

<?php endif; // fin router de secciones ?>

</main>

<footer class="wrap">
  <div>FLOWNERGY © <?= date('Y') ?> &nbsp;·&nbsp; Monitor IoT para la industria de Yumbo &nbsp;·&nbsp;
  <a href="?ver=contacto" style="color:var(--gold);text-decoration:none;">Contacto</a></div>
</footer>

<!-- ================================================================
     JAVASCRIPT
================================================================ -->
<script>
// ────────────────────────────────────────────────────────────────────
// Gráfica histórica (dashboard)
// ────────────────────────────────────────────────────────────────────
const elHistorico = document.getElementById('grafica-historico');
if (elHistorico) {
  const labels   = <?= $json_labels ?>;
  const potencia = <?= $json_potencia ?>;
  const voltaje  = <?= $json_voltaje ?>;
  const fp       = <?= $json_fp ?>.map(v => v * 100); // ×100 para escalar

  new Chart(elHistorico, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Potencia (W)',
          data: potencia,
          borderColor: '#FFD700',
          backgroundColor: 'rgba(255,215,0,0.06)',
          borderWidth: 2.5,
          tension: 0.4,
          fill: true,
          pointRadius: 2,
        },
        {
          label: 'Voltaje (V)',
          data: voltaje,
          borderColor: '#00e5ff',
          backgroundColor: 'transparent',
          borderWidth: 1.8,
          tension: 0.4,
          fill: false,
          pointRadius: 1,
        },
        {
          label: 'FP ×100',
          data: fp,
          borderColor: '#39ff14',
          backgroundColor: 'transparent',
          borderWidth: 1.5,
          tension: 0.4,
          fill: false,
          pointRadius: 1,
          borderDash: [4,3],
        },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { labels: { color: '#8899aa', font: { size: 11 } } },
        tooltip: { backgroundColor: '#0d1118', borderColor: '#FFD700', borderWidth: 1 },
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#5a6a7a', font: { size: 10 }, maxTicksLimit: 10 },
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#5a6a7a', font: { size: 10 } },
        },
      },
    },
  });
}

// ────────────────────────────────────────────────────────────────────
// Bluetooth + Medición en tiempo real (sección medicion)
// ────────────────────────────────────────────────────────────────────
let btPort, btReader, btConnected = false;
let btBuffer = '';
let ultimaLectura = null;
let totalGuardados = 0;

// Gráfica en tiempo real
const MAX_RT_POINTS = 60;
const rtHistory = { labels:[], potencia:[], voltaje:[], fp:[] };
let chartRT = null;

const elRT = document.getElementById('grafica-rt');
if (elRT) {
  chartRT = new Chart(elRT, {
    type: 'line',
    data: {
      labels: [],
      datasets: [
        { label:'Potencia(W)', data:[], borderColor:'#FFD700', backgroundColor:'rgba(255,215,0,0.07)',
          borderWidth:2, tension:0.4, fill:true, pointRadius:0 },
        { label:'Voltaje(V)',  data:[], borderColor:'#00e5ff', backgroundColor:'transparent',
          borderWidth:1.5, tension:0.4, fill:false, pointRadius:0 },
        { label:'FP×100',     data:[], borderColor:'#39ff14',  backgroundColor:'transparent',
          borderWidth:1.2, tension:0.4, fill:false, pointRadius:0, borderDash:[3,2] },
      ],
    },
    options: {
      responsive:true, animation: { duration: 0 },
      plugins: { legend:{ labels:{ color:'#8899aa', font:{size:10} } } },
      scales: {
        x: { display:false },
        y: { grid:{ color:'rgba(255,255,255,0.04)' }, ticks:{ color:'#5a6a7a', font:{size:10} } },
      },
    },
  });
}

// Temporizador para guardar en BD cada 5 minutos (300 segundos)
let saveCountdown = 300;
const countdownEl = document.getElementById('countdown');

function iniciarTemporizador() {
  setInterval(() => {
    if (!btConnected) return;
    saveCountdown--;
    if (saveCountdown <= 0) {
      saveCountdown = 300;
      guardarEnBD();
    }
    const m = Math.floor(saveCountdown / 60);
    const s = saveCountdown % 60;
    if (countdownEl) countdownEl.textContent = `${m}:${String(s).padStart(2,'0')}`;
  }, 1000);
}
iniciarTemporizador();

// ── Bluetooth toggle ──────────────────────────────────────────────
async function toggleBluetooth() {
  if (btConnected) { await btDisconnect(); return; }

  if (!('serial' in navigator)) {
    alert('Usa Google Chrome o Microsoft Edge para la conexión Bluetooth.');
    return;
  }
  try {
    btPort = await navigator.serial.requestPort();
    await btPort.open({ baudRate: 115200 });
    btConnected = true;
    setBtStatus('on', 'CONECTADO');
    document.getElementById('btn-bt').textContent    = '⛔ Desconectar';
    document.getElementById('btn-bt').className      = 'btn btn-red';
    document.getElementById('btn-start').disabled    = false;
    document.getElementById('btn-stop').disabled     = false;
    consolaLog('Puerto Bluetooth abierto.', 'ok');
    btReadLoop();
  } catch(e) {
    consolaLog('Error al conectar: ' + e.message, 'err');
  }
}

async function btDisconnect() {
  btConnected = false;
  try { btReader && await btReader.cancel(); } catch(_) {}
  try { btPort   && await btPort.close();   } catch(_) {}
  setBtStatus('', 'DESCONECTADO');
  document.getElementById('btn-bt').textContent  = '🔗 Conectar Bluetooth';
  document.getElementById('btn-bt').className    = 'btn btn-blue';
  document.getElementById('btn-start').disabled  = true;
  document.getElementById('btn-stop').disabled   = true;
  consolaLog('Desconectado.', 'err');
}

// ── Loop de lectura ───────────────────────────────────────────────
async function btReadLoop() {
  const decoder = new TextDecoderStream();
  btPort.readable.pipeTo(decoder.writable);
  btReader = decoder.readable.getReader();
  try {
    while (btConnected) {
      const { value, done } = await btReader.read();
      if (done) break;
      btBuffer += value;
      const lineas = btBuffer.split('\n');
      btBuffer = lineas.pop();
      lineas.forEach(procesarLinea);
    }
  } catch(e) {
    if (btConnected) consolaLog('Error de lectura: ' + e.message, 'err');
  }
}

// ── Procesar cada línea JSON recibida ─────────────────────────────
function procesarLinea(linea) {
  linea = linea.trim();
  if (!linea) return;
  consolaLog(linea, 'ok');

  // Si es confirmación de comando, ignorar
  if (linea.startsWith('{"cmd_ack"')) return;

  try {
    const d = JSON.parse(linea);
    ultimaLectura = d;

    // Actualizar métricas
    actualizarMetrica('m-voltaje',   d.voltaje?.toFixed(1)    ?? '---');
    actualizarMetrica('m-corriente', d.corriente?.toFixed(3)  ?? '---');
    actualizarMetrica('m-potencia',  d.potencia?.toFixed(1)   ?? '---');
    actualizarMetrica('m-energia',   d.energia?.toFixed(4)    ?? '---');
    actualizarMetrica('m-frecuencia',d.frecuencia?.toFixed(1) ?? '---');
    actualizarMetrica('m-fp',        d.fp?.toFixed(3)         ?? '---');

    // Mostrar alerta de sistema si hay estado anormal
    mostrarAlertaSistema(d.estado);

    // Agregar punto a gráfica en tiempo real
    if (chartRT && d.estado !== 'sin_red') {
      const t = new Date().toLocaleTimeString();
      agregarPuntoRT(t, d.potencia || 0, d.voltaje || 0, (d.fp || 0) * 100);
    }

  } catch(_) { /* línea no JSON */ }
}

function actualizarMetrica(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = val;
  el.style.transform = 'scale(1.06)';
  setTimeout(() => el.style.transform = 'scale(1)', 200);
}

function mostrarAlertaSistema(estado) {
  const el = document.getElementById('alerta-sistema');
  if (!el) return;
  const alertas = {
    'sin_red':        ['alerta-yellow', '⚠️ Sin señal de red AC. Verifica el PZEM-004T.'],
    'sobrecarga':     ['alerta-red',    '🔥 SOBRECARGA DETECTADA. Relé desactivado por seguridad.'],
    'voltaje_anormal':['alerta-red',    '🔺 Voltaje fuera de rango normal. Revisa la red eléctrica.'],
    'pf_bajo':        ['alerta-yellow', '🔧 Factor de potencia bajo. Se recomienda mantenimiento.'],
    'ok':             [null, null],
  };
  const cfg = alertas[estado];
  if (cfg && cfg[0]) {
    el.style.display = 'block';
    el.innerHTML = `<div class="alerta ${cfg[0]}">${cfg[1]}</div>`;
  } else {
    el.style.display = 'none';
  }
}

// ── Gráfica en tiempo real ────────────────────────────────────────
function agregarPuntoRT(t, p, v, fp) {
  rtHistory.labels.push(t);
  rtHistory.potencia.push(p);
  rtHistory.voltaje.push(v);
  rtHistory.fp.push(fp);

  if (rtHistory.labels.length > MAX_RT_POINTS) {
    rtHistory.labels.shift();
    rtHistory.potencia.shift();
    rtHistory.voltaje.shift();
    rtHistory.fp.shift();
  }

  chartRT.data.labels              = rtHistory.labels;
  chartRT.data.datasets[0].data   = rtHistory.potencia;
  chartRT.data.datasets[1].data   = rtHistory.voltaje;
  chartRT.data.datasets[2].data   = rtHistory.fp;
  chartRT.update('none');
}

// ── Enviar comando al ESP32 ───────────────────────────────────────
async function enviarComando(cmd) {
  if (!btConnected || !btPort?.writable) {
    consolaLog('No conectado. Conecta el Bluetooth primero.', 'err');
    return;
  }
  const writer = btPort.writable.getWriter();
  await writer.write(new TextEncoder().encode(cmd + '\n'));
  writer.releaseLock();
  consolaLog(`Comando enviado: ${cmd}`, 'warn');
}

// ── Guardar en base de datos (cada 5 minutos) ─────────────────────
async function guardarEnBD() {
  if (!ultimaLectura || ultimaLectura.estado === 'sin_red') {
    consolaLog('Sin datos válidos para guardar.', 'warn');
    return;
  }

  const serial = '<?= addslashes($serial ?? '') ?>';

  try {
    const resp = await fetch('../api/guardar_medicion.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_serial:  serial,
        voltaje:    ultimaLectura.voltaje,
        corriente:  ultimaLectura.corriente,
        potencia:   ultimaLectura.potencia,
        energia:    ultimaLectura.energia,
        frecuencia: ultimaLectura.frecuencia,
        fp:         ultimaLectura.fp,
        estado:     ultimaLectura.estado,
      }),
    });

    const resultado = await resp.json();
    totalGuardados++;
    document.getElementById('total-guardados').textContent = totalGuardados;
    consolaLog(`✅ Guardado #${totalGuardados} en BD.`, 'ok');

    // Mostrar alertas del backend
    if (resultado.alertas && resultado.alertas.length > 0) {
      const contenedor = document.getElementById('alertas-rt');
      resultado.alertas.forEach(al => {
        const div = document.createElement('div');
        const cls = al.tipo.includes('pico')||al.tipo.includes('voltaje_alto')||al.tipo==='sobrecarga'
                  ? 'alerta-red' : 'alerta-yellow';
        div.className = `alerta ${cls}`;
        div.textContent = '⚠️ ' + al.mensaje;
        contenedor.prepend(div);
        setTimeout(() => div.remove(), 30000); // Desaparece a los 30 seg
      });
    }
  } catch(e) {
    consolaLog('Error guardando en BD: ' + e.message, 'err');
  }
}

// ── Helpers UI ────────────────────────────────────────────────────
function setBtStatus(cls, text) {
  const badge = document.getElementById('bt-badge');
  if (!badge) return;
  badge.className = 'bt-status ' + cls;
  document.getElementById('bt-text').textContent = text;
}

function consolaLog(msg, tipo='') {
  const c = document.getElementById('console-bt');
  if (!c) return;
  const d = document.createElement('div');
  d.className = 'console-line console-' + tipo;
  d.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
  c.prepend(d);
  if (c.children.length > 80) c.removeChild(c.lastChild);
}
</script>

</body>
</html>
