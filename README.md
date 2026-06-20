# Sistema de Gestión de Usuarios

## Requisitos previos

| Herramienta | Versión recomendada |
|---|---|
| PHP | ^8.2 |
| Composer | ^2.x |
| Node.js | ^18 o superior |
| npm | ^9.x |
| MySQL / MariaDB | ^8.x |
| Angular CLI | ^17 (standalone API) |

---

## Configuración y ejecución

### 1. Base de datos

1. Crea una base de datos vacía (por ejemplo `usuarios_db`):
   ```sql
   CREATE DATABASE usuarios_db;
   ```
2. Configura las credenciales en el `.env` del backend (ver paso siguiente).

### 2. Backend (Laravel)

```bash
# 1. Clona el repositorio y entra a la carpeta del backend
cd backend

# 2. Instala dependencias
composer install

# 3. Copia el archivo de entorno y genera la key de la app
cp .env.example .env
php artisan key:generate

# 4. Configura la conexión a la base de datos en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=usuarios_db
DB_USERNAME=root
DB_PASSWORD=

# 5. Instala Sanctum para autenticación por token
php artisan install:api

# 6. Corre las migraciones (crea tabla users, personal_access_tokens, etc.)
php artisan migrate

# 7. Levanta el servidor de desarrollo
php artisan serve
```

El backend quedará disponible en `http://127.0.0.1:8000/api`.

### 3. Frontend (Angular)

```bash
# 1. Entra a la carpeta del frontend
cd frontend

# 2. Instala dependencias
npm install

# 3. Verifica que la URL de la API en el servicio apunte a tu backend
# src/app/Servicios/uso-de-api.ts
private apiUrl = 'http://127.0.0.1:8000/api';

# 4. Levanta el servidor de desarrollo
ng serve
```

La aplicación quedará disponible en `http://localhost:4200`.

### Endpoints disponibles

| Método | Endpoint | Descripción | Auth |
|---|---|---|---|
| POST | `/api/login` | Autentica y devuelve token | No |
| POST | `/api/logout` | Revoca el token actual | Sí |
| GET | `/api/users` | Lista paginada/filtrada/buscable | Sí |
| POST | `/api/users` | Crea un usuario | Sí |
| GET | `/api/users/{id}` | Obtiene un usuario | Sí |
| PUT | `/api/users/{id}` | Actualiza un usuario | Sí |
| DELETE | `/api/users/{id}` | Elimina un usuario | Sí |
| GET | `/api/users/verify/{email}` | Verifica disponibilidad de email | No |

Parámetros de consulta soportados en `GET /api/users`: `page`, `limit`, `search`.

---

## Explicación de la implementación

### Backend

- **Laravel + Sanctum** para autenticación basada en tokens (no sesiones), ideal para un frontend SPA desacoplado.
- El modelo `Usuario` extiende `Authenticatable` e implementa `HasApiTokens`, lo que permite generar y revocar tokens por usuario sin depender de cookies.
- El password se hashea automáticamente mediante un **cast de Eloquent** (`'password' => 'hashed'`), evitando tener que llamar manualmente a `Hash::make()` en cada punto de entrada (creación y actualización).
- Todas las rutas de usuarios (excepto `login` y `verify`) están protegidas con el middleware `auth:sanctum`, de forma que solo un cliente autenticado puede leer o modificar datos.
- Cada método del controlador está envuelto en `try/catch` para devolver respuestas JSON consistentes (`error`, `detalle`) en caso de fallo, en lugar de dejar que Laravel devuelva una página de error HTML.
- La búsqueda (`search`) se implementó con `LIKE` sobre varios campos (`firstName`, `lastName`, `email`, `phoneNumber`) combinados con `orWhere` dentro de un closure, para no romper otros filtros aplicados en la misma query.
- La paginación usa el paginator nativo de Laravel (`paginate()`), limitando el `limit` a un máximo de 100 registros por página como medida de protección.

### Frontend

- **Angular standalone components** (sin `NgModules`), siguiendo el enfoque moderno de Angular 17+.
- Toda la comunicación con la API está centralizada en un único servicio (`UsoDeAPI`), que expone métodos tipados (`login`, `getUsuarios`, `crearUsuario`, etc.) y maneja el guardado del token en `localStorage`.
- El token se persiste en `localStorage` tras el login y se elimina en el logout; un **route guard** (`authGuard`) verifica su existencia antes de permitir el acceso a rutas protegidas como `/dashboard`, redirigiendo a `/login` si no hay sesión activa.
- El dashboard implementa **CRUD completo** sobre un modal reutilizable (mismo formulario para crear y editar, cambiando el modo), búsqueda con *debounce* de 400ms para no saturar la API con cada tecla, y paginación controlada desde el propio componente.
- Las validaciones de formulario en el frontend son mínimas (`required`, `minlength`) porque la validación de negocio fuerte vive en el backend; los errores 422 del servidor se muestran directamente en el modal.

### Decisiones de diseño clave

- **Separación de responsabilidades**: el componente no llama directamente a `HttpClient`; siempre pasa por el servicio `UsoDeAPI`, lo que facilita testear y cambiar la URL base en un solo lugar.
- **Token-based auth en vez de sesiones**: al ser un frontend SPA separado del backend (distintos orígenes), un token Bearer es más simple de manejar que cookies de sesión con CORS.
- **Edición de password opcional**: al editar un usuario, el campo password se envía vacío y solo se incluye en el payload si el usuario escribió uno nuevo, evitando sobrescribir el hash existente por accidente.

---

## Bibliotecas y frameworks utilizados

### Backend
- **Laravel** (^11.x) — framework PHP principal
- **Laravel Sanctum** — autenticación basada en tokens para SPAs/APIs
- Validación nativa de Laravel (`Illuminate\Support\Facades\Validator`)

### Frontend
- **Angular** (^17.x) — framework principal (standalone components)
- **@angular/common/http** (`HttpClient`) — consumo de la API REST
- **@angular/forms** (`FormsModule`) — formularios con `ngModel` (modal de crear/editar)
- **@angular/router** — navegación y protección de rutas (`authGuard`)
- **RxJS** — manejo de observables (`tap`, `subscribe`) en las llamadas HTTP

---

## Desafíos enfrentados y cómo fueron superados

  **Proteger rutas privadas sin un sistema de roles complejo**
  En vez de implementar lógica de roles desde el inicio, se optó por un guard simple (`authGuard`) que verifica únicamente la existencia de un token válido en `localStorage`, suficiente para el alcance actual del proyecto y fácil de extender después con verificación de roles si se requiere.
ampo `password` vacío por defecto y excluirlo del payload de actualización si el usuario no escribió uno nuevo, evitando que Laravel hasheara una cadena vacía y dejara al usuario sin poder iniciar sesión.
