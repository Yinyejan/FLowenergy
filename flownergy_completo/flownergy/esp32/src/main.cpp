/**
 * Flownergy - Monitor de Energía Industrial
 * Hardware: ESP32 WROOM-32 + PZEM-004T v3.0 + LCD I2C 16x2
 * 
 * Envía JSON por Bluetooth cada segundo para display en tiempo real.
 * El dashboard web guarda en BD cada 5 minutos y detecta anomalías.
 */

#include <Arduino.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <PZEM004Tv30.h>
#include <BluetoothSerial.h>

// ── Objetos principales ───────────────────────────────────────────────
LiquidCrystal_I2C lcd(0x27, 16, 2);
PZEM004Tv30       pzem(Serial2, 16, 17); // RX=16, TX=17
BluetoothSerial   SerialBT;

// ── Constantes de configuración ───────────────────────────────────────
const int   PIN_RELE         = 4;
const float LIMITE_AMPERES   = 10.0;   // Sobrecarga máxima
const float LIMITE_VOLTAJE_MIN = 102.0;  // 120V - 15%
const float LIMITE_VOLTAJE_MAX = 138.0;  // 120V + 15% // Voltaje máximo aceptable (V)
const float LIMITE_PF_MIN    = 0.75;   // Factor de potencia mínimo

// ── Icono de rayo para LCD ────────────────────────────────────────────
byte Rayo[8] = {
  B00010, B00100, B01000, B11111, B00010, B00100, B01000, B00000
};

// ── Variables de estado ───────────────────────────────────────────────
bool rele_activo   = false;
bool medir_activo  = false; // Controlado por comando BT del dashboard
int  ciclo_lcd     = 0;     // Alterna pantalla cada 3 seg

// ── Prototipos ────────────────────────────────────────────────────────
void leerComandoBluetooth();
String construirJSON(const char* estado, float v, float c, float p, float e, float f, float pf);
void mostrarLCD(float v, float c, float p, float e, float f, float pf);

// ─────────────────────────────────────────────────────────────────────
void setup() {
    Serial.begin(115200);
    SerialBT.begin("ESP32_Flownergy"); // Nombre BT visible al parear
    Wire.begin(21, 22);                // I2C: SDA=21, SCL=22

    pinMode(PIN_RELE, OUTPUT);
    digitalWrite(PIN_RELE, LOW);

    lcd.init();
    lcd.backlight();
    lcd.createChar(0, Rayo);

    // Pantalla de bienvenida
    lcd.setCursor(0, 0); lcd.print("  FLOWNERGY IoT ");
    lcd.setCursor(0, 1); lcd.print("  Iniciando...  ");
    delay(2000);

    lcd.setCursor(0, 0); lcd.print("BT: ESP32_Flow  ");
    lcd.setCursor(0, 1); lcd.print("Esperando red...");
    delay(1500);
    lcd.clear();

    Serial.println("[Flownergy] ESP32 listo. Esperando conexion BT...");
}

// ─────────────────────────────────────────────────────────────────────
void loop() {
    // Escuchar comandos desde el dashboard web
    leerComandoBluetooth();

    // Leer todos los parámetros del PZEM-004T
    float voltage = pzem.voltage();
    float current = pzem.current();
    float power   = pzem.power();
    float energy  = pzem.energy();
    float freq    = pzem.frequency();
    float pf      = pzem.pf();

    // Reemplazar NaN por 0 para serialización segura
    if (isnan(voltage)) voltage = 0;
    if (isnan(current)) current = 0;
    if (isnan(power))   power   = 0;
    if (isnan(energy))  energy  = 0;
    if (isnan(freq))    freq    = 0;
    if (isnan(pf))      pf      = 0;

    // ── Determinar estado del sistema ─────────────────────────────────
    String estado = "ok";

    if (voltage == 0) {
        estado = "sin_red";
        digitalWrite(PIN_RELE, LOW);
        rele_activo = false;

    } else if (current > LIMITE_AMPERES) {
        estado = "sobrecarga";
        digitalWrite(PIN_RELE, LOW);
        rele_activo = false;
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("!!! SOBRECARGA !!");
        lcd.setCursor(0, 1); lcd.print("RELE DESACTIVADO");
        // Enviar alerta y bloquear
        SerialBT.println(construirJSON("sobrecarga", voltage, current, power, energy, freq, pf));
        Serial.println("[ALERTA] SOBRECARGA - Sistema bloqueado por seguridad.");
        while (true) { delay(1000); } // Bloqueo de seguridad

    } else if (voltage < LIMITE_VOLTAJE_MIN || voltage > LIMITE_VOLTAJE_MAX) {
        estado = "voltaje_anormal";
    } else if (pf > 0 && pf < LIMITE_PF_MIN) {
        estado = "pf_bajo"; // Señal de mantenimiento eléctrico necesario
    }

    // ── Enviar JSON por Bluetooth (cada ciclo = 1 seg) ─────────────────
    String json = construirJSON(estado.c_str(), voltage, current, power, energy, freq, pf);
    SerialBT.println(json);
    Serial.println(json); // Debug por USB

    // ── Actualizar LCD ────────────────────────────────────────────────
    mostrarLCD(voltage, current, power, energy, freq, pf);

    delay(1000); // Ciclo cada 1 segundo
}

// ─────────────────────────────────────────────────────────────────────
/**
 * Escucha comandos del dashboard:
 *   "START" → activa medición y relé
 *   "STOP"  → desactiva medición y relé
 *   "RESET" → reinicia contador de energía PZEM
 */
void leerComandoBluetooth() {
    if (SerialBT.available()) {
        String cmd = SerialBT.readStringUntil('\n');
        cmd.trim();
        Serial.print("[CMD] Recibido: "); Serial.println(cmd);

        if (cmd == "START") {
            medir_activo = true;
            digitalWrite(PIN_RELE, HIGH);
            rele_activo = true;
            SerialBT.println("{\"cmd_ack\":\"START_OK\"}");
            lcd.clear();
            lcd.setCursor(0, 0); lcd.print("MIDIENDO...     ");

        } else if (cmd == "STOP") {
            medir_activo = false;
            digitalWrite(PIN_RELE, LOW);
            rele_activo = false;
            SerialBT.println("{\"cmd_ack\":\"STOP_OK\"}");
            lcd.clear();
            lcd.setCursor(0, 0); lcd.print("EN ESPERA       ");

        } else if (cmd == "RESET_ENERGY") {
            pzem.resetEnergy();
            SerialBT.println("{\"cmd_ack\":\"ENERGY_RESET_OK\"}");
        }
    }
}

// ─────────────────────────────────────────────────────────────────────
/**
 * Construye el JSON con todos los parámetros del PZEM-004T.
 * El campo "rele" indica si el relé está activo.
 */
String construirJSON(const char* estado, float v, float c, float p, float e, float f, float pf) {
    String json = "{";
    json += "\"estado\":\"";   json += estado;         json += "\",";
    json += "\"voltaje\":";    json += String(v, 2);   json += ",";
    json += "\"corriente\":";  json += String(c, 3);   json += ",";
    json += "\"potencia\":";   json += String(p, 2);   json += ",";
    json += "\"energia\":";    json += String(e, 4);   json += ",";
    json += "\"frecuencia\":"; json += String(f, 1);   json += ",";
    json += "\"fp\":";         json += String(pf, 3);  json += ",";
    json += "\"rele\":";       json += rele_activo ? "true" : "false";
    json += "}";
    return json;
}

// ─────────────────────────────────────────────────────────────────────
/**
 * Alterna entre dos pantallas en el LCD cada 3 ciclos:
 *   Pantalla A: Voltaje + Corriente
 *   Pantalla B: Potencia + Energía
 */
void mostrarLCD(float v, float c, float p, float e, float f, float pf) {
    ciclo_lcd++;
    if (ciclo_lcd > 6) ciclo_lcd = 0;

    if (ciclo_lcd <= 3) {
        // Pantalla A: V y A
        lcd.setCursor(0, 0);
        lcd.write(byte(0)); // Icono rayo
        lcd.print(" ");
        lcd.print(v, 1); lcd.print("V  ");
        lcd.print(c, 2); lcd.print("A  ");

        lcd.setCursor(0, 1);
        lcd.print("FP:");  lcd.print(pf, 2);
        lcd.print(" Hz:"); lcd.print(f, 0);
        lcd.print("     ");
    } else {
        // Pantalla B: Potencia y Energía
        lcd.setCursor(0, 0);
        lcd.print("P: "); lcd.print(p, 1); lcd.print(" W      ");

        lcd.setCursor(0, 1);
        lcd.print("E: "); lcd.print(e, 3); lcd.print(" kWh    ");
    }
}
