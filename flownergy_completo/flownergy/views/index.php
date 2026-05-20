<?php
/**
 * views/index.php - Router principal de Flownergy
 */

session_start();
require_once(dirname(__DIR__) . '/models/MaquinaModel.php');
require_once(dirname(__DIR__) . '/models/MedicionModel.php');

$seccion     = $_GET['ver'] ?? 'inicio';
$error_login = isset($_GET['error']);
$registrado  = isset($_GET['registrado']);

$privadas = ['dashboard', 'medicion'];
if (in_array($seccion, $privadas) && !isset($_SESSION['usuario'])) {
    header('Location: ?ver=acceder');
    exit();
}

$maquina    = null;
$historico  = [];
$alertas_db = [];
$serial     = null;

if (isset($_SESSION['usuario'])) {
    $maquina = MaquinaModel::obtenerPorUsuario((int)$_SESSION['usuario']);
    if ($maquina) {
        $serial     = $maquina['Id_serial'];
        $historico  = MedicionModel::obtenerHistorico($serial, 24);
        $alertas_db = MedicionModel::obtenerAlertas($serial, 5);
        $_SESSION['maquina_serial'] = $serial;
    }
}

$proyecto = [
    'nombre'  => 'Flownergy',
    'mision'  => 'Brindar soluciones tecnologicas accesibles para que las microempresas de Yumbo y la region optimicen su consumo energetico mediante monitoreo inteligente, previniendo danos y reduciendo costos.',
    'vision'  => 'Para el 2030, ser lideres en gestion de energia para micro y medianas empresas, integrando hardware de bajo costo, alta precision e inteligencia predictiva.',
    'valores' => [
        ['tag' => 'Innovacion',     'desc' => 'ESP32 + PZEM-004T para monitoreo de precision.'],
        ['tag' => 'Prevencion',     'desc' => 'Alertas anticipadas evitan danos e incendios.'],
        ['tag' => 'Accesibilidad',  'desc' => 'Tecnologia IoT al alcance de la microempresa.'],
        ['tag' => 'Sostenibilidad', 'desc' => 'Menos consumo = menos impacto ambiental.'],
    ],
    'ods' => [
        ['num' => '07', 'titulo' => 'Energia asequible y no contaminante',      'color' => '#FCC30B', 'url' => 'https://www.google.com/search?q=ODS+7+Energia+asequible+y+no+contaminante'],
        ['num' => '09', 'titulo' => 'Industria, innovacion e infraestructura',  'color' => '#FD6925', 'url' => 'https://www.google.com/search?q=ODS+9+Industria+innovacion+e+infraestructura'],
        ['num' => '11', 'titulo' => 'Ciudades y comunidades sostenibles',       'color' => '#FD9D24', 'url' => 'https://www.google.com/search?q=ODS+11+Ciudades+y+comunidades+sostenibles'],
        ['num' => '12', 'titulo' => 'Produccion y consumo responsables',        'color' => '#BF8B2E', 'url' => 'https://www.google.com/search?q=ODS+12+Produccion+y+consumo+responsables'],
        ['num' => '13', 'titulo' => 'Accion por el clima',                      'color' => '#3F7E44', 'url' => 'https://www.google.com/search?q=ODS+13+Accion+por+el+clima'],
    ],
];

$video_speech_url = '#'; // Cambia este valor por el link de YouTube, Drive o TikTok del speech del proyecto.

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
<title>Flownergy - Monitor de Energia Industrial</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
  --bg:#070a0d;
  --bg-soft:#0d1218;
  --panel:rgba(13,18,24,.76);
  --panel-2:rgba(255,255,255,.055);
  --line:rgba(255,255,255,.12);
  --line-strong:rgba(255,255,255,.2);
  --text:#f4f7fb;
  --muted:#9aa8b6;
  --muted-2:#667584;
  --gold:#f7c948;
  --amber:#ff9f1c;
  --green:#31e981;
  --blue:#34d6ff;
  --red:#ff5570;
  --violet:#8f7bff;
  --font:'Inter',system-ui,sans-serif;
  --mono:'Share Tech Mono',monospace;
  --radius:8px;
  --shadow:0 24px 80px rgba(0,0,0,.38);
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  min-height:100vh;
  overflow-x:hidden;
  font-family:var(--font);
  color:var(--text);
  background:
    radial-gradient(circle at 16% 18%, rgba(247,201,72,.16), transparent 28%),
    radial-gradient(circle at 82% 8%, rgba(52,214,255,.13), transparent 24%),
    radial-gradient(circle at 58% 84%, rgba(49,233,129,.1), transparent 24%),
    linear-gradient(135deg,#050608 0%,#081016 44%,#0d120c 100%);
}
body::before{
  content:"";
  position:fixed;
  inset:0;
  z-index:-3;
  background:
    linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
  background-size:72px 72px;
  mask-image:linear-gradient(to bottom,rgba(0,0,0,.9),rgba(0,0,0,.2));
}
body::after{
  content:"";
  position:fixed;
  inset:-20%;
  z-index:-2;
  background:conic-gradient(from 90deg at 50% 50%,rgba(247,201,72,.12),rgba(52,214,255,.08),rgba(49,233,129,.1),rgba(247,201,72,.12));
  filter:blur(80px);
  animation:bgShift 18s linear infinite;
  opacity:.72;
}
@keyframes bgShift{to{transform:rotate(1turn) scale(1.08)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
@keyframes float{0%,100%{transform:translate3d(0,0,0) rotateX(58deg) rotateZ(-22deg)}50%{transform:translate3d(0,-16px,0) rotateX(58deg) rotateZ(-18deg)}}
@keyframes orbit{to{transform:rotate(1turn)}}
@keyframes pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 currentColor}50%{opacity:.65;box-shadow:0 0 0 6px transparent}}
@keyframes scan{to{transform:translateX(120%)}}
.wrap{width:min(calc(100% - 40px),1200px);margin:0 auto;position:relative;z-index:1}
.site-glow{position:fixed;inset:0;z-index:-1;pointer-events:none;background:radial-gradient(circle at var(--mx,50%) var(--my,40%),rgba(247,201,72,.12),transparent 24rem)}
header{
  position:sticky;
  top:18px;
  z-index:50;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:18px;
  margin-top:18px;
  padding:12px 14px 12px 16px;
  border:1px solid var(--line);
  border-radius:8px;
  background:rgba(6,10,14,.72);
  backdrop-filter:blur(20px);
  box-shadow:0 14px 50px rgba(0,0,0,.28);
}
.logo-container{display:flex;align-items:center;gap:12px;color:var(--text);text-decoration:none;min-width:max-content}
.logo-mark{
  width:42px;height:42px;border-radius:8px;display:grid;place-items:center;position:relative;overflow:hidden;
  background:linear-gradient(135deg,#fff6c8 0%,var(--gold) 42%,#44f0ff 100%);
  color:#06100d;font-weight:900;box-shadow:0 0 32px rgba(247,201,72,.28);
}
.logo-mark img{width:100%;height:100%;object-fit:contain;padding:3px;background:#071014}
.logo-word{font-size:1.1rem;font-weight:900;letter-spacing:.16em;text-transform:uppercase}
.logo-sub{display:block;margin-top:2px;font-size:.62rem;letter-spacing:.19em;color:var(--muted)}
nav{display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end}
nav a{color:var(--muted);text-decoration:none;font-size:.88rem;font-weight:700;padding:10px 12px;border-radius:6px;transition:.22s}
nav a:hover,nav a.active{color:var(--text);background:rgba(255,255,255,.07)}
nav .btn-nav{color:#08100a!important;background:linear-gradient(135deg,var(--gold),var(--green));box-shadow:0 10px 28px rgba(247,201,72,.18)}
.user-badge{font-family:var(--mono);font-size:.78rem;color:var(--blue);border:1px solid rgba(52,214,255,.28);padding:8px 10px;border-radius:6px;background:rgba(52,214,255,.08)}
main.wrap{padding-top:34px}
.card{
  position:relative;overflow:hidden;border:1px solid var(--line);border-radius:var(--radius);padding:28px;
  background:linear-gradient(180deg,rgba(255,255,255,.075),rgba(255,255,255,.035));
  box-shadow:0 18px 58px rgba(0,0,0,.24);
  transition:transform .28s ease,border-color .28s ease,background .28s ease;
}
.card::before{
  content:"";position:absolute;inset:0;pointer-events:none;
  background:linear-gradient(120deg,transparent 15%,rgba(255,255,255,.08),transparent 34%);
  transform:translateX(-120%);
}
.card:hover{transform:translateY(-5px);border-color:var(--line-strong);background:linear-gradient(180deg,rgba(255,255,255,.1),rgba(255,255,255,.045))}
.card:hover::before{animation:scan .9s ease}
.card-sm{padding:20px}
.grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.grid-4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.section{padding:54px 0}
.kicker{display:inline-flex;gap:8px;align-items:center;padding:7px 10px;border:1px solid rgba(247,201,72,.22);border-radius:6px;background:rgba(247,201,72,.08);color:var(--gold);font:700 .72rem var(--mono);letter-spacing:.16em;text-transform:uppercase}
.sec-title{font-size:clamp(2rem,4vw,4.4rem);line-height:.96;font-weight:900;letter-spacing:-.04em;margin:14px 0 10px}
.sec-sub{max-width:760px;color:var(--muted);font-size:1.04rem;line-height:1.75;margin-bottom:28px}
.hero{
  min-height:calc(100vh - 132px);
  display:grid;
  grid-template-columns:minmax(0,1.02fr) minmax(360px,.98fr);
  align-items:center;
  gap:34px;
  padding:30px 0 70px;
}
.hero-copy{animation:fadeUp .8s ease both}
.hero h1{font-size:clamp(3.5rem,8.7vw,8.8rem);line-height:.82;font-weight:900;letter-spacing:-.075em;max-width:780px}
.text-energy{display:inline-block;background:linear-gradient(90deg,#fff,var(--gold) 42%,var(--green));-webkit-background-clip:text;background-clip:text;color:transparent}
.hero .sub{max-width:650px;margin-top:24px;color:#c9d3df;font-size:1.08rem;line-height:1.75}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:46px;
  padding:13px 20px;border:1px solid transparent;border-radius:6px;text-decoration:none;font:800 .92rem var(--font);
  color:var(--text);cursor:pointer;transition:.22s ease;white-space:nowrap;
}
.btn-gold{background:linear-gradient(135deg,var(--gold),var(--green));color:#05100a;box-shadow:0 18px 44px rgba(247,201,72,.22)}
.btn-gold:hover,.btn-green:hover,.btn-blue:hover{transform:translateY(-2px);filter:saturate(1.14)}
.btn-outline{background:rgba(255,255,255,.04);border-color:var(--line);color:var(--text)}
.btn-outline:hover{border-color:rgba(247,201,72,.45);background:rgba(247,201,72,.08)}
.btn-blue{background:linear-gradient(135deg,var(--blue),#83f3ff);color:#021016}
.btn-green{background:linear-gradient(135deg,var(--green),#b4ff5e);color:#03130b}
.btn-red{background:linear-gradient(135deg,var(--red),#ff9baa);color:#180207}
.btn-sm{min-height:38px;padding:9px 14px;font-size:.82rem}
.btn-full{width:100%}
.hero-visual{min-height:560px;position:relative;display:grid;place-items:center;perspective:1100px;animation:fadeUp .9s .12s ease both}
.energy-device{
  width:min(420px,80vw);aspect-ratio:1;position:relative;transform-style:preserve-3d;animation:float 7s ease-in-out infinite;
}
.device-core{
  position:absolute;inset:16%;border-radius:8px;border:1px solid rgba(255,255,255,.16);
  background:linear-gradient(145deg,rgba(255,255,255,.16),rgba(255,255,255,.04)),linear-gradient(135deg,rgba(247,201,72,.28),rgba(52,214,255,.1));
  box-shadow:inset 0 1px 0 rgba(255,255,255,.26),0 38px 90px rgba(0,0,0,.42),0 0 70px rgba(52,214,255,.12);
}
.device-core::before,.device-core::after{content:"";position:absolute;border:1px solid rgba(255,255,255,.1);border-radius:8px}
.device-core::before{inset:18%;background:rgba(0,0,0,.18)}
.device-core::after{inset:38%;background:radial-gradient(circle,var(--gold),rgba(247,201,72,.08));box-shadow:0 0 44px rgba(247,201,72,.72)}
.device-ring{position:absolute;inset:4%;border:1px solid rgba(52,214,255,.28);border-radius:50%;animation:orbit 14s linear infinite}
.device-ring:nth-child(2){inset:0;border-color:rgba(49,233,129,.22);animation-duration:22s;animation-direction:reverse}
.device-ring::before{content:"";position:absolute;top:7%;left:50%;width:12px;height:12px;border-radius:50%;background:var(--blue);box-shadow:0 0 24px var(--blue)}
.telemetry-card{
  position:absolute;right:0;bottom:32px;width:230px;padding:15px;border:1px solid rgba(255,255,255,.16);border-radius:8px;
  background:rgba(7,10,13,.78);backdrop-filter:blur(16px);box-shadow:var(--shadow);transform:translateZ(60px) rotateZ(22deg);
}
.telemetry-row{display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.08);font-family:var(--mono);font-size:.82rem}
.telemetry-row:last-child{border-bottom:0}
.hero-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:28px;max-width:620px}
.stat{border:1px solid var(--line);border-radius:8px;background:rgba(255,255,255,.045);padding:14px}
.stat strong{display:block;font-size:1.35rem;color:var(--gold)}
.stat span{font-size:.78rem;color:var(--muted)}
.feature-icon{width:46px;height:46px;border-radius:8px;display:grid;place-items:center;margin-bottom:16px;background:rgba(247,201,72,.1);color:var(--gold);font-size:1.25rem}
.feature-card h3,.benefit-card h3{font-size:1.15rem;margin-bottom:10px}
.feature-card p,.benefit-card p{color:var(--muted);line-height:1.65;font-size:.94rem}
.benefit-card{min-height:170px}
.wide-band{
  border:1px solid var(--line);border-radius:8px;padding:34px;background:
  linear-gradient(90deg,rgba(247,201,72,.14),rgba(52,214,255,.08),rgba(49,233,129,.1));
  display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;
}
.placeholder{
  border:1px dashed rgba(255,255,255,.28);border-radius:8px;min-height:220px;display:grid;place-items:center;text-align:center;
  color:var(--muted);font-family:var(--mono);padding:24px;background:rgba(255,255,255,.035)
}
.media-lab{display:grid;grid-template-columns:1.05fr .95fr;gap:18px;align-items:stretch}
.prototype-uploader{
  min-height:360px;border:1px solid rgba(255,255,255,.16);border-radius:8px;position:relative;overflow:hidden;
  background:
    linear-gradient(145deg,rgba(255,255,255,.08),rgba(255,255,255,.03)),
    radial-gradient(circle at 24% 18%,rgba(247,201,72,.18),transparent 28%),
    radial-gradient(circle at 78% 78%,rgba(52,214,255,.14),transparent 26%);
}
.prototype-uploader input{position:absolute;inset:0;opacity:0;cursor:pointer;z-index:4}
.prototype-preview{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.92;display:none}
.prototype-empty{position:absolute;inset:0;display:grid;place-items:center;text-align:center;padding:28px}
.prototype-empty strong{display:block;color:var(--text);font-size:1.8rem;letter-spacing:-.04em;margin-bottom:10px}
.prototype-empty span{display:block;color:var(--muted);font-family:var(--mono);line-height:1.7}
.prototype-frame{position:absolute;inset:18px;border:1px solid rgba(247,201,72,.28);border-radius:8px;pointer-events:none}
.prototype-frame::before,.prototype-frame::after{content:"";position:absolute;width:46px;height:46px;border-color:var(--gold);border-style:solid;opacity:.75}
.prototype-frame::before{left:-1px;top:-1px;border-width:2px 0 0 2px}
.prototype-frame::after{right:-1px;bottom:-1px;border-width:0 2px 2px 0}
.speech-panel{
  min-height:360px;border:1px solid var(--line);border-radius:8px;padding:26px;display:flex;flex-direction:column;justify-content:space-between;
  background:linear-gradient(180deg,rgba(255,255,255,.085),rgba(255,255,255,.035));
}
.video-orb{width:120px;height:120px;border-radius:50%;margin:18px auto;display:grid;place-items:center;position:relative;background:radial-gradient(circle,var(--gold),rgba(247,201,72,.12) 58%,transparent 60%)}
.video-orb::before,.video-orb::after{content:"";position:absolute;inset:-16px;border:1px solid rgba(52,214,255,.25);border-radius:50%;animation:orbit 10s linear infinite}
.video-orb::after{inset:-30px;border-color:rgba(49,233,129,.18);animation-duration:16s;animation-direction:reverse}
.play-triangle{width:0;height:0;border-top:17px solid transparent;border-bottom:17px solid transparent;border-left:25px solid #06100d;margin-left:7px;z-index:2}
.visual-flow{position:relative;min-height:250px;border:1px solid var(--line);border-radius:8px;overflow:hidden;background:rgba(255,255,255,.035);padding:28px}
.flow-line{position:absolute;left:5%;right:5%;top:50%;height:2px;background:linear-gradient(90deg,transparent,var(--gold),var(--blue),var(--green),transparent)}
.flow-line::before{content:"";position:absolute;inset:-5px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.8),transparent);animation:scan 2.6s linear infinite}
.flow-steps{position:relative;z-index:2;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;height:100%;align-items:center}
.flow-step{background:rgba(5,8,11,.72);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:18px;backdrop-filter:blur(12px)}
.flow-step strong{display:block;color:var(--gold);font-family:var(--mono);font-size:.8rem;margin-bottom:8px}
.flow-step span{color:var(--text);font-weight:800}
.ods-badge{display:flex;align-items:center;gap:12px;border-radius:8px;padding:14px;border:1px solid var(--line);background:rgba(255,255,255,.045)}
.ods-badge{text-decoration:none;color:var(--text);transition:.22s ease}
.ods-badge:hover{transform:translateY(-3px);filter:saturate(1.15);background:rgba(255,255,255,.07)}
.ods-badge strong{font-size:1.55rem}
.why-card{min-height:230px}
.why-card strong{display:block;font-size:2.3rem;line-height:1;color:var(--gold);letter-spacing:-.05em;margin-bottom:12px}
.why-card p{color:var(--muted);line-height:1.65}
.pricing-strip{
  display:grid;grid-template-columns:1.1fr .9fr;gap:18px;align-items:center;margin-top:18px;
  border:1px solid rgba(49,233,129,.22);border-radius:8px;padding:24px;background:linear-gradient(135deg,rgba(49,233,129,.12),rgba(247,201,72,.08));
}
.pricing-strip h3{font-size:clamp(1.7rem,3vw,3rem);line-height:.98;letter-spacing:-.05em}
.pricing-strip p{color:var(--muted);line-height:1.7;margin-top:12px}
.price-badge{border:1px solid rgba(247,201,72,.32);border-radius:8px;background:rgba(5,8,11,.56);padding:22px;text-align:center}
.price-badge span{display:block;color:var(--muted);font-size:.8rem;text-transform:uppercase;letter-spacing:.12em;font-weight:900}
.price-badge strong{display:block;color:var(--green);font-size:2rem;margin-top:8px;letter-spacing:-.04em}
.team-avatar{width:92px;height:92px;border-radius:50%;margin:0 auto 14px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(247,201,72,.18),rgba(52,214,255,.12));border:1px solid rgba(247,201,72,.32);font-weight:900;color:var(--gold);font-size:1.8rem}
.input-group{margin-bottom:16px;text-align:left}
.input-group label{display:block;margin-bottom:7px;color:var(--muted);font-size:.72rem;font-weight:900;letter-spacing:.13em;text-transform:uppercase}
.input-group input,.input-group select{
  width:100%;border:1px solid var(--line);border-radius:6px;background:rgba(0,0,0,.24);color:var(--text);
  font:600 .98rem var(--font);padding:13px 14px;outline:none;transition:.2s;
}
.input-group input:focus,.input-group select:focus{border-color:rgba(247,201,72,.64);box-shadow:0 0 0 4px rgba(247,201,72,.09)}
.login-shell{min-height:calc(100vh - 190px);display:grid;grid-template-columns:1fr 440px;gap:28px;align-items:center;padding:44px 0}
.login-pitch{padding:34px;border-left:3px solid var(--gold)}
.login-pitch h1{font-size:clamp(2.8rem,6vw,6.2rem);line-height:.88;letter-spacing:-.065em;margin:18px 0}
.login-card{padding:34px;background:linear-gradient(180deg,rgba(255,255,255,.11),rgba(255,255,255,.045))}
.lock-orbit{width:86px;height:86px;border-radius:50%;margin:0 auto 18px;display:grid;place-items:center;position:relative;background:rgba(247,201,72,.09);color:var(--gold);font-size:2rem}
.lock-orbit::before{content:"";position:absolute;inset:-8px;border:1px solid rgba(52,214,255,.28);border-radius:50%;border-left-color:transparent;animation:orbit 6s linear infinite}
.login-mini-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:22px}
.login-mini-card{border:1px solid var(--line);border-radius:8px;background:rgba(255,255,255,.045);padding:14px;text-align:center}
.login-mini-card strong{display:block;color:var(--gold);font-family:var(--mono);font-size:1.05rem}
.login-mini-card span{display:block;color:var(--muted);font-size:.72rem;margin-top:4px}
.password-field{position:relative}
.password-field input{padding-right:104px}
.password-toggle{
  position:absolute;right:8px;top:50%;transform:translateY(-50%);min-height:34px;padding:7px 10px;border-radius:6px;
  border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.06);color:var(--muted);font:800 .72rem var(--font);cursor:pointer;
}
.password-toggle:hover{color:var(--text);border-color:rgba(247,201,72,.35)}
.login-helper{
  display:flex;align-items:flex-start;gap:10px;margin:16px 0 22px;padding:13px;border-radius:8px;
  background:rgba(52,214,255,.08);border:1px solid rgba(52,214,255,.18);color:var(--muted);font-size:.86rem;line-height:1.55;
}
.login-helper strong{color:var(--blue)}
.login-steps{display:grid;gap:10px;margin-top:22px}
.login-step{display:flex;gap:10px;align-items:center;color:var(--muted);font-size:.92rem}
.login-step i{width:24px;height:24px;border-radius:50%;display:grid;place-items:center;background:rgba(49,233,129,.1);border:1px solid rgba(49,233,129,.22);color:var(--green);font-style:normal;font-family:var(--mono);font-size:.78rem}
.trust-strip{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}
.trust-pill{border:1px solid var(--line);border-radius:999px;background:rgba(255,255,255,.045);padding:8px 10px;color:var(--muted);font-size:.78rem;font-weight:800}
.back-top{
  position:fixed;right:18px;bottom:18px;z-index:40;width:44px;height:44px;border-radius:8px;border:1px solid var(--line);
  background:rgba(6,10,14,.76);backdrop-filter:blur(14px);color:var(--gold);font:900 1rem var(--font);cursor:pointer;
  opacity:0;pointer-events:none;transform:translateY(10px);transition:.2s;box-shadow:0 14px 36px rgba(0,0,0,.28)
}
.back-top.visible{opacity:1;pointer-events:auto;transform:none}
.back-top:hover{border-color:rgba(247,201,72,.45);background:rgba(247,201,72,.12)}
.alerta{display:flex;align-items:center;gap:10px;padding:13px 14px;border-radius:8px;margin-bottom:12px;font-weight:700;font-size:.9rem}
.alerta-red{background:rgba(255,85,112,.12);border:1px solid rgba(255,85,112,.28);color:#ff94a6}
.alerta-yellow{background:rgba(247,201,72,.12);border:1px solid rgba(247,201,72,.28);color:var(--gold)}
.alerta-blue{background:rgba(52,214,255,.1);border:1px solid rgba(52,214,255,.26);color:var(--blue)}
.alerta-green{background:rgba(49,233,129,.1);border:1px solid rgba(49,233,129,.26);color:var(--green)}
.metric-card{position:relative;border:1px solid var(--line);border-radius:8px;padding:20px;background:rgba(255,255,255,.045);overflow:hidden}
.metric-card::before{content:"";position:absolute;left:0;top:0;right:0;height:3px;background:var(--mc-color,var(--gold));box-shadow:0 0 22px var(--mc-color,var(--gold))}
.metric-icon{position:absolute;right:16px;top:14px;color:var(--mc-color,var(--gold));opacity:.35;font-size:1.2rem}
.metric-label{font-size:.68rem;color:var(--muted);letter-spacing:.14em;font-weight:900;text-transform:uppercase;margin-bottom:12px}
.metric-value{font-family:var(--mono);font-size:2.4rem;line-height:1;color:var(--text);text-shadow:0 0 22px var(--mc-color,var(--gold))}
.metric-unit{font-size:.82rem;color:var(--muted);margin-top:8px}
.bt-status{display:inline-flex;align-items:center;gap:8px;padding:10px 13px;border-radius:6px;border:1px solid var(--line);background:rgba(255,255,255,.04);font-family:var(--mono);font-size:.82rem;color:var(--muted)}
.bt-dot{width:9px;height:9px;border-radius:50%;background:var(--muted)}
.bt-status.on{color:var(--green);border-color:rgba(49,233,129,.4);background:rgba(49,233,129,.08)}
.bt-status.on .bt-dot{background:var(--green);animation:pulse 1.7s infinite}
.bt-status.err{color:var(--red);border-color:rgba(255,85,112,.4);background:rgba(255,85,112,.08)}
.bt-status.err .bt-dot{background:var(--red)}
.console{height:158px;overflow:auto;display:flex;flex-direction:column-reverse;border:1px solid rgba(255,255,255,.08);border-radius:8px;background:#05080b;padding:13px;font-family:var(--mono);font-size:.82rem;color:var(--muted)}
.console-line{padding:3px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.console-ok{color:var(--green)}.console-err{color:var(--red)}.console-warn{color:var(--gold)}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.9rem}
th,td{padding:12px 13px;border-bottom:1px solid rgba(255,255,255,.075);text-align:left}
th{color:var(--muted);font-size:.75rem;letter-spacing:.08em;text-transform:uppercase}
td{font-family:var(--mono)}
#csv-panel{display:none;margin-bottom:22px}
#csv-panel.visible-panel{display:block;animation:fadeUp .24s ease}
.fade-in-scroll{opacity:0;transform:translateY(24px);transition:opacity .75s ease,transform .75s ease}
.fade-in-scroll.visible{opacity:1;transform:none}
footer{margin-top:70px;border-top:1px solid var(--line);padding:26px 0;color:var(--muted);font-size:.86rem;background:rgba(0,0,0,.22)}
@media(max-width:980px){
  .hero,.login-shell{grid-template-columns:1fr}
  .hero{min-height:auto;padding-top:28px}
  .hero-visual{min-height:410px}
  .grid-3,.grid-4{grid-template-columns:repeat(2,minmax(0,1fr))}
  .wide-band{grid-template-columns:1fr}
  .media-lab{grid-template-columns:1fr}
  .flow-steps{grid-template-columns:repeat(2,1fr)}
  .pricing-strip{grid-template-columns:1fr}
}
@media(max-width:720px){
  .wrap{width:min(calc(100% - 26px),1200px)}
  header{position:relative;top:auto;flex-direction:column;align-items:flex-start}
  nav{justify-content:flex-start}
  nav a{padding:8px 9px}
  .hero h1{font-size:clamp(3rem,17vw,5rem)}
  .hero-stats,.grid-2,.grid-3,.grid-4{grid-template-columns:1fr}
  .hero-visual{min-height:330px}
  .telemetry-card{right:8px;bottom:12px;width:205px}
  .card{padding:22px}
  .flow-steps{grid-template-columns:1fr}
  .visual-flow{min-height:auto}
  .flow-line{display:none}
}
</style>
</head>
<body>
<div class="site-glow" aria-hidden="true"></div>
<div class="wrap">
<header>
  <a href="?ver=inicio" class="logo-container" aria-label="Inicio Flownergy">
    <span class="logo-mark">
      <img src="../assets/logo-flownergy.svg" alt="" onerror="this.remove();this.parentElement.append('F');">
    </span>
    <span>
      <span class="logo-word">Flownergy</span>
      <span class="logo-sub">Energy Intelligence</span>
    </span>
  </a>
  <nav>
    <a href="?ver=inicio" class="<?= $seccion==='inicio' ? 'active':'' ?>">Inicio</a>
    <a href="?ver=nosotros" class="<?= $seccion==='nosotros' ? 'active':'' ?>">Nosotros</a>
    <a href="?ver=contacto" class="<?= $seccion==='contacto' ? 'active':'' ?>">Contacto</a>
    <?php if(isset($_SESSION['usuario'])): ?>
      <a href="?ver=dashboard" class="<?= $seccion==='dashboard'?'active':'' ?>">Dashboard</a>
      <?php if($maquina): ?>
        <a href="?ver=medicion" class="<?= $seccion==='medicion'?'active':'' ?>">Medicion</a>
      <?php endif; ?>
      <span class="user-badge">ID: <?= $_SESSION['usuario'] ?></span>
      <a href="../api/cerrar.php" class="btn-nav">Salir</a>
    <?php else: ?>
      <a href="?ver=acceder" class="btn-nav">Acceder</a>
    <?php endif; ?>
  </nav>
</header>
</div>

<main class="wrap">
<?php if($seccion === 'inicio'): ?>

<section class="hero">
  <div class="hero-copy">
    <span class="kicker">IoT energetico industrial</span>
    <h1>Energia que <span class="text-energy">fluye</span> con inteligencia.</h1>
    <p class="sub">Flownergy convierte maquinas electricas en activos medibles: telemetria Bluetooth, analitica de consumo, alertas preventivas y una experiencia lista para vender a microempresas de Yumbo.</p>
    <div class="hero-actions">
      <a href="?ver=acceder" class="btn btn-gold">Entrar al panel</a>
      <a href="?ver=contacto" class="btn btn-outline">Solicitar demo</a>
    </div>
    <div class="hero-stats">
      <div class="stat"><strong>24/7</strong><span>Monitoreo operativo</span></div>
      <div class="stat"><strong>5 min</strong><span>Historico exportable</span></div>
      <div class="stat"><strong>ESP32</strong><span>Hardware accesible</span></div>
    </div>
  </div>
  <div class="hero-visual" aria-hidden="true">
    <div class="energy-device">
      <div class="device-ring"></div>
      <div class="device-ring"></div>
      <div class="device-core"></div>
      <div class="telemetry-card">
        <div class="telemetry-row"><span>Voltaje</span><strong>118.7 V</strong></div>
        <div class="telemetry-row"><span>Potencia</span><strong>942 W</strong></div>
        <div class="telemetry-row"><span>Factor P.</span><strong>0.96</strong></div>
        <div class="telemetry-row"><span>Estado</span><strong style="color:var(--green)">OK</strong></div>
      </div>
    </div>
  </div>
</section>

<section class="section fade-in-scroll">
  <span class="kicker">Sistema completo</span>
  <div class="sec-title">Del sensor al negocio.</div>
  <p class="sec-sub">La pagina comunica el valor del proyecto sin perder su parte tecnica: hardware, prevencion, ahorro y datos accionables para tomar decisiones.</p>
  <div class="grid-3">
    <div class="card feature-card">
      <div class="feature-icon">BT</div>
      <h3>Conexion Bluetooth</h3>
      <p>ESP32 WROOM-32 envia lecturas en tiempo real al navegador mediante Web Serial, sin cambiar la logica de medicion existente.</p>
    </div>
    <div class="card feature-card">
      <div class="feature-icon">PZ</div>
      <h3>Medicion precisa</h3>
      <p>PZEM-004T registra voltaje, corriente, potencia, energia, frecuencia y factor de potencia para entender la carga.</p>
    </div>
    <div class="card feature-card">
      <div class="feature-icon">AI</div>
      <h3>Alertas preventivas</h3>
      <p>El historico permite detectar picos, bajos de consumo y factor de potencia ineficiente antes de que se vuelva costo.</p>
    </div>
  </div>
</section>

<section class="section fade-in-scroll">
  <span class="kicker">Por que escogernos</span>
  <div class="sec-title">Ahorro real sin comprar tecnologia costosa.</div>
  <p class="sec-sub">Flownergy se diferencia porque entrega monitoreo energetico efectivo, facil de implementar y pensado para empresas que necesitan controlar costos desde el primer mes.</p>
  <div class="grid-3">
    <div class="card why-card">
      <strong>01</strong>
      <h3>Solucion efectiva</h3>
      <p>Medimos datos utiles de la maquina, detectamos anomalias y convertimos la energia en decisiones claras para prevenir fallas y desperdicio.</p>
    </div>
    <div class="card why-card">
      <strong>02</strong>
      <h3>Bajo costo</h3>
      <p>Usamos hardware accesible como ESP32 y PZEM-004T para ofrecer una alternativa mas economica frente a sistemas industriales tradicionales.</p>
    </div>
    <div class="card why-card">
      <strong>03</strong>
      <h3>Suscripcion mensual</h3>
      <p>El modelo mensual permite acceder al monitoreo sin una inversion inicial alta, manteniendo soporte, mejoras y visualizacion continua.</p>
    </div>
  </div>
  <div class="pricing-strip">
    <div>
      <h3>Pagas menos para consumir mejor.</h3>
      <p>Nuestra propuesta es que la empresa ahorre energia y reduzca riesgos pagando una suscripcion de bajo costo, con informacion suficiente para actuar antes de que el problema sea caro.</p>
    </div>
    <div class="price-badge">
      <span>Factor diferenciador</span>
      <strong>Ahorro mensual</strong>
      <p style="color:var(--muted);line-height:1.6;margin-top:10px;">Monitoreo, alertas y reportes al costo mas bajo posible para microempresas.</p>
    </div>
  </div>
</section>

<section class="section fade-in-scroll">
  <div class="wide-band">
    <div>
      <span class="kicker">Visual comercial</span>
      <div class="sec-title" style="font-size:clamp(2rem,4vw,4.8rem)">Energia clara para empresas reales.</div>
      <p class="sec-sub" style="margin-bottom:0">Ahorro energetico, seguridad electrica y mantenimiento predictivo en una interfaz que se siente como producto, no como prototipo.</p>
    </div>
    <a href="?ver=acceder" class="btn btn-gold">Probar dashboard</a>
  </div>
</section>

<section class="section fade-in-scroll">
  <div class="grid-4">
    <?php
    $beneficios = [
      ['01','Prevencion de incendios','Detecta sobrecargas y condiciones de voltaje peligrosas.'],
      ['02','Ahorro energetico','Identifica patrones anormales y consumos innecesarios.'],
      ['03','Mantenimiento','Usa el factor de potencia como senal temprana de revision.'],
      ['04','Reportes CSV','Exporta lecturas por rango para analisis y evidencias.'],
    ];
    foreach($beneficios as $b): ?>
      <div class="card benefit-card">
        <span class="kicker"><?= $b[0] ?></span>
        <h3 style="margin-top:16px;"><?= $b[1] ?></h3>
        <p><?= $b[2] ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section fade-in-scroll">
  <div class="grid-2">
    <div class="placeholder">Espacio listo para imagen real del prototipo: guarda tu foto como<br><strong>flownergy/assets/prototipo-flownergy.jpg</strong></div>
    <div class="placeholder">Espacio listo para mockup o instalacion industrial:<br><strong>flownergy/assets/instalacion-yumbo.jpg</strong></div>
  </div>
</section>

<section class="section fade-in-scroll">
  <span class="kicker">Prototipo y speech</span>
  <div class="sec-title">Muestra el proyecto como una solucion lista.</div>
  <p class="sec-sub">Este bloque esta preparado para poner la foto real del prototipo y un enlace al video speech. La carga de imagen es una vista previa local para presentaciones; no cambia la base de datos ni el funcionamiento del sistema.</p>
  <div class="media-lab">
    <div class="prototype-uploader">
      <input type="file" id="prototype-upload" accept="image/*" aria-label="Subir imagen del prototipo">
      <img id="prototype-preview" class="prototype-preview" alt="Vista previa del prototipo Flownergy">
      <div class="prototype-empty" id="prototype-empty">
        <div>
          <span class="kicker">Subir imagen del prototipo</span>
          <strong>Arrastra o selecciona una foto del dispositivo.</strong>
          <span>Ideal: caja del ESP32, sensor PZEM, rele, LCD o instalacion en maquina.</span>
        </div>
      </div>
      <div class="prototype-frame"></div>
    </div>
    <div class="speech-panel">
      <div>
        <span class="kicker">Video speech</span>
        <div class="video-orb"><div class="play-triangle"></div></div>
        <h3 style="font-size:clamp(1.8rem,3vw,3rem);line-height:1;letter-spacing:-.05em;text-align:center;">Pitch del proyecto en un solo clic.</h3>
        <p style="color:var(--muted);line-height:1.75;text-align:center;margin-top:16px;">Pon aqui el enlace a tu video explicando problema, solucion, impacto, ODS y demostracion del prototipo.</p>
      </div>
      <a href="<?= htmlspecialchars($video_speech_url) ?>" target="_blank" class="btn btn-gold btn-full" <?= $video_speech_url === '#' ? 'onclick="event.preventDefault(); alert(\'Edita $video_speech_url en index.php con el link de tu speech.\');"' : '' ?>>Ver video speech</a>
    </div>
  </div>
</section>

<section class="section fade-in-scroll">
  <div class="visual-flow">
    <div class="flow-line"></div>
    <div class="flow-steps">
      <div class="flow-step"><strong>01 SENSOR</strong><span>PZEM-004T lee la red electrica</span></div>
      <div class="flow-step"><strong>02 ESP32</strong><span>Procesa y envia por Bluetooth</span></div>
      <div class="flow-step"><strong>03 DASHBOARD</strong><span>Visualiza metricas y alertas</span></div>
      <div class="flow-step"><strong>04 DECISION</strong><span>Ahorro, seguridad y mantenimiento</span></div>
    </div>
  </div>
</section>

<?php elseif($seccion === 'nosotros'): ?>

<section class="section fade-in-scroll">
  <span class="kicker">Proposito</span>
  <div class="sec-title">Tecnologia energetica para crecer con control.</div>
  <p class="sec-sub">Flownergy nace para que una microempresa pueda ver, entender y actuar sobre su consumo electrico sin comprar infraestructura costosa.</p>
  <div class="grid-2" style="margin-bottom:18px;">
    <div class="card">
      <span class="kicker">Mision</span>
      <p style="margin-top:18px;color:var(--muted);line-height:1.8;font-size:1.04rem;"><?= $proyecto['mision'] ?></p>
    </div>
    <div class="card">
      <span class="kicker">Vision</span>
      <p style="margin-top:18px;color:var(--muted);line-height:1.8;font-size:1.04rem;"><?= $proyecto['vision'] ?></p>
    </div>
  </div>
  <div class="grid-4" style="margin-bottom:18px;">
    <?php foreach($proyecto['valores'] as $v): ?>
      <div class="card card-sm">
        <h3><?= $v['tag'] ?></h3>
        <p style="color:var(--muted);line-height:1.6;margin-top:8px;"><?= $v['desc'] ?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card" style="margin-bottom:18px;">
    <span class="kicker">Equipo</span>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:22px;">
      <div style="text-align:center;">
        <div class="team-avatar">FY</div>
        <div style="font-weight:900;font-size:1.1rem;">Flownergy SAS</div>
        <div style="color:var(--gold);font-size:.9rem;margin-top:5px;">Desarrollador y Lider IoT Yumbeño</div>
      </div>
    </div>
  </div>
  <div class="card">
    <span class="kicker">ODS</span>
    <div class="grid-4" style="margin-top:18px;">
      <?php foreach($proyecto['ods'] as $ods): ?>
        <a class="ods-badge" href="<?= htmlspecialchars($ods['url']) ?>" target="_blank" rel="noopener" style="border-color:<?= $ods['color'] ?>55;background:<?= $ods['color'] ?>14;">
          <strong style="color:<?= $ods['color'] ?>;"><?= $ods['num'] ?></strong>
          <span><?= $ods['titulo'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif($seccion === 'contacto'): ?>

<section class="section fade-in-scroll" style="text-align:center;">
  <span class="kicker">Contacto comercial</span>
  <div class="sec-title">Llevemos Flownergy a una maquina real.</div>
  <p class="sec-sub" style="margin-left:auto;margin-right:auto;">Escribenos para una demo, una instalacion piloto o para revisar como adaptar el sistema a tu empresa.</p>
  <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-bottom:28px;">
    <a href="https://wa.me/573225200707?text=Hola%2C%20me%20interesa%20Flownergy" target="_blank" class="btn btn-gold">WhatsApp: 322 520 0707</a>
    <a href="mailto:alejandrodiazramirez2020@gmail.com?subject=Consulta%20Flownergy" class="btn btn-outline">Enviar correo</a>
  </div>
  <div class="card" style="max-width:560px;margin:0 auto;text-align:left;">
    <span class="kicker">Datos</span>
    <p style="margin-top:18px;color:var(--muted);line-height:2;">
      WhatsApp: <strong style="color:var(--text);">+57 322 520 0707</strong><br>
      Email: <strong style="color:var(--text);">alejandrodiazramirez2020@gmail.com</strong><br>
      Ubicacion: <strong style="color:var(--text);">Yumbo, Valle del Cauca, Colombia</strong>
    </p>
  </div>
</section>

<?php elseif($seccion === 'acceder'): ?>

<section class="login-shell fade-in-scroll">
  <div class="login-pitch">
    <span class="kicker">Acceso seguro</span>
    <h1>Controla la energia antes de que la energia te controle.</h1>
    <p class="sec-sub">Ingresa al panel para registrar equipos, consultar historicos, exportar datos y medir en vivo desde el ESP32.</p>
    <div class="login-mini-grid">
      <div class="login-mini-card"><strong>BT</strong><span>Conexion local</span></div>
      <div class="login-mini-card"><strong>CSV</strong><span>Reportes</span></div>
      <div class="login-mini-card"><strong>IoT</strong><span>Tiempo real</span></div>
    </div>
    <div class="login-steps">
      <div class="login-step"><i>1</i><span>Accede con tu ID de usuario.</span></div>
      <div class="login-step"><i>2</i><span>Registra la maquina que vas a monitorear.</span></div>
      <div class="login-step"><i>3</i><span>Conecta Bluetooth y empieza la telemetria.</span></div>
    </div>
    <div class="trust-strip">
      <span class="trust-pill">Chrome / Edge recomendado</span>
      <span class="trust-pill">Datos locales</span>
      <span class="trust-pill">Lecturas cada 5 min</span>
    </div>
  </div>
  <div class="card login-card">
    <div class="lock-orbit">F</div>
    <h2 style="text-align:center;font-size:1.85rem;margin-bottom:8px;">Acceso Flownergy</h2>
    <p style="text-align:center;color:var(--muted);margin-bottom:24px;">Credenciales del panel energetico</p>
    <?php if($error_login): ?>
      <div class="alerta alerta-red">ID o contrasena incorrectos</div>
    <?php endif; ?>
    <div class="login-helper">
      <span style="color:var(--blue);font-family:var(--mono);">INFO</span>
      <span><strong>Tip:</strong> para usar Bluetooth desde el navegador, abre el panel en Chrome o Edge y ten el ESP32 emparejado como los tecnicos lo indiquen.</span>
    </div>
    <form action="../api/validar.php" method="POST">
      <div class="input-group">
        <label>ID de usuario</label>
        <input type="number" name="usuario" placeholder="Ej: 110" required autofocus/>
      </div>
      <div class="input-group" style="margin-bottom:22px;">
        <label>Contrasena</label>
        <div class="password-field">
          <input type="password" name="password" id="login-password" placeholder="••••••" required/>
          <button type="button" class="password-toggle" id="password-toggle" aria-controls="login-password" aria-pressed="false">Mostrar</button>
        </div>
      </div>
      <button type="submit" class="btn btn-gold btn-full">Entrar al panel</button>
    </form>
    <p style="color:var(--muted-2);font-size:.78rem;line-height:1.6;text-align:center;margin-top:18px;">Tu sesion habilita dashboard, medicion en vivo y exportacion de datos para la maquina registrada.</p>
  </div>
</section>

<?php elseif($seccion === 'dashboard'): ?>

<section class="section fade-in-scroll">
<?php if($registrado): ?>
  <div class="alerta alerta-green">Maquina registrada correctamente. Ya puedes empezar a medir.</div>
<?php endif; ?>

<?php if(!$maquina): ?>
  <div style="max-width:680px;margin:0 auto;">
    <span class="kicker">Primer paso</span>
    <div class="sec-title">Registra tu maquina.</div>
    <p class="sec-sub">Asocia el equipo que vas a monitorear para activar medicion, historico y exportacion.</p>
    <div class="card">
      <form action="../api/registrar_maquina.php" method="POST">
        <div class="grid-2">
          <div class="input-group">
            <label>Numero serial</label>
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
            <label>Version del equipo</label>
            <input type="text" name="version" placeholder="1.0"/>
          </div>
        </div>
        <button type="submit" class="btn btn-gold btn-full" style="margin-top:8px;">Registrar maquina</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:22px;">
    <div>
      <span class="kicker">Equipo activo</span>
      <div class="sec-title" style="font-size:clamp(2rem,4vw,4rem);"><?= htmlspecialchars($maquina['Nombre_tipo']) ?></div>
      <p style="color:var(--muted);font-family:var(--mono);">
        Serial: <?= $maquina['Id_serial'] ?> | <?= $maquina['Voltaje_nominal'] ?>V nominal | v<?= $maquina['Version'] ?>
      </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="?ver=medicion" class="btn btn-green">Empezar medicion</a>
      <button onclick="document.getElementById('csv-panel').classList.toggle('visible-panel')" class="btn btn-outline">Exportar CSV</button>
    </div>
  </div>

  <div id="csv-panel">
    <div class="card card-sm">
      <span class="kicker">Exportacion</span>
      <form action="../api/exportar_csv.php" method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-top:16px;">
        <input type="hidden" name="id_serial" value="<?= $serial ?>"/>
        <div class="input-group" style="margin:0;flex:1;min-width:170px;">
          <label>Desde</label>
          <input type="date" name="desde" value="<?= date('Y-m-d', strtotime('-7 days')) ?>"/>
        </div>
        <div class="input-group" style="margin:0;flex:1;min-width:170px;">
          <label>Hasta</label>
          <input type="date" name="hasta" value="<?= date('Y-m-d') ?>"/>
        </div>
        <button type="submit" class="btn btn-blue btn-sm">Descargar</button>
      </form>
    </div>
  </div>

  <?php if(!empty($alertas_db)): ?>
    <div style="margin-bottom:22px;">
      <span class="kicker">Alertas recientes</span>
      <div style="margin-top:12px;">
      <?php foreach(array_slice($alertas_db,0,3) as $al):
        $cls = in_array($al['Tipo'],['pico_potencia','sobrecarga','voltaje_alto']) ? 'alerta-red'
             : ($al['Tipo']==='fp_bajo'||$al['Tipo']==='mantenimiento' ? 'alerta-yellow' : 'alerta-blue');
      ?>
        <div class="alerta <?= $cls ?>">
          <?= htmlspecialchars($al['Mensaje']) ?>
          <span style="margin-left:auto;font-family:var(--mono);opacity:.72;white-space:nowrap;"><?= date('d/m H:i', strtotime($al['Fecha_hora'])) ?></span>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:22px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:18px;">
      <div>
        <span class="kicker">Historico 24H</span>
        <h3 style="font-size:1.4rem;margin-top:12px;">Consumo y calidad electrica</h3>
        <p style="color:var(--muted);margin-top:5px;">Mediciones agrupadas cada 5 minutos</p>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:.82rem;">
        <span>Potencia W</span><span>Voltaje V</span><span>FP x100</span>
      </div>
    </div>
    <?php if(empty($historico)): ?>
      <div class="placeholder" style="min-height:190px;">No hay datos historicos aun. Inicia una medicion para registrar lecturas.</div>
    <?php else: ?>
      <canvas id="grafica-historico" height="190"></canvas>
    <?php endif; ?>
  </div>

  <?php if(!empty($historico)): ?>
    <div class="card">
      <span class="kicker">Ultimas lecturas</span>
      <div class="table-wrap" style="margin-top:16px;">
        <table>
          <thead>
            <tr>
              <th>Fecha/Hora</th><th>Voltaje</th><th>Corriente</th><th>Potencia</th><th>Energia</th><th>FP</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach(array_reverse(array_slice($historico,-8)) as $h): ?>
            <tr>
              <td><?= $h['intervalo'] ?></td>
              <td style="color:var(--blue);"><?= $h['voltaje'] ?> V</td>
              <td><?= $h['corriente'] ?> A</td>
              <td style="color:var(--gold);font-weight:900;"><?= $h['potencia'] ?> W</td>
              <td><?= $h['energia'] ?> kWh</td>
              <td style="color:<?= $h['fp']<0.85?'var(--red)':'var(--green)' ?>"><?= $h['fp'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
</section>

<?php elseif($seccion === 'medicion'): ?>

<section class="section fade-in-scroll">
<?php if(!$maquina): ?>
  <div class="alerta alerta-yellow">Primero registra tu maquina en el <a href="?ver=dashboard" style="color:var(--gold);">Dashboard</a>.</div>
<?php else: ?>
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:18px;flex-wrap:wrap;margin-bottom:22px;">
    <div>
      <span class="kicker">Telemetria en vivo</span>
      <div class="sec-title" style="font-size:clamp(2rem,4vw,4rem);">Medicion en tiempo real</div>
      <p style="color:var(--muted);font-family:var(--mono);">
        <?= htmlspecialchars($maquina['Nombre_tipo']) ?> | Serial: <?= $maquina['Id_serial'] ?>
      </p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <div class="bt-status" id="bt-badge"><div class="bt-dot"></div><span id="bt-text">DESCONECTADO</span></div>
      <button class="btn btn-blue" id="btn-bt" onclick="toggleBluetooth()">Conectar Bluetooth</button>
    </div>
  </div>

  <div id="alerta-sistema" style="display:none;margin-bottom:16px;"></div>

  <div class="grid-4" style="margin-bottom:18px;">
    <div class="metric-card" style="--mc-color:var(--blue);">
      <div class="metric-icon">V</div>
      <div class="metric-label">Voltaje</div>
      <div class="metric-value" id="m-voltaje">---</div>
      <div class="metric-unit">Voltios (V)</div>
    </div>
    <div class="metric-card" style="--mc-color:var(--amber);">
      <div class="metric-icon">A</div>
      <div class="metric-label">Corriente</div>
      <div class="metric-value" id="m-corriente">---</div>
      <div class="metric-unit">Amperios (A)</div>
    </div>
    <div class="metric-card" style="--mc-color:var(--green);">
      <div class="metric-icon">W</div>
      <div class="metric-label">Potencia</div>
      <div class="metric-value" id="m-potencia">---</div>
      <div class="metric-unit">Vatios (W)</div>
    </div>
    <div class="metric-card" style="--mc-color:var(--gold);">
      <div class="metric-icon">FP</div>
      <div class="metric-label">Factor P.</div>
      <div class="metric-value" id="m-fp">---</div>
      <div class="metric-unit">Cos phi</div>
    </div>
  </div>

  <div class="grid-2" style="margin-bottom:18px;">
    <div class="card">
      <span class="kicker">Control</span>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
        <button class="btn btn-green" onclick="sendBTCommand('START')">Iniciar rele</button>
        <button class="btn btn-red" onclick="sendBTCommand('STOP')">Detener</button>
        <button class="btn btn-outline" onclick="sendBTCommand('RESET_ENERGY')">Reset energia</button>
      </div>
      <p style="color:var(--muted);line-height:1.7;margin-top:16px;">Estos controles envian los mismos comandos esperados por el firmware del ESP32.</p>
    </div>
    <div class="card">
      <span class="kicker">Lectura extendida</span>
      <div class="telemetry-row"><span>Energia</span><strong id="m-energia">---</strong></div>
      <div class="telemetry-row"><span>Frecuencia</span><strong id="m-frecuencia">---</strong></div>
      <div class="telemetry-row"><span>Rele</span><strong id="m-rele">---</strong></div>
    </div>
  </div>

  <div class="card">
    <span class="kicker">Terminal Bluetooth</span>
    <div class="console" id="bt-console" style="margin-top:16px;">
      <div class="console-line">Esperando conexion...</div>
    </div>
  </div>
<?php endif; ?>
</section>

<?php endif; ?>
</main>

<button class="back-top" id="back-top" type="button" aria-label="Volver arriba">↑</button>

<footer>
  <div class="wrap" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <span>&copy; <?= date('Y') ?> <strong>Flownergy</strong>. Monitor de energia industrial IoT.</span>
    <span style="font-family:var(--mono);">ESP32 + PZEM-004T + Bluetooth</span>
  </div>
</footer>

<script>
const FLOWNERGY_SERIAL = <?= json_encode($serial) ?>;
const CHART_LABELS = <?= $json_labels ?: '[]' ?>;
const CHART_POTENCIA = <?= $json_potencia ?: '[]' ?>;
const CHART_VOLTAJE = <?= $json_voltaje ?: '[]' ?>;
const CHART_FP = <?= $json_fp ?: '[]' ?>;

let serialPort = null;
let serialReader = null;
let serialWriter = null;
let btConnected = false;
let latestReading = null;
let lastSaveAt = 0;
const SAVE_INTERVAL_MS = 5 * 60 * 1000;

document.addEventListener('mousemove', (event) => {
  document.documentElement.style.setProperty('--mx', `${event.clientX}px`);
  document.documentElement.style.setProperty('--my', `${event.clientY}px`);
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.fade-in-scroll').forEach((el) => {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
      });
    }, { threshold: 0.12 });
    observer.observe(el);
  });
  renderHistoricalChart();
  initPrototypePreview();
  initLoginEnhancements();
  initBackTop();
});

function initPrototypePreview() {
  const input = document.getElementById('prototype-upload');
  const preview = document.getElementById('prototype-preview');
  const empty = document.getElementById('prototype-empty');
  if (!input || !preview || !empty) return;
  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      preview.src = reader.result;
      preview.style.display = 'block';
      empty.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });
}

function initLoginEnhancements() {
  const password = document.getElementById('login-password');
  const toggle = document.getElementById('password-toggle');
  if (password && toggle) {
    toggle.addEventListener('click', () => {
      const visible = password.type === 'text';
      password.type = visible ? 'password' : 'text';
      toggle.textContent = visible ? 'Mostrar' : 'Ocultar';
      toggle.setAttribute('aria-pressed', String(!visible));
      password.focus();
    });
  }

  document.querySelectorAll('.login-card input').forEach((input) => {
    input.addEventListener('input', () => {
      input.style.borderColor = input.value ? 'rgba(49,233,129,.48)' : '';
    });
  });
}

function initBackTop() {
  const button = document.getElementById('back-top');
  if (!button) return;
  const update = () => button.classList.toggle('visible', window.scrollY > 520);
  update();
  window.addEventListener('scroll', update, { passive: true });
  button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

function renderHistoricalChart() {
  const canvas = document.getElementById('grafica-historico');
  if (!canvas || typeof Chart === 'undefined') return;
  const ctx = canvas.getContext('2d');
  const fpScaled = CHART_FP.map((value) => Number(value) * 100);
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: CHART_LABELS,
      datasets: [
        { label: 'Potencia (W)', data: CHART_POTENCIA, borderColor: '#f7c948', backgroundColor: 'rgba(247,201,72,.12)', borderWidth: 2, tension: .35, pointRadius: 0, fill: true },
        { label: 'Voltaje (V)', data: CHART_VOLTAJE, borderColor: '#34d6ff', borderWidth: 2, tension: .35, pointRadius: 0 },
        { label: 'FP x100', data: fpScaled, borderColor: '#31e981', borderWidth: 2, tension: .35, pointRadius: 0 }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { labels: { color: '#9aa8b6', usePointStyle: true } } },
      scales: {
        x: { ticks: { color: '#667584', maxRotation: 0, autoSkip: true }, grid: { color: 'rgba(255,255,255,.06)' } },
        y: { ticks: { color: '#667584' }, grid: { color: 'rgba(255,255,255,.06)' } }
      }
    }
  });
}

function logBT(message, type = '') {
  const consoleEl = document.getElementById('bt-console');
  if (!consoleEl) return;
  const line = document.createElement('div');
  line.className = `console-line ${type}`;
  line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
  consoleEl.prepend(line);
}

function setBTStatus(status, text) {
  const badge = document.getElementById('bt-badge');
  const label = document.getElementById('bt-text');
  const button = document.getElementById('btn-bt');
  if (!badge || !label) return;
  badge.classList.remove('on', 'err');
  if (status) badge.classList.add(status);
  label.textContent = text;
  if (button) button.textContent = status === 'on' ? 'Desconectar' : 'Conectar Bluetooth';
}

async function toggleBluetooth() {
  if (btConnected) {
    await disconnectBluetooth();
    return;
  }
  if (!('serial' in navigator)) {
    setBTStatus('err', 'NO SOPORTADO');
    showSystemAlert('Tu navegador no soporta Web Serial. Usa Chrome o Edge.', 'alerta-red');
    return;
  }
  try {
    serialPort = await navigator.serial.requestPort();
    await serialPort.open({ baudRate: 115200 });
    serialWriter = serialPort.writable.getWriter();
    btConnected = true;
    setBTStatus('on', 'CONECTADO');
    showSystemAlert('Bluetooth conectado. Puedes iniciar la medicion.', 'alerta-green');
    logBT('Puerto serial conectado.', 'console-ok');
    readBluetoothLoop();
  } catch (error) {
    setBTStatus('err', 'ERROR');
    logBT(`Error de conexion: ${error.message}`, 'console-err');
    showSystemAlert('No se pudo conectar al dispositivo Bluetooth.', 'alerta-red');
  }
}

async function disconnectBluetooth() {
  try {
    btConnected = false;
    if (serialReader) {
      await serialReader.cancel();
      serialReader.releaseLock();
      serialReader = null;
    }
    if (serialWriter) {
      serialWriter.releaseLock();
      serialWriter = null;
    }
    if (serialPort) {
      await serialPort.close();
      serialPort = null;
    }
    setBTStatus('', 'DESCONECTADO');
    logBT('Puerto serial desconectado.', 'console-warn');
  } catch (error) {
    logBT(`Error al desconectar: ${error.message}`, 'console-err');
  }
}

async function readBluetoothLoop() {
  const decoder = new TextDecoderStream();
  const readableClosed = serialPort.readable.pipeTo(decoder.writable).catch(() => {});
  serialReader = decoder.readable.getReader();
  let buffer = '';
  try {
    while (btConnected) {
      const { value, done } = await serialReader.read();
      if (done) break;
      buffer += value;
      const lines = buffer.split(/\r?\n/);
      buffer = lines.pop() || '';
      lines.map((line) => line.trim()).filter(Boolean).forEach(handleBTLine);
    }
  } catch (error) {
    if (btConnected) logBT(`Lectura interrumpida: ${error.message}`, 'console-err');
  } finally {
    try { serialReader.releaseLock(); } catch (error) {}
    await readableClosed;
  }
}

function handleBTLine(line) {
  logBT(line);
  try {
    const data = JSON.parse(line);
    if (data.cmd_ack) {
      logBT(`Comando confirmado: ${data.cmd_ack}`, 'console-ok');
      return;
    }
    latestReading = data;
    updateMetrics(data);
    evaluateReading(data);
    maybeSaveReading(data);
  } catch (error) {
    // El firmware puede enviar mensajes de debug que no son JSON.
  }
}

function updateMetrics(data) {
  setMetric('m-voltaje', data.voltaje, 1);
  setMetric('m-corriente', data.corriente, 2);
  setMetric('m-potencia', data.potencia, 0);
  setMetric('m-fp', data.fp, 2);
  setMetric('m-energia', data.energia, 4, ' kWh');
  setMetric('m-frecuencia', data.frecuencia, 1, ' Hz');
  const rele = document.getElementById('m-rele');
  if (rele) rele.textContent = data.rele ? 'ACTIVO' : 'INACTIVO';
}

function setMetric(id, value, decimals, suffix = '') {
  const el = document.getElementById(id);
  if (!el) return;
  const numeric = Number(value);
  el.textContent = Number.isFinite(numeric) ? `${numeric.toFixed(decimals)}${suffix}` : '---';
}

function evaluateReading(data) {
  const estado = data.estado || 'ok';
  if (estado === 'sobrecarga') showSystemAlert('Sobrecarga detectada. El rele fue desactivado por seguridad.', 'alerta-red');
  else if (estado === 'voltaje_anormal') showSystemAlert('Voltaje fuera de rango. Revisa la alimentacion electrica.', 'alerta-yellow');
  else if (estado === 'pf_bajo') showSystemAlert('Factor de potencia bajo. Recomendacion: mantenimiento preventivo.', 'alerta-yellow');
  else if (estado === 'sin_red') showSystemAlert('Sin lectura de red electrica.', 'alerta-blue');
  else showSystemAlert('Sistema estable. Lecturas dentro del rango esperado.', 'alerta-green');
}

function showSystemAlert(message, cls) {
  const alert = document.getElementById('alerta-sistema');
  if (!alert) return;
  alert.className = `alerta ${cls}`;
  alert.textContent = message;
  alert.style.display = 'flex';
}

async function sendBTCommand(command) {
  if (!btConnected || !serialWriter) {
    showSystemAlert('Conecta Bluetooth antes de enviar comandos.', 'alerta-yellow');
    return;
  }
  try {
    const encoder = new TextEncoder();
    await serialWriter.write(encoder.encode(`${command}\n`));
    logBT(`TX ${command}`, 'console-ok');
  } catch (error) {
    logBT(`No se pudo enviar ${command}: ${error.message}`, 'console-err');
  }
}

async function maybeSaveReading(data) {
  if (!FLOWNERGY_SERIAL) return;
  const now = Date.now();
  if (lastSaveAt && now - lastSaveAt < SAVE_INTERVAL_MS) return;
  lastSaveAt = now;
  try {
    const response = await fetch('../api/guardar_medicion.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_serial: FLOWNERGY_SERIAL,
        voltaje: Number(data.voltaje || 0),
        corriente: Number(data.corriente || 0),
        potencia: Number(data.potencia || 0),
        energia: Number(data.energia || 0),
        frecuencia: Number(data.frecuencia || 0),
        fp: Number(data.fp || 0),
        estado: data.estado || 'ok'
      })
    });
    const result = await response.json();
    if (result.guardado) logBT('Lectura guardada en base de datos.', 'console-ok');
    if (Array.isArray(result.alertas) && result.alertas.length) {
      result.alertas.forEach((alerta) => logBT(alerta.mensaje || 'Alerta generada', 'console-warn'));
    }
  } catch (error) {
    logBT(`No se pudo guardar la lectura: ${error.message}`, 'console-err');
  }
}
</script>
</body>
</html>
