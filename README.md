# Libro de visitas (PHP + MariaDB)

Un clásico de la web de los 2000: un **libro de visitas** donde cualquiera puede dejar su nombre y un mensaje. Hecho en **PHP 8** puro (sin frameworks), con **PDO** para hablar con una base de datos **MariaDB** (compatible con MySQL).

> Proyecto con fines exclusivamente educativos. No tiene moderación, ni protección anti-spam, ni autenticación.

---

## Nivel y objetivos de aprendizaje

**Nivel:** inicial–intermedio.

Al dockerizar esta aplicación vas a practicar:

- Usar una imagen que **ya incluye un servidor web** (Apache + PHP) y colocar el código donde ese servidor lo espera.
- **Instalar extensiones de PHP** con los *scripts auxiliares* que trae la imagen oficial.
- Configurar una base de datos relacional mediante sus **variables de entorno de inicialización**.
- Separar la configuración sensible en un archivo **`.env`** que no se sube a git.
- Esperar a que la base de datos esté **lista** (no solo "arrancada").
- Agregar una **herramienta de administración** (phpMyAdmin o Adminer) como servicio extra.

---

## Stack tecnológico

| Componente | Tecnología |
|---|---|
| Lenguaje | PHP 8.2 o superior |
| Servidor web | Apache HTTP Server con `mod_php` |
| Acceso a datos | PDO con el driver `pdo_mysql` |
| Base de datos | MariaDB 11 (también funciona con MySQL 8) |
| Interfaz | HTML generado por PHP, sin JavaScript |

---

## Arquitectura

```
 Navegador ──HTTP──▶  web (Apache + PHP, puerto 80)
                         │   index.php  → formulario y lista de mensajes
                         │   health.php → verificación de salud
                         │
                         │  TCP 3306 (protocolo MySQL)
                         ▼
                       db (MariaDB)
                         └─ base "libro_visitas", tabla "mensajes"
```

Tabla `mensajes` (se crea automáticamente la primera vez que la app se conecta):

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `INT AUTO_INCREMENT` | Clave primaria |
| `autor` | `VARCHAR(60)` | Obligatorio |
| `texto` | `VARCHAR(500)` | Obligatorio |
| `creado_en` | `TIMESTAMP` | Por defecto, la fecha actual |

---

## Estructura del proyecto

```
04-libro-visitas-php-mariadb/
├── src/                  # Raíz pública del sitio (lo que Apache debe servir)
│   ├── index.php         # Página principal: muestra y guarda mensajes
│   ├── health.php        # Devuelve JSON con el estado de la base de datos
│   └── db.php            # Conexión PDO + creación de la tabla
├── .env.example          # Plantilla de variables de entorno (sin valores reales)
├── README.md
└── CHANGELOG.md
```

Detalles importantes:

- **`src/db.php`**
  - Lee la configuración con `getenv()`. Las variables deben llegar **al proceso de PHP** (el servidor web), no solo a tu terminal.
  - Usa `charset=utf8mb4` para soportar acentos y emojis.
  - Ejecuta `CREATE TABLE IF NOT EXISTS`, así que **no necesitas** un script SQL de inicialización (aunque podrías usar uno como reto extra).
- **`src/index.php`**
  - Usa **sentencias preparadas** para evitar inyección SQL y `htmlspecialchars` para evitar XSS.
  - Tras guardar, redirige con `303` (patrón *Post/Redirect/Get*) para que recargar la página no duplique mensajes.
  - El pie de página muestra la versión de PHP y el nombre del contenedor.
- **`.env.example`**: copia este archivo como `.env` y rellena los valores. El `.env` está en `.gitignore`.

---

## Variables de entorno

Las lee la aplicación PHP:

| Variable | Valor por defecto | Descripción |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | Host de MariaDB. |
| `DB_PORT` | `3306` | Puerto de MariaDB. |
| `DB_NAME` | `libro_visitas` | Nombre de la base de datos. |
| `DB_USER` | `libro` | Usuario de la base de datos. |
| `DB_PASSWORD` | (vacío) | Contraseña del usuario. |

Además, la imagen oficial de MariaDB necesita sus **propias** variables de inicialización (contraseña de *root*, base de datos, usuario y contraseña a crear). Búscalas en la documentación de la imagen: empiezan con el prefijo `MARIADB_`. Deben coincidir con los valores que le pasas a la app.

---

## Rutas

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/` | Muestra el formulario y los últimos 50 mensajes. |
| `POST` | `/` | Guarda un mensaje (`autor`, `texto`) y redirige a `/`. |
| `GET` | `/health.php` | `200` si la base responde; `503` si no. |

---

## Ejecución local sin Docker (referencia)

1. Requiere PHP con la extensión `pdo_mysql` y un servidor MariaDB/MySQL.
2. Crea la base de datos y un usuario con permisos sobre ella.
3. Define las variables de entorno y arranca el servidor web integrado de PHP apuntando a la carpeta `src/`.

---

## Reto: dockerizar la aplicación

> Pistas, no soluciones.

### Parte 1: el `Dockerfile`

1. **Imagen base:** la imagen oficial de PHP tiene variantes; busca la que incluye **Apache**. Usa una versión explícita.
2. **¿Dónde va el código?** Averigua cuál es el `DocumentRoot` por defecto de Apache en esa imagen y copia **el contenido** de `src/` allí.
3. **Extensión de base de datos:** la imagen **no** incluye el driver de MySQL para PDO. Lee la sección *"How to install more PHP extensions"* de la documentación de la imagen: trae scripts auxiliares para compilar e instalar extensiones.
4. **Configuración de PHP:** la imagen trae dos archivos `php.ini` de ejemplo (desarrollo y producción). Investiga cómo activar el de **producción**.
5. **Permisos:** Apache corre con el usuario `www-data`. Los archivos solo necesitan permiso de lectura para él.
6. **Puerto:** documenta el puerto en el que escucha Apache.
7. **Comando de arranque:** la imagen ya define uno adecuado. ¿Necesitas cambiarlo?

### Parte 2: el `docker-compose.yml`

1. Dos servicios: **web** (tu imagen) y **db** (MariaDB oficial, versión explícita).
2. Crea tu archivo `.env` a partir de `.env.example`. Investiga la diferencia entre:
   - el archivo `.env` que Compose usa para **interpolar** `${VARIABLES}` dentro del `docker-compose.yml`, y
   - la opción `env_file`, que **inyecta** variables dentro del contenedor.
3. Pasa a MariaDB sus variables `MARIADB_*` y a la web sus variables `DB_*`, reutilizando los mismos valores del `.env`.
4. **Volumen con nombre** para los datos de MariaDB (busca la ruta en la documentación).
5. **Healthcheck de la base de datos:** la imagen oficial de MariaDB incluye un script de healthcheck pensado justo para esto; búscalo en la documentación.
6. La web debe esperar a que la base esté **sana**.
7. Publica solo el puerto de la web.
8. **Importante:** las variables `MARIADB_*` **solo** se aplican la primera vez, cuando el volumen está vacío. Si cambias la contraseña después, ¿qué tienes que hacer?

### Criterios de aceptación

- [ ] Puedes firmar el libro y ver los mensajes.
- [ ] Los mensajes sobreviven a recrear los contenedores.
- [ ] Ninguna contraseña aparece escrita directamente en el `docker-compose.yml`.
- [ ] El archivo `.env` **no** está en git (comprueba el estado del repositorio).
- [ ] `/health.php` devuelve `200`.
- [ ] La web no muestra errores de conexión al arrancar todo desde cero.

### Retos extra

- Agrega **phpMyAdmin** o **Adminer** como tercer servicio y entra a ver la tabla `mensajes`.
- Reemplaza la creación automática de la tabla por un **script SQL** montado en la carpeta de inicialización de la imagen de MariaDB, e incluye un par de mensajes de ejemplo.
- Usa **Docker secrets** de Compose para la contraseña (investiga las variables `*_FILE` de la imagen de MariaDB). Ojo: la app PHP lee `DB_PASSWORD` directamente, así que tendrías que adaptar `db.php`.
- Cambia la variante de PHP a **FPM** y agrega un servicio **Nginx** que le pase las peticiones PHP.

---

## Errores comunes

| Síntoma | Causa probable |
|---|---|
| `could not find driver` | No instalaste la extensión `pdo_mysql`. |
| `Connection refused` o `No such file or directory` | `DB_HOST` vacío, `localhost` o la base aún no estaba lista. |
| `Access denied for user` | Las credenciales de la app no coinciden con las `MARIADB_*`, o cambiaste credenciales con un volumen ya inicializado. |
| Apache muestra el listado de archivos o un 404 | Copiaste la carpeta `src` en lugar de su contenido, o en la ruta equivocada. |
| `Forbidden` | Permisos de los archivos incorrectos. |

---

## Referencias

- Imagen oficial de PHP: <https://hub.docker.com/_/php>
- Imagen oficial de MariaDB: <https://hub.docker.com/_/mariadb>
- Variables en Compose (`.env` e interpolación): <https://docs.docker.com/compose/how-tos/environment-variables/>
- Secrets en Compose: <https://docs.docker.com/compose/how-tos/use-secrets/>
- PDO en el manual de PHP: <https://www.php.net/manual/es/book.pdo.php>
- Imagen oficial de phpMyAdmin: <https://hub.docker.com/_/phpmyadmin>
