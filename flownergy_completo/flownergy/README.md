# ⚡ FLOWNERGY — Monitor de Energía Industrial IoT

Sistema de monitoreo eléctrico con **ESP32 + PZEM-004T** para micro y medianas empresas.
Detecta picos, bajos de consumo y genera alertas de mantenimiento preventivo.

---

## 📁 Estructura completa del proyecto

```
flownergy/                          ← Carpeta raíz (va en htdocs/)
│
├── config/
│   └── Db.php                      ← Conexión PDO a MySQL (Singleton)
│
├── models/
│   ├── UsuarioModel.php            ← Autenticación de usuarios
│   ├── MaquinaModel.php            ← Registro y consulta de máquinas
│   └── MedicionModel.php           ← Lecturas, anomalías, estadísticas
│
├── api/
│   ├── validar.php                 ← POST: Login de usuario
│   ├── cerrar.php                  ← GET:  Logout
│   ├── registrar_maquina.php       ← POST: Registrar nueva máquina
│   ├── guardar_medicion.php        ← POST: Guardar lectura PZEM (AJAX)
│   └── exportar_csv.php            ← GET:  Exportar datos a CSV
│
├── views/
│   └── index.php                   ← Router principal + todas las vistas
│
├── esp32/
│   ├── platformio.ini              ← Configuración PlatformIO
│   └── src/
│       └── main.cpp                ← Firmware del ESP32
│
├── sql/
│   └── flownergyy_updated.sql      ← Esquema completo de la BD
│
└── recibir_datos.php               ← Textos editables del proyecto
```

---

## 🚀 Instalación paso a paso

### 1. Base de datos
1. Abre XAMPP → inicia **Apache** y **MySQL**
2. Ve a http://localhost/phpmyadmin
3. Importa el archivo `sql/flownergyy_updated.sql`
4. Verifica que se crearon las tablas: `inicio_sesion`, `registro`, `sensores_energia`, `alertas`

### 2. Proyecto PHP
1. Copia toda la carpeta `flownergy/` a `C:/xampp/htdocs/`
2. Abre http://localhost/flownergy/views/index.php en Chrome

### 3. Firmware ESP32
1. Abre la carpeta `esp32/` en VS Code con PlatformIO
2. Conecta el ESP32 al PC por **USB (COM3)**
3. Haz clic en **Upload** (→) y espera `SUCCESS`

### 4. Emparejar Bluetooth
```
Windows → Configuración → Bluetooth → Agregar dispositivo → Bluetooth
→ Buscar: ESP32_Flownergy
→ Emparejar (PIN: 1234 si lo pide)
→ Verificar en Administrador de dispositivos → Puertos (COM y LPT)
→ Anotar el COM asignado (ej: COM5)
```

### 5. Usar el sistema
```
1. Abre Chrome → http://localhost/flownergy/views/index.php?ver=acceder
2. Login: ID = 110 / Contraseña = 123  (datos de prueba)
3. Registra tu máquina (solo la primera vez)
4. Ve a Medición → Conectar Bluetooth → Selecciona el COM5
5. Presiona ▶ Iniciar → ¡El relé se activa y empieza la medición!
6. Los datos se guardan en BD cada 5 minutos automáticamente
```

---

## 🔌 Conexiones físicas del ESP32

| Componente   | Pin ESP32 | Pin Componente |
|---|---|---|
| PZEM-004T    | GPIO16 (RX2) | TX del PZEM |
| PZEM-004T    | GPIO17 (TX2) | RX del PZEM |
| LCD I2C      | GPIO21 (SDA) | SDA |
| LCD I2C      | GPIO22 (SCL) | SCL |
| Módulo Relé  | GPIO4        | IN  |
| LCD + Relé   | 5V / GND    | VCC / GND |

---

## 📊 Datos del PZEM-004T utilizados

| Campo | Unidad | Uso en Flownergy |
|---|---|---|
| Voltaje | V | Detección de voltaje fuera de rango |
| Corriente | A | Detección de sobrecarga (>10A = corte) |
| Potencia activa | W | Detección de picos y bajos |
| Energía | kWh | Historial de consumo acumulado |
| Frecuencia | Hz | Monitoreo de calidad de red |
| Factor de Potencia | cosφ | Predicción de mantenimiento |

---

## 🔔 Lógica de alertas

| Alerta | Condición |
|---|---|
| Pico de potencia | Potencia > promedio 30 días × 1.30 |
| Consumo bajo | Potencia < promedio 30 días × 0.50 |
| Voltaje alto | Voltaje > Nominal × 1.15 |
| Voltaje bajo | Voltaje < Nominal × 0.85 |
| Factor P. bajo | cosφ < 0.75 |
| Mantenimiento | 3+ alertas de FP bajo en 3 horas |
| Sobrecarga | Corriente > 10 A → corte inmediato |

---

## 👥 Contacto

- 📱 WhatsApp: **+57 322 520 0707**
- ✉️ Email: **alejandrodiazramirez2020@gmail.com**
- 📍 Yumbo, Valle del Cauca, Colombia

---

## ⚠️ Notas importantes

- La **Web Serial API** (Bluetooth desde el navegador) solo funciona en **Chrome y Edge**
- Para flashear el ESP32 usa el puerto **USB (COM3)**, no el Bluetooth
- El Bluetooth (COM5) es solo para recibir datos en el dashboard
- El sistema guarda una lectura en BD cada **5 minutos** mientras hay conexión activa
- Para exportar CSV, usa el panel del Dashboard con filtro de fechas
