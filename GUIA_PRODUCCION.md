# Guía de Conexión en Producción — FuelOps

## Requisitos Previos

- Firebird 5.x instalado y corriendo en el servidor de BD
- PHP 7.4+ con extensión `pdo_firebird` habilitada
- Servidor web (IIS o Apache) con acceso a la red interna

## 1. Habilitar extensión PHP

En `php.ini`:
```ini
extension=pdo_firebird
```

Verificar:
```bash
php -m | findstr firebird
```

## 2. Crear archivo .env en producción

Copiar `.env.example` (o crear desde cero) en la raíz del proyecto:

```ini
# Entorno
APP_ENV=production
APP_DEBUG=false

# Firebird Producción
DB_DRIVER=firebird
DB_SERVER=192.168.4.101
DB_PATH=C:\ALVIC5\BO5\FDB\BO5.FDB
DB_PORT=3050
DB_USER=SYSDBA
DB_PASSWORD=TU_PASSWORD_AQUI
DB_CHARSET=UTF8

# App
APP_NAME=FuelOps
APP_VERSION=3.0.0
APP_BASE_URL=/proyectos/intermedio/admin/
APP_ITEMS_PER_PAGE=25
APP_MAX_EXPORT=10000

# CORS (restringir en producción)
CORS_ORIGIN=https://tudominio.com
```

## 3. Rutas de Firebird en Windows

El archivo `.FDB` debe ser accesible desde el servidor PHP. Opciones:

### Opción A: Ruta local (misma máquina)
```ini
DB_SERVER=localhost
DB_PATH=C:\ALVIC5\BO5\FDB\BO5.FDB
```

### Opción B: Ruta de red (servidor remoto)
```ini
DB_SERVER=192.168.4.101
DB_PATH=\\192.168.4.101\ALVIC5\BO5\FDB\BO5.FDB
```

### Opción C: Alias de Firebird
Crear en `firebird.conf` o `aliases.conf`:
```
BO5 = C:\ALVIC5\BO5\FDB\BO5.FDB
```
Luego en `.env`:
```ini
DB_SERVER=192.168.4.101
DB_PATH=BO5
```

## 4. Permisos de Archivo

El servicio de Firebird necesita acceso al `.FDB`:

```powershell
# En el servidor de Firebird, dar permisos al servicio
icacls "C:\ALVIC5\BO5\FDB\BO5.FDB" /grant "Firebird Server:(OI)(CI)F"
```

## 5. Firewall — Puerto 3050

Abrir puerto TCP 3050 en el servidor de BD:

```powershell
netsh advfirewall firewall add rule name="Firebird" dir=in action=allow protocol=TCP localport=3050
```

## 6. Verificar Conexión

```bash
# Test rápido desde PHP
php -r "
try {
    \$dsn = 'firebird:host=192.168.4.101;service=3050;dbname=C:\\ALVIC5\\BO5\\FDB\\BO5.FDB;charset=UTF8';
    \$pdo = new PDO(\$dsn, 'SYSDBA', 'tu_password');
    echo 'Conexion OK';
} catch (PDOException \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"
```

## 7. Estructura de Carpetas en Producción

```
C:\inetpub\wwwroot\proyectos\intermedio\
├── .env                          # ← Configuración (no web-accessible)
├── admin/
│   ├── config.php
│   ├── core/
│   ├── modules/
│   └── ...
└── ...
```

Asegurar que `.env` NO sea accesible vía web. En IIS:
```xml
<!-- web.config en la raíz -->
<configuration>
    <system.webServer>
        <security>
            <requestFiltering>
                <denyUrlSequences>
                    <add sequence=".env" />
                </denyUrlSequences>
            </requestFiltering>
        </security>
    </system.webServer>
</configuration>
```

## 8. Variables de Entorno por IP

El sistema detecta automáticamente el entorno. En producción, configurar las IPs en `.env`:

```ini
ENV_IP_81_42_223_28=fueltruck_oficina
ENV_IP_37_143_127_188=igara_oficina
ENV_IP_37_143_127_164=ruta
```

Esto permite mostrar/ocultar funcionalidades según la ubicación del usuario.

## 9. Troubleshooting

| Problema | Solución |
|----------|----------|
| `could not find driver` | Verificar `extension=pdo_firebird` en php.ini |
| `connection refused` | Verificar firewall puerto 3050 y servicio Firebird corriendo |
| `permission denied` | Verificar permisos del servicio Firebird sobre el .FDB |
| `database disk image is malformed` | Usar `gfix` para reparar o restaurar backup |
| `file in use` | Verificar que no haya otro proceso usando el .FDB |

## 10. Backup de Firebird

```bash
# Backup completo
gbak -backup -user SYSDBA -password tu_password "192.168.4.101:C:\ALVIC5\BO5\FDB\BO5.FDB" C:\backups\bo5_20260907.fbk

# Restore
gbak -replace -user SYSDBA -password tu_password C:\backups\bo5_20260907.fbk "192.168.4.101:C:\ALVIC5\BO5\FDB\BO5.FDB"
```
