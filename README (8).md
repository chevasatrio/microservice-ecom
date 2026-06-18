# 📦 Final Project — Integrasi Aplikasi Enterprise (IAE)
## Sistem Microservices E-Commerce Berbasis Laravel + Docker + GraphQL

> **Mata Kuliah:** Integrasi Aplikasi Enterprise  
> **Sifat:** Berkelompok (4 Mahasiswa)  
> **Teknologi Utama:** Laravel, Docker, GraphQL (Manual + Hasura), RabbitMQ, MySQL

---

## 📋 Daftar Isi

1. [Gambaran Sistem](#-gambaran-sistem)
2. [Pembagian Tugas (4 Orang)](#-pembagian-tugas-4-orang)
3. [Arsitektur Sistem](#️-arsitektur-sistem)
4. [Struktur Repository](#-struktur-repository)
5. [Prasyarat & Instalasi Global](#-prasyarat--instalasi-global)
6. [FASE 0 — Setup RabbitMQ (Dikerjakan Bersama)](#-fase-0--setup-rabbitmq-dikerjakan-bersama)
7. [FASE 1 — User Service (Mahasiswa 1)](#-fase-1--user-service-mahasiswa-1)
8. [FASE 2 — Product Service (Mahasiswa 2)](#-fase-2--product-service-mahasiswa-2)
9. [FASE 3 — Order Service (Mahasiswa 3)](#-fase-3--order-service-mahasiswa-3)
10. [FASE 4 — GraphQL & Hasura Gateway (Mahasiswa 4)](#-fase-4--graphql--hasura-gateway-mahasiswa-4)
11. [Database Seeder — Data Dummy E-Commerce](#-database-seeder--data-dummy-e-commerce)
12. [Menjalankan Semua Service Sekaligus](#-menjalankan-semua-service-sekaligus)
13. [Checklist Penilaian](#-checklist-penilaian)
14. [Panduan Dokumentasi & Postman](#-panduan-dokumentasi--postman)
15. [Tips Presentasi & Demo](#-tips-presentasi--demo)

---

## 🎯 Gambaran Sistem

> **Catatan: Apakah perlu Kubernetes?**
> **Tidak.** Berdasarkan spesifikasi tugas (deliverables & rubrik penilaian), teknologi yang diwajibkan adalah **Docker** (containerisasi), **RESTful API**, **GraphQL** (manual + Hasura), dan **Message Broker** (RabbitMQ) — Kubernetes **tidak disebutkan sama sekali** dan tidak masuk poin penilaian.
>
> **Docker Compose sudah cukup dan lebih sesuai** untuk skala project ini karena:
> - Rubrik "Docker Deployment" hanya menilai apakah *"semua service berjalan terpisah dalam container berbeda, setup bisa dijalankan"* — ini persis yang dicapai `docker compose up -d --build`.
> - Kubernetes menambah kompleksitas besar (Deployment, Service, ConfigMap, Secret, Ingress, cluster setup via Minikube/Kind) tanpa menambah poin di rubrik manapun.
> - RabbitMQ sebagai message broker **berjalan sebagai container biasa** (lihat Fase 0) — ini sudah memenuhi syarat *"Message broker: Bebas (Redis, RabbitMQ, Kafka, dll)"* tanpa perlu orkestrasi tambahan.
> - **Hasura** dijalankan via **Hasura Cloud (managed/online)** dengan database Neon Postgres — bukan container lokal, dan tetap memenuhi syarat *"GraphQL: dengan Hasura"* tanpa menambah kompleksitas Docker/Kubernetes sama sekali.
>
> Jika dosen/asisten secara eksplisit meminta Kubernetes sebagai nilai tambah (bukan requirement wajib), itu bisa jadi pengembangan opsional di luar scope README ini — tapi untuk memenuhi rubrik 100%, **Docker Compose sudah maksimal**.

Sistem ini adalah platform **E-Commerce Microservices** yang terdiri dari 4 layanan terpisah, masing-masing dikelola oleh satu anggota kelompok:

| Service | Tanggung Jawab | Port | Framework |
|---|---|---|---|
| **User Service** | Manajemen pengguna & autentikasi | `8000` | Laravel 10 |
| **Product Service** | Manajemen produk & stok | `8001` | Laravel 10 |
| **Order Service** | Pembuatan & manajemen pesanan | `8002` | Laravel 10 |
| **GraphQL Gateway** | GraphQL manual (Laravel) | `8003` | Laravel 10 |
| **Hasura (Cloud)** | GraphQL otomatis dari database e-commerce | online (`*.hasura.app`) | Hasura Cloud + Neon Postgres |

**Komunikasi antar service:**
- **Sinkron (HTTP):** Order Service memanggil User & Product Service via Guzzle HTTP
- **Asinkron (Queue):** Order Service dispatch job ke RabbitMQ → Product Service consume untuk update stok

---

## 👥 Pembagian Tugas (4 Orang)

| Anggota | Service | Poin Utama |
|---|---|---|
| **Mahasiswa 1** | User Service | RESTful CRUD users, Docker (PHP-FPM + Nginx + MySQL), database terpisah |
| **Mahasiswa 2** | Product Service | RESTful CRUD products, RabbitMQ consumer (update stok), Docker |
| **Mahasiswa 3** | Order Service | RESTful CRUD orders, komunikasi ke service lain, RabbitMQ producer, Docker |
| **Mahasiswa 4** | GraphQL Gateway | GraphQL schema manual (Laravel), Hasura setup, API federation, Docker |

> **Catatan:** Setiap mahasiswa wajib memiliki GitHub repo sendiri untuk service-nya, Dockerfile, dan dokumentasi Postman.

---

## 🏛️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT (Postman / curl)                  │
└────────────┬──────────────────────────────┬────────────────────┘
             │ REST                          │ GraphQL
             ▼                              ▼
┌────────────────────┐          ┌─────────────────────────┐
│   Order Service    │          │   GraphQL Gateway        │
│   (Laravel:8002)   │          │  Laravel:8003 (manual)   │
└────────┬───────────┘          └─────────┬───────────────┘
         │                                │
   HTTP  │   RabbitMQ                     │ HTTP ke semua service
         │      │                         │
    ┌────▼──────▼───────┐   ┌─────────────▼──────────────┐
    │  User Service      │   │   Product Service           │
    │  (Laravel:8000)    │   │   (Laravel:8001)            │
    │  DB: MySQL:3307    │   │   DB: MySQL:3308            │
    └────────────────────┘   │   RabbitMQ Consumer ✓       │
                             └────────────────────────────┘
    ┌────────────────────┐
    │   Order Service DB │
    │   MySQL:3309       │
    └────────────────────┘
    ┌────────────────────┐
    │   RabbitMQ         │
    │   :5672 / :15672   │
    └────────────────────┘

Docker Network: laravel-net (semua container terhubung)

┌──────────────────────────────────────────────────────────┐
│  Hasura Cloud (online, terpisah/independen)               │
│  https://<project>.hasura.app/v1/graphql                  │
│  DB: Neon Postgres — tabel users, products, orders        │
└──────────────────────────────────────────────────────────┘
```

### Stack tiap container per service

```
[service]-nginx   ← Nginx (reverse proxy, port publik)
      │
      │ fastcgi :9000
      ▼
[service]-app     ← PHP 8.2-FPM (Laravel)
      │
      │ MySQL
      ▼
[service]-db      ← MySQL 8 (database terisolasi)
```

---

## 📁 Struktur Repository

Setiap anggota memiliki repo terpisah. Struktur tiap repo:

```
[nama]-service/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── [Nama]Controller.php
│   ├── Models/
│   │   └── [Nama].php
│   └── Jobs/                    ← hanya product & order service
│       └── UpdateProductStock.php
├── database/
│   └── migrations/
│       └── xxxx_create_[nama]s_table.php
├── routes/
│   └── api.php
├── docker/
│   ├── php/
│   │   └── Dockerfile           ← PHP-FPM image
│   └── nginx/
│       └── default.conf         ← Nginx config
├── docker-compose.yml
├── .env                         ← untuk lokal (tidak di-commit)
├── .env.example                 ← template env
└── README.md
```

---

## ⚙️ Prasyarat & Instalasi Global

Pastikan semua anggota menginstal tools berikut sebelum mulai:

```bash
# Cek versi
php --version           # PHP 8.1+ (atau pakai XAMPP)
composer --version      # Composer 2+
docker --version        # Docker 24+
docker compose version  # Docker Compose v2+
```

**Tool tambahan yang direkomendasikan:**
- [Postman](https://www.postman.com/) — untuk dokumentasi & testing API
- [TablePlus](https://tableplus.com/) atau phpMyAdmin (XAMPP) — untuk inspect database
- [RabbitMQ Management UI](http://localhost:15672) — monitor message queue

> **Catatan pengerjaan:** Kerjakan Laravel dulu secara lokal (XAMPP) sampai API berjalan, baru setup Docker. Ubah `DB_HOST` dari `127.0.0.1` (lokal) ke nama container Docker saat pindah ke Docker.

---

## 🐇 FASE 0 — Setup RabbitMQ (Dikerjakan Bersama)

RabbitMQ adalah message broker yang dipakai bersama oleh Product Service dan Order Service. Jalankan ini **satu kali** sebelum semua service lain.

### Langkah 0.1 — Buat Docker Network

```bash
# Jalankan sekali saja oleh salah satu anggota (Mahasiswa 1)
docker network create laravel-net
```

### Langkah 0.2 — Buat folder rabbitmq

```
rabbitmq/
└── docker-compose.yml
```

### Langkah 0.3 — Buat docker-compose.yml RabbitMQ

Buat file `rabbitmq/docker-compose.yml`:

```yaml
version: '3.8'

services:
  rabbitmq:
    image: rabbitmq:3-management
    container_name: rabbitmq
    ports:
      - "5672:5672"     # AMQP protocol (digunakan Laravel)
      - "15672:15672"   # Management UI (buka di browser)
    environment:
      RABBITMQ_DEFAULT_USER: guest
      RABBITMQ_DEFAULT_PASS: guest
    networks:
      - laravel-net

networks:
  laravel-net:
    external: true
```

### Langkah 0.4 — Jalankan RabbitMQ

```bash
cd rabbitmq
docker compose up -d
```

### Langkah 0.5 — Verifikasi RabbitMQ

Buka browser: `http://localhost:15672`
- **Username:** `guest`
- **Password:** `guest`

Jika halaman login muncul, RabbitMQ sudah berjalan dengan benar.

---

## 🚀 FASE 1 — User Service (Mahasiswa 1)

### Langkah 1.1 — Buat Project Laravel

```bash
composer create-project laravel/laravel user-service
cd user-service
```

### Langkah 1.2 — Install Dependensi

```bash
composer require laravel/sanctum
composer require guzzlehttp/guzzle
```

### Langkah 1.3 — Setup File .env

Edit file `.env` di root project:

```env
APP_NAME=UserService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack

# Untuk development lokal (XAMPP):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=user_service_db
DB_USERNAME=root
DB_PASSWORD=

# Untuk Docker (ganti DB_HOST ke nama container):
# DB_HOST=user-service-db

QUEUE_CONNECTION=sync
```

Generate app key:

```bash
php artisan key:generate
```

### Langkah 1.4 — Buat Database (Lokal)

Buka phpMyAdmin di XAMPP (`http://localhost/phpmyadmin`) lalu jalankan:

```sql
CREATE DATABASE user_service_db;
```

### Langkah 1.5 — Buat Migration & Model

```bash
php artisan make:model User -m
```

Edit file migration yang dibuat di `database/migrations/xxxx_create_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

Edit Model `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'email', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}
```

Jalankan migrasi:

```bash
php artisan migrate
```

### Langkah 1.6 — Buat Controller

```bash
php artisan make:controller Api/UserController
```

Isi `app/Http/Controllers/Api/UserController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'status'  => 'Success',
            'message' => 'Users retrieved successfully',
            'data'    => User::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status'  => 'Success',
            'message' => 'User created successfully',
            'data'    => $user,
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'User not found',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'Success',
            'message' => 'User found',
            'data'    => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'User not found',
                'data'    => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        if ($request->has('name'))     $user->name     = $request->name;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status'  => 'Success',
            'message' => 'User updated successfully',
            'data'    => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'User not found',
                'data'    => null,
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'User deleted successfully',
            'data'    => null,
        ]);
    }
}
```

### Langkah 1.7 — Setup Routes

Edit `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class);
```

### Langkah 1.8 — Test Lokal

```bash
php artisan serve --port=8000
```

Test di Postman atau browser:
- `GET http://localhost:8000/api/users`
- `POST http://localhost:8000/api/users` dengan body JSON

### Langkah 1.9 — Buat File Docker

Buat folder dan file berikut di dalam project `user-service/`:

#### `docker/php/Dockerfile`

```dockerfile
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy .env
RUN cp .env.example .env

# Generate app key
RUN php artisan key:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

#### `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    # Handle PHP files
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # Handle all other requests
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }

    # Hide sensitive files
    location ~ /\.ht {
        deny all;
    }
}
```

#### `docker-compose.yml` (di root user-service)

```yaml
version: '3.8'

services:
  # PHP-FPM Application
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: user-service-app
    restart: unless-stopped
    volumes:
      - .:/var/www
      - /var/www/vendor
    environment:
      - DB_HOST=user-service-db
      - DB_DATABASE=user_service_db
      - DB_USERNAME=root
      - DB_PASSWORD=
    networks:
      - laravel-net
    depends_on:
      - db

  # Nginx Web Server
  nginx:
    image: nginx:stable-alpine
    container_name: user-service-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - laravel-net

  # MySQL Database
  db:
    image: mysql:8
    container_name: user-service-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: user_service_db
      MYSQL_ROOT_PASSWORD: ""
      MYSQL_ALLOW_EMPTY_PASSWORD: "yes"
    ports:
      - "3307:3306"
    volumes:
      - user_db_data:/var/lib/mysql
    networks:
      - laravel-net

networks:
  laravel-net:
    external: true

volumes:
  user_db_data:
```

#### `.env.example`

Salin isi `.env` ke `.env.example`, lalu kosongkan nilai sensitif:

```env
APP_NAME=UserService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=user-service-db
DB_PORT=3306
DB_DATABASE=user_service_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync
```

### Langkah 1.10 — Jalankan dengan Docker

```bash
# Pastikan network sudah dibuat (cukup sekali)
docker network create laravel-net

# Build image dan jalankan semua container
docker compose up -d --build

# Cek container berjalan
docker compose ps

# Jalankan migrasi di dalam container
docker exec user-service-app php artisan migrate --force

# Cek log jika ada error
docker compose logs app
docker compose logs nginx
```

**Verifikasi Docker berjalan:**
```bash
# Harus muncul: user-service-app, user-service-nginx, user-service-db
docker ps

# Test endpoint via Docker
curl http://localhost:8000/api/users
```

---

## 🚀 FASE 2 — Product Service (Mahasiswa 2)

### Langkah 2.1 — Buat Project Laravel

```bash
composer create-project laravel/laravel product-service
cd product-service
```

### Langkah 2.2 — Install Dependensi

```bash
composer require laravel/sanctum
composer require guzzlehttp/guzzle
composer require vladimir-yuldashev/laravel-queue-rabbitmq
```

### Langkah 2.3 — Setup File .env

Edit `.env`:

```env
APP_NAME=ProductService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

LOG_CHANNEL=stack

# Untuk development lokal (XAMPP):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=product_service_db
DB_USERNAME=root
DB_PASSWORD=

# Untuk Docker (ganti DB_HOST ke nama container):
# DB_HOST=product-service-db

# Queue — gunakan 'sync' saat lokal, 'rabbitmq' saat Docker
QUEUE_CONNECTION=sync
# QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=product-stock-update
```

Generate app key:

```bash
php artisan key:generate
```

### Langkah 2.4 — Setup Config Queue (RabbitMQ)

Publish config queue:

```bash
php artisan vendor:publish --tag="laravel-queue-rabbitmq"
```

Edit `config/queue.php`, pastikan ada koneksi rabbitmq:

```php
'connections' => [
    // ... koneksi lain ...

    'rabbitmq' => [
        'driver'   => 'rabbitmq',
        'queue'    => env('RABBITMQ_QUEUE', 'default'),
        'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,

        'hosts' => [
            [
                'host'     => env('RABBITMQ_HOST', '127.0.0.1'),
                'port'     => env('RABBITMQ_PORT', 5672),
                'user'     => env('RABBITMQ_USER', 'guest'),
                'password' => env('RABBITMQ_PASSWORD', 'guest'),
                'vhost'    => env('RABBITMQ_VHOST', '/'),
            ],
        ],

        'options' => [
            'ssl_options' => [
                'cafile'      => env('RABBITMQ_SSL_CAFILE', null),
                'local_cert'  => env('RABBITMQ_SSL_LOCALCERT', null),
                'local_key'   => env('RABBITMQ_SSL_LOCALKEY', null),
                'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                'passphrase'  => env('RABBITMQ_SSL_PASSPHRASE', null),
            ],
            'queue' => [
                'job' => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
            ],
        ],

        'worker' => env('RABBITMQ_WORKER', 'default'),
    ],
],
```

### Langkah 2.5 — Buat Database (Lokal)

```sql
CREATE DATABASE product_service_db;
```

### Langkah 2.6 — Buat Migration & Model

```bash
php artisan make:model Product -m
```

Edit migration `database/migrations/xxxx_create_products_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

Edit Model `app/Models/Product.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'code', 'name', 'description', 'price', 'stock',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}
```

Jalankan migrasi:

```bash
php artisan migrate
```

### Langkah 2.7 — Buat Job (RabbitMQ Consumer)

```bash
php artisan make:job UpdateProductStock
```

Edit `app/Jobs/UpdateProductStock.php`:

```php
<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateProductStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $productId;
    public int    $quantity;

    public function __construct(string $productId, int $quantity)
    {
        $this->productId = $productId;
        $this->quantity  = $quantity;
    }

    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            Log::warning("[UpdateProductStock] Product not found: {$this->productId}");
            return;
        }

        // Simulasi proses lambat untuk demo asinkron
        sleep(3);

        $product->decrement('stock', $this->quantity);

        Log::info("[UpdateProductStock] Stock updated — product: {$this->productId}, qty: -{$this->quantity}");
    }
}
```

### Langkah 2.8 — Buat Controller

```bash
php artisan make:controller Api/ProductController
```

Edit `app/Http/Controllers/Api/ProductController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json([
            'status'  => 'Success',
            'message' => 'Products retrieved successfully',
            'data'    => Product::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'        => 'required|string|unique:products,code',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        $product = Product::create($request->only([
            'code', 'name', 'description', 'price', 'stock',
        ]));

        return response()->json([
            'status'  => 'Success',
            'message' => 'Product created successfully',
            'data'    => $product,
        ], 201);
    }

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Product not found',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'Success',
            'message' => 'Product found',
            'data'    => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Product not found',
                'data'    => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code'        => 'sometimes|string|unique:products,code,' . $id,
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        $product->update($request->only([
            'code', 'name', 'description', 'price', 'stock',
        ]));

        return response()->json([
            'status'  => 'Success',
            'message' => 'Product updated successfully',
            'data'    => $product,
        ]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Product not found',
                'data'    => null,
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'Product deleted successfully',
            'data'    => null,
        ]);
    }

    // Endpoint update stok secara SINKRON (untuk demo perbandingan)
    public function updateStock(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Product not found',
                'data'    => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'product_quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        // Simulasi proses lambat — ini yang membuat sinkron terasa berat
        sleep(5);

        $product->decrement('stock', $request->product_quantity);

        return response()->json([
            'status'  => 'Success',
            'message' => 'Stock updated (sync — blocking 5s)',
            'data'    => $product,
        ]);
    }
}
```

### Langkah 2.9 — Setup Routes

Edit `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
Route::post('products/{id}/update-stock', [ProductController::class, 'updateStock']);
```

### Langkah 2.10 — Test Lokal

```bash
php artisan serve --port=8001
```

### Langkah 2.11 — Buat File Docker

#### `docker/php/Dockerfile`

```dockerfile
FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN cp .env.example .env
RUN php artisan key:generate

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

> **Catatan:** Pastikan `sockets` ikut di-install karena dibutuhkan oleh package RabbitMQ.

#### `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### `docker-compose.yml`

```yaml
version: '3.8'

services:
  # PHP-FPM Application
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: product-service-app
    restart: unless-stopped
    volumes:
      - .:/var/www
      - /var/www/vendor
    environment:
      - DB_HOST=product-service-db
      - DB_DATABASE=product_service_db
      - DB_USERNAME=root
      - DB_PASSWORD=
      - QUEUE_CONNECTION=rabbitmq
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672
      - RABBITMQ_USER=guest
      - RABBITMQ_PASSWORD=guest
      - RABBITMQ_QUEUE=product-stock-update
    networks:
      - laravel-net
    depends_on:
      - db

  # Nginx Web Server
  nginx:
    image: nginx:stable-alpine
    container_name: product-service-nginx
    restart: unless-stopped
    ports:
      - "8001:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - laravel-net

  # MySQL Database
  db:
    image: mysql:8
    container_name: product-service-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: product_service_db
      MYSQL_ROOT_PASSWORD: ""
      MYSQL_ALLOW_EMPTY_PASSWORD: "yes"
    ports:
      - "3308:3306"
    volumes:
      - product_db_data:/var/lib/mysql
    networks:
      - laravel-net

networks:
  laravel-net:
    external: true

volumes:
  product_db_data:
```

#### `.env.example`

```env
APP_NAME=ProductService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=product-service-db
DB_PORT=3306
DB_DATABASE=product_service_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=product-stock-update
```

### Langkah 2.12 — Jalankan dengan Docker

```bash
# Build dan jalankan
docker compose up -d --build

# Cek status container
docker compose ps

# Jalankan migrasi
docker exec product-service-app php artisan migrate --force

# Jalankan queue worker sebagai consumer RabbitMQ (di background)
docker exec -d product-service-app php artisan queue:work \
  --queue=product-stock-update \
  --verbose \
  --tries=3 \
  --timeout=90

# Cek log queue worker
docker logs product-service-app
```

---

## 🚀 FASE 3 — Order Service (Mahasiswa 3)

### Langkah 3.1 — Buat Project Laravel

```bash
composer create-project laravel/laravel order-service
cd order-service
```

### Langkah 3.2 — Install Dependensi

```bash
composer require laravel/sanctum
composer require guzzlehttp/guzzle
composer require vladimir-yuldashev/laravel-queue-rabbitmq
```

### Langkah 3.3 — Setup File .env

Edit `.env`:

```env
APP_NAME=OrderService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8002

LOG_CHANNEL=stack

# Untuk development lokal (XAMPP):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_service_db
DB_USERNAME=root
DB_PASSWORD=

# Untuk Docker (ganti DB_HOST ke nama container):
# DB_HOST=order-service-db

# Queue — gunakan 'sync' saat lokal, 'rabbitmq' saat Docker
QUEUE_CONNECTION=sync
# QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=product-stock-update

# URL service lain — pakai localhost saat lokal
USER_SERVICE_URL=http://localhost:8000
PRODUCT_SERVICE_URL=http://localhost:8001

# URL service lain — pakai nama container saat Docker
# USER_SERVICE_URL=http://user-service-nginx
# PRODUCT_SERVICE_URL=http://product-service-nginx
```

Generate app key:

```bash
php artisan key:generate
```

### Langkah 3.4 — Setup Config Queue (RabbitMQ)

```bash
php artisan vendor:publish --tag="laravel-queue-rabbitmq"
```

Pastikan `config/queue.php` sudah ada koneksi `rabbitmq` (sama seperti Fase 2 Langkah 2.4).

### Langkah 3.5 — Buat Database (Lokal)

```sql
CREATE DATABASE order_service_db;
```

### Langkah 3.6 — Buat Migration & Model

```bash
php artisan make:model Order -m
```

Edit migration `database/migrations/xxxx_create_orders_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('product_id');
            $table->string('user_id');
            $table->string('status')->default('pending');
            $table->decimal('total_price', 10, 2);
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

Edit Model `app/Models/Order.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'code', 'product_id', 'user_id',
        'status', 'total_price', 'quantity',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}
```

Jalankan migrasi:

```bash
php artisan migrate
```

### Langkah 3.7 — Buat Job (RabbitMQ Producer)

```bash
php artisan make:job UpdateProductStock
```

Edit `app/Jobs/UpdateProductStock.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateProductStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $productId;
    public int    $quantity;

    public function __construct(string $productId, int $quantity)
    {
        $this->productId = $productId;
        $this->quantity  = $quantity;

        // Arahkan ke antrian yang sama dengan yang didengar Product Service
        $this->onQueue('product-stock-update');
    }

    public function handle(): void
    {
        // Job ini hanya di-dispatch dari Order Service ke RabbitMQ.
        // Proses sebenarnya (decrement stock) ada di Product Service.
    }
}
```

### Langkah 3.8 — Buat Controller

```bash
php artisan make:controller Api/OrderController
```

Edit `app/Http/Controllers/Api/OrderController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateProductStock;
use App\Models\Order;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 5.0]);
    }

    public function index()
    {
        return response()->json([
            'status'  => 'Success',
            'message' => 'Orders retrieved successfully',
            'data'    => Order::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'  => 'required|string',
            'user_id'     => 'required|string',
            'quantity'    => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status'      => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        $order = Order::create([
            'id'          => Str::uuid()->toString(),
            'code'        => 'OR-' . Str::upper(Str::random(8)),
            'product_id'  => $request->product_id,
            'user_id'     => $request->user_id,
            'status'      => $request->status ?? 'pending',
            'total_price' => $request->total_price,
            'quantity'    => $request->quantity,
        ]);

        // Dispatch job ASINKRON ke RabbitMQ
        // Order Service langsung return tanpa menunggu stok diupdate
        UpdateProductStock::dispatch($request->product_id, $request->quantity);

        return response()->json([
            'status'  => 'Success',
            'message' => 'Order created successfully (stock update queued async)',
            'data'    => $order,
        ], 201);
    }

    public function show($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Order not found',
                'data'    => null,
            ], 404);
        }

        // Komunikasi SINKRON ke service lain untuk mendapatkan detail
        $productData = null;
        $userData    = null;

        try {
            $res         = $this->http->get(env('PRODUCT_SERVICE_URL') . "/api/products/{$order->product_id}");
            $productData = json_decode($res->getBody(), true)['data'] ?? null;
        } catch (RequestException $e) {
            // Service tidak tersedia, tetap lanjutkan
        }

        try {
            $res      = $this->http->get(env('USER_SERVICE_URL') . "/api/users/{$order->user_id}");
            $userData = json_decode($res->getBody(), true)['data'] ?? null;
        } catch (RequestException $e) {
            // Service tidak tersedia, tetap lanjutkan
        }

        return response()->json([
            'status'  => 'Success',
            'message' => 'Order found',
            'data'    => array_merge($order->toArray(), [
                'product' => $productData,
                'user'    => $userData,
            ]),
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Order not found',
                'data'    => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status'      => 'sometimes|string',
            'total_price' => 'sometimes|numeric|min:0',
            'quantity'    => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        $order->update($request->only(['status', 'total_price', 'quantity']));

        return response()->json([
            'status'  => 'Success',
            'message' => 'Order updated successfully',
            'data'    => $order,
        ]);
    }

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Order not found',
                'data'    => null,
            ], 404);
        }

        $order->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'Order deleted successfully',
            'data'    => null,
        ]);
    }

    public function getByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->get();

        return response()->json([
            'status'  => 'Success',
            'message' => 'Orders by user retrieved',
            'data'    => $orders,
        ]);
    }
}
```

### Langkah 3.9 — Setup Routes

Edit `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::apiResource('orders', OrderController::class);
Route::get('orders/user/{userId}', [OrderController::class, 'getByUser']);
```

### Langkah 3.10 — Test Lokal

```bash
php artisan serve --port=8002
```

### Langkah 3.11 — Buat File Docker

#### `docker/php/Dockerfile`

```dockerfile
FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# sockets wajib untuk RabbitMQ
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN cp .env.example .env
RUN php artisan key:generate

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

#### `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### `docker-compose.yml`

```yaml
version: '3.8'

services:
  # PHP-FPM Application
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: order-service-app
    restart: unless-stopped
    volumes:
      - .:/var/www
      - /var/www/vendor
    environment:
      - DB_HOST=order-service-db
      - DB_DATABASE=order_service_db
      - DB_USERNAME=root
      - DB_PASSWORD=
      - QUEUE_CONNECTION=rabbitmq
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672
      - RABBITMQ_USER=guest
      - RABBITMQ_PASSWORD=guest
      - RABBITMQ_QUEUE=product-stock-update
      - USER_SERVICE_URL=http://user-service-nginx
      - PRODUCT_SERVICE_URL=http://product-service-nginx
    networks:
      - laravel-net
    depends_on:
      - db

  # Nginx Web Server
  nginx:
    image: nginx:stable-alpine
    container_name: order-service-nginx
    restart: unless-stopped
    ports:
      - "8002:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - laravel-net

  # MySQL Database
  db:
    image: mysql:8
    container_name: order-service-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: order_service_db
      MYSQL_ROOT_PASSWORD: ""
      MYSQL_ALLOW_EMPTY_PASSWORD: "yes"
    ports:
      - "3309:3306"
    volumes:
      - order_db_data:/var/lib/mysql
    networks:
      - laravel-net

networks:
  laravel-net:
    external: true

volumes:
  order_db_data:
```

#### `.env.example`

```env
APP_NAME=OrderService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8002

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=order-service-db
DB_PORT=3306
DB_DATABASE=order_service_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=product-stock-update

USER_SERVICE_URL=http://user-service-nginx
PRODUCT_SERVICE_URL=http://product-service-nginx
```

### Langkah 3.12 — Jalankan dengan Docker

```bash
# Build dan jalankan
docker compose up -d --build

# Cek status container
docker compose ps

# Jalankan migrasi
docker exec order-service-app php artisan migrate --force

# Cek log jika ada error
docker compose logs app
docker compose logs nginx
```

---

## 🚀 FASE 4 — GraphQL & Hasura Gateway (Mahasiswa 4)

> Mahasiswa 4 bertanggung jawab atas **dua implementasi GraphQL**:
> 1. **GraphQL Manual** — Laravel + package `rebing/graphql-laravel` (jalan di Docker lokal, port 8003)
> 2. **Hasura** — GraphQL engine otomatis via **Hasura Cloud (online)**, dengan database sendiri (Neon Postgres) — independen, tanpa perlu Docker/ngrok

### Langkah 4.1 — Buat Project Laravel

```bash
composer create-project laravel/laravel graphql-service
cd graphql-service
```

### Langkah 4.2 — Install Dependensi

```bash
composer require rebing/graphql-laravel
composer require guzzlehttp/guzzle
```

### Langkah 4.3 — Publish Config GraphQL

```bash
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
```

Perintah ini akan membuat file `config/graphql.php`.

### Langkah 4.4 — Setup File .env

Edit `.env`:

```env
APP_NAME=GraphQLService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8003

LOG_CHANNEL=stack

# Tidak perlu database untuk service ini (hanya proxy ke service lain)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=graphql_service_db
DB_USERNAME=root
DB_PASSWORD=

# URL service lain — pakai localhost saat lokal
USER_SERVICE_URL=http://localhost:8000
PRODUCT_SERVICE_URL=http://localhost:8001
ORDER_SERVICE_URL=http://localhost:8002

# URL service lain — pakai nama container saat Docker
# USER_SERVICE_URL=http://user-service-nginx
# PRODUCT_SERVICE_URL=http://product-service-nginx
# ORDER_SERVICE_URL=http://order-service-nginx
```

Generate app key:

```bash
php artisan key:generate
```

### Langkah 4.5 — Buat Struktur Folder GraphQL

```bash
mkdir -p app/GraphQL/Types
mkdir -p app/GraphQL/Queries
```

### Langkah 4.6 — Buat Types

**`app/GraphQL/Types/UserType.php`**

```php
<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserType extends GraphQLType
{
    protected $attributes = [
        'name'        => 'User',
        'description' => 'Data pengguna dari User Service',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type'        => Type::string(),
                'description' => 'UUID pengguna',
            ],
            'name' => [
                'type'        => Type::string(),
                'description' => 'Nama pengguna',
            ],
            'email' => [
                'type'        => Type::string(),
                'description' => 'Email pengguna',
            ],
        ];
    }
}
```

**`app/GraphQL/Types/ProductType.php`**

```php
<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ProductType extends GraphQLType
{
    protected $attributes = [
        'name'        => 'Product',
        'description' => 'Data produk dari Product Service',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type'        => Type::string(),
                'description' => 'UUID produk',
            ],
            'code' => [
                'type'        => Type::string(),
                'description' => 'Kode produk',
            ],
            'name' => [
                'type'        => Type::string(),
                'description' => 'Nama produk',
            ],
            'description' => [
                'type'        => Type::string(),
                'description' => 'Deskripsi produk',
            ],
            'price' => [
                'type'        => Type::float(),
                'description' => 'Harga produk',
            ],
            'stock' => [
                'type'        => Type::int(),
                'description' => 'Jumlah stok',
            ],
        ];
    }
}
```

**`app/GraphQL/Types/OrderType.php`**

```php
<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class OrderType extends GraphQLType
{
    protected $attributes = [
        'name'        => 'Order',
        'description' => 'Data pesanan dari Order Service',
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type'        => Type::string(),
                'description' => 'UUID pesanan',
            ],
            'code' => [
                'type'        => Type::string(),
                'description' => 'Kode pesanan',
            ],
            'product_id' => [
                'type'        => Type::string(),
                'description' => 'UUID produk',
            ],
            'user_id' => [
                'type'        => Type::string(),
                'description' => 'UUID pengguna',
            ],
            'status' => [
                'type'        => Type::string(),
                'description' => 'Status pesanan',
            ],
            'total_price' => [
                'type'        => Type::float(),
                'description' => 'Total harga',
            ],
            'quantity' => [
                'type'        => Type::int(),
                'description' => 'Jumlah barang',
            ],
        ];
    }
}
```

### Langkah 4.7 — Buat Queries

**`app/GraphQL/Queries/UsersQuery.php`**

```php
<?php

namespace App\GraphQL\Queries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class UsersQuery extends Query
{
    protected $attributes = [
        'name'        => 'users',
        'description' => 'Ambil semua data pengguna dari User Service',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        try {
            $client   = new Client(['timeout' => 5.0]);
            $response = $client->get(env('USER_SERVICE_URL') . '/api/users');
            $body     = json_decode($response->getBody()->getContents(), true);
            return $body['data'] ?? [];
        } catch (RequestException $e) {
            return [];
        }
    }
}
```

**`app/GraphQL/Queries/ProductsQuery.php`**

```php
<?php

namespace App\GraphQL\Queries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ProductsQuery extends Query
{
    protected $attributes = [
        'name'        => 'products',
        'description' => 'Ambil semua data produk dari Product Service',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Product'));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        try {
            $client   = new Client(['timeout' => 5.0]);
            $response = $client->get(env('PRODUCT_SERVICE_URL') . '/api/products');
            $body     = json_decode($response->getBody()->getContents(), true);
            return $body['data'] ?? [];
        } catch (RequestException $e) {
            return [];
        }
    }
}
```

**`app/GraphQL/Queries/OrdersQuery.php`**

```php
<?php

namespace App\GraphQL\Queries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class OrdersQuery extends Query
{
    protected $attributes = [
        'name'        => 'orders',
        'description' => 'Ambil semua data pesanan dari Order Service',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Order'));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        try {
            $client   = new Client(['timeout' => 5.0]);
            $response = $client->get(env('ORDER_SERVICE_URL') . '/api/orders');
            $body     = json_decode($response->getBody()->getContents(), true);
            return $body['data'] ?? [];
        } catch (RequestException $e) {
            return [];
        }
    }
}
```

### Langkah 4.8 — Daftarkan di config/graphql.php

Edit `config/graphql.php`:

```php
<?php

return [
    'route' => [
        'prefix' => 'graphql',
        'middleware' => ['api'],
    ],

    'default_schema' => 'default',

    'schemas' => [
        'default' => [
            'query' => [
                'users'    => \App\GraphQL\Queries\UsersQuery::class,
                'products' => \App\GraphQL\Queries\ProductsQuery::class,
                'orders'   => \App\GraphQL\Queries\OrdersQuery::class,
            ],
            'mutation'   => [],
            'middleware' => [],
            'method'     => ['GET', 'POST'],
        ],
    ],

    'types' => [
        'User'    => \App\GraphQL\Types\UserType::class,
        'Product' => \App\GraphQL\Types\ProductType::class,
        'Order'   => \App\GraphQL\Types\OrderType::class,
    ],

    'error_formatter'     => ['\Rebing\GraphQL\GraphQL', 'formatError'],
    'errors_handler'      => ['\Rebing\GraphQL\GraphQL', 'handleErrors'],
    'security'            => [
        'query_max_complexity'  => null,
        'query_max_depth'       => null,
        'disable_introspection' => false,
    ],
    'pagination_type'     => \Rebing\GraphQL\Support\PaginationType::class,
    'simple_pagination_type' => \Rebing\GraphQL\Support\SimplePaginationType::class,
    'defaultFieldResolver' => null,
    'headers'             => [],
    'json_encoding_options' => 0,
    'apq'                 => false,
];
```

### Langkah 4.9 — Test GraphQL Lokal

```bash
php artisan serve --port=8003
```

Buka Postman, kirim `POST http://localhost:8003/graphql` dengan body:

```json
{
  "query": "{ users { id name email } products { id code name price stock } orders { id code status total_price } }"
}
```

Atau bisa juga gunakan format multi-line:

```graphql
query {
  users {
    id
    name
    email
  }
  products {
    id
    code
    name
    price
    stock
  }
  orders {
    id
    code
    status
    total_price
    quantity
  }
}
```

### Langkah 4.10 — Buat File Docker (GraphQL Service)

#### `docker/php/Dockerfile`

```dockerfile
FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN cp .env.example .env
RUN php artisan key:generate

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

#### `docker/nginx/default.conf`

```nginx
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### `docker-compose.yml`

```yaml
version: '3.8'

services:
  # PHP-FPM Application
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: graphql-service-app
    restart: unless-stopped
    volumes:
      - .:/var/www
      - /var/www/vendor
    environment:
      - USER_SERVICE_URL=http://user-service-nginx
      - PRODUCT_SERVICE_URL=http://product-service-nginx
      - ORDER_SERVICE_URL=http://order-service-nginx
    networks:
      - laravel-net

  # Nginx Web Server
  nginx:
    image: nginx:stable-alpine
    container_name: graphql-service-nginx
    restart: unless-stopped
    ports:
      - "8003:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - laravel-net

networks:
  laravel-net:
    external: true
```

#### `.env.example`

```env
APP_NAME=GraphQLService
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8003

LOG_CHANNEL=stack

# GraphQL Service tidak butuh database sendiri (hanya proxy ke service lain)
DB_CONNECTION=sqlite

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

USER_SERVICE_URL=http://user-service-nginx
PRODUCT_SERVICE_URL=http://product-service-nginx
ORDER_SERVICE_URL=http://order-service-nginx
```

> **Catatan penting:** Karena `DB_CONNECTION=sqlite`, pastikan file `database/database.sqlite` ada (kosong tidak masalah). Tambahkan baris ini di `docker/php/Dockerfile` sebelum `composer install`:
> ```dockerfile
> RUN touch database/database.sqlite
> ```
> Jika kamu sebelumnya memakai `DB_CONNECTION=mysql` dan mengalami error `Connection refused` / `getaddrinfo ... graphql-service-db failed`, ini karena GraphQL Service **memang tidak punya container database** — gunakan `sqlite` seperti di atas, lalu jalankan:
> ```bash
> docker exec graphql-service-app php artisan config:clear
> docker exec graphql-service-app php artisan cache:clear
> ```

### Langkah 4.11 — Jalankan GraphQL Service dengan Docker

```bash
# Build dan jalankan
docker compose up -d --build

# Cek status container
docker compose ps

# Cek log jika ada error
docker compose logs app
docker compose logs nginx

# Test endpoint GraphQL via Docker
curl -X POST http://localhost:8003/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ users { id name } }"}'
```

---

### Langkah 4.12 — Setup Hasura (Hasura Cloud — Tanpa Setup Lokal)

> ## ⚠️ PENTING — Perubahan Pendekatan Hasura
>
> Sebelumnya direncanakan Hasura terhubung ke **GraphQL Service Laravel via Remote Schema + ngrok**. Pendekatan ini **dibatalkan** karena terlalu rapuh (URL ngrok berubah-ubah, perlu tunnel selalu aktif, error 502 berulang).
>
> **Pendekatan baru (lebih sederhana & stabil):** Hasura Cloud akan **memiliki database sendiri** (PostgreSQL gratis via Neon), dan Hasura **otomatis membuatkan GraphQL API** dari tabel-tabel tersebut — tanpa perlu Laravel, tanpa Docker, tanpa ngrok, tanpa setup lokal sama sekali untuk bagian Hasura ini.
>
> Ini tetap memenuhi rubrik **"GraphQL: dengan Hasura"** karena Hasura sendiri menjadi backend GraphQL untuk data e-commerce (users, products, orders) — independen dari GraphQL Manual (Laravel) yang sudah dibuat di Langkah 4.1–4.11.

---

#### 🧹 Untuk yang Sudah Setup Hasura Lokal — Bersihkan Dulu

Kalau kamu/teman kamu **sudah** menjalankan container Hasura lokal (Langkah 4.12 versi lama dengan `docker-compose.yml` berisi `hasura` + `hasura-db`), bersihkan dulu agar tidak bingung dan tidak makan resource:

```bash
cd hasura
docker compose down -v
cd ..

# Hapus folder hasura/ jika sudah tidak dipakai
# (opsional — bisa juga dibiarkan, tapi jangan dijalankan lagi)
```

Hapus juga referensi `hasura` dari `start-all.sh` (lihat bagian [Menjalankan Semua Service Sekaligus](#-menjalankan-semua-service-sekaligus) yang sudah diperbarui — service `hasura` dan `hasura-db` **tidak lagi** ada di script).

> **Tidak masalah** kalau ada yang sudah terlanjur setup ngrok juga — tinggal tutup terminal ngrok-nya (`Ctrl+C`), tidak ada yang perlu dibersihkan lebih lanjut.

---

#### Langkah A — Buat Project di Hasura Cloud

1. Buka [https://cloud.hasura.io](https://cloud.hasura.io)
2. Klik **Sign Up** (bisa pakai akun GitHub/Google) — kalau sudah pernah daftar untuk versi lokal sebelumnya, **pakai akun yang sama**, tidak perlu daftar baru
3. Klik **Create Project**
4. Pilih:
   - **Plan:** Free
   - **Region:** pilih yang terdekat (misal Singapore / Asia Pacific)
   - **Project name:** `iae-ecommerce` (atau nama sesuai project kalian)
5. Klik **Create Project** — tunggu sampai status **Running**
6. Klik **Launch Console**

---

#### Langkah B — Buat Database PostgreSQL Gratis (Neon)

> Hasura Cloud paling mulus terhubung ke **PostgreSQL**. Neon menyediakan Postgres gratis, provisioning instan, tanpa setup server.

1. Buka [https://neon.tech](https://neon.tech) → **Sign Up** (GitHub/Google)
2. Klik **Create Project**
3. Beri nama project, misal `ecommerce-hasura`, pilih region (samakan dengan region Hasura Cloud jika bisa)
4. Klik **Create Project** — database langsung siap dalam beberapa detik
5. Di dashboard Neon, klik **Connect** → pilih opsi **tanpa `-pooler`** (direct connection) → copy **connection string**, formatnya:
   ```
   postgresql://<user>:<password>@<host>.neon.tech/<dbname>?sslmode=require
   ```

---

#### Langkah C — Hubungkan Neon ke Hasura Cloud

1. Di Hasura Console (yang terbuka dari Langkah A), klik tab **Data**
2. Klik **Connect Database**
3. Pilih **Postgres**
4. **Database Display Name:** `ecommerce_db`
5. **Connection String:** paste connection string dari Neon (Langkah B poin 5)
6. Klik **Connect Database**

Jika berhasil, tab **Data** akan menampilkan database `ecommerce_db` dengan daftar tabel (masih kosong).

---

#### Langkah D — Buat Tabel via Hasura Console (Tab Data)

Klik **Create Table** untuk masing-masing tabel berikut:

**Tabel `users`**

| Column | Type | Default / Extra |
|---|---|---|
| `id` | UUID | `gen_random_uuid()`, **Primary Key** |
| `name` | Text | — |
| `email` | Text | **Unique** |
| `password` | Text | — |
| `created_at` | Timestamp | `now()` |
| `updated_at` | Timestamp | `now()` |

**Tabel `products`**

| Column | Type | Default / Extra |
|---|---|---|
| `id` | UUID | `gen_random_uuid()`, **Primary Key** |
| `code` | Text | **Unique** |
| `name` | Text | — |
| `description` | Text | nullable |
| `price` | Numeric | — |
| `stock` | Integer | default `0` |
| `created_at` | Timestamp | `now()` |
| `updated_at` | Timestamp | `now()` |

**Tabel `orders`**

| Column | Type | Default / Extra |
|---|---|---|
| `id` | UUID | `gen_random_uuid()`, **Primary Key** |
| `code` | Text | **Unique** |
| `product_id` | UUID | **Foreign Key** → `products.id` |
| `user_id` | UUID | **Foreign Key** → `users.id` |
| `status` | Text | default `'pending'` |
| `total_price` | Numeric | — |
| `quantity` | Integer | — |
| `created_at` | Timestamp | `now()` |
| `updated_at` | Timestamp | `now()` |

> **Setelah tabel `orders` dibuat:** masuk ke tab **Relationships** pada tabel `orders` → Hasura otomatis mendeteksi foreign key dan menyarankan relationship (`order.product`, `order.user`, `product.orders`, `user.orders`). Klik **Add** untuk semuanya — penting agar query nested (misal `orders { product { name } }`) berfungsi.

---

#### Langkah E — Jalankan Seeder

Cara tercepat: gunakan tab **SQL** di Hasura Console (tab **Data** → **SQL**).

1. Buka file **`seeder.sql`** (sudah disediakan terpisah — lihat lampiran)
2. Copy seluruh isi file
3. Paste ke editor SQL di Hasura Console
4. **Centang "Track this"** (penting — agar tabel & data otomatis terdaftar ke GraphQL API)
5. Klik **Run!**

Seeder ini akan:
- Membuat tabel `users`, `products`, `orders` (jika belum ada dari Langkah D)
- Mengisi 3 data user, 5 data produk, 5 data order — saling terhubung via UUID statis (tidak perlu copy-paste ID manual)

---

#### Langkah F — Ambil Admin Secret & Endpoint

1. Di Hasura Console, klik **Project Settings** (ikon gear di pojok)
2. Tab **Env Vars** → cari `HASURA_GRAPHQL_ADMIN_SECRET`
   - Jika sudah ada → klik **ikon mata** untuk melihat nilainya, copy
   - Jika belum ada → klik **New Env Var** → pilih `HASURA_GRAPHQL_ADMIN_SECRET` → isi value sendiri (misal `myadminsecret123`) → **Save** (tunggu ~30 detik untuk redeploy)
3. Di dashboard utama project, catat **GraphQL Endpoint**:
   ```
   https://<nama-project>.hasura.app/v1/graphql
   ```

---

#### Langkah G — Test Query di Hasura Console

1. Klik tab **API**
2. Di **Explorer**, field `users`, `products`, `orders` harus sudah muncul
3. Jalankan query:

```graphql
query FederatedQuery {
  users {
    id
    name
    email
  }
  products {
    id
    code
    name
    price
    stock
  }
  orders {
    id
    code
    status
    total_price
    quantity
    product {
      name
      price
    }
    user {
      name
      email
    }
  }
}
```

4. Klik **▶ Run** — hasil muncul di panel kanan, termasuk data nested `product` dan `user` dari relationship

---

#### Langkah H — Test di Postman

**Endpoint:**
```
POST https://<nama-project>.hasura.app/v1/graphql
```

**Headers:**

| Key | Value |
|---|---|
| `Content-Type` | `application/json` |
| `x-hasura-admin-secret` | `<admin secret dari Langkah F>` |

**Body (raw JSON) — Query:**

```json
{
  "query": "query { users { id name email } products { id code name price stock } orders { id code status total_price quantity } }"
}
```

**Body — Mutation Tambah Produk:**

```json
{
  "query": "mutation { insert_products_one(object: {code: \"PRD-006\", name: \"Webcam Logitech C920\", description: \"Webcam Full HD 1080p\", price: 950000, stock: 10}) { id code name price stock } }"
}
```

**Body — Mutation Update Stok:**

```json
{
  "query": "mutation { update_products_by_pk(pk_columns: {id: \"<ID_PRODUK>\"}, _set: {stock: 14}) { id name stock } }"
}
```

> **Untuk dokumentasi Postman:** endpoint ini bisa diakses **dari mana saja** (online, 24/7) — cocok untuk di-share ke dosen/asisten tanpa mereka perlu menjalankan Docker atau ngrok.

---

### Ringkasan Lokasi Admin Secret (Hasura Cloud)

| Tempat | Fungsi |
|---|---|
| Hasura Console → **Project Settings → Env Vars** | **Mendefinisikan/melihat** nilai admin secret |
| Header HTTP `x-hasura-admin-secret` (Postman/curl) | **Otentikasi** setiap request ke `/v1/graphql` dari luar console |
| Hasura Console online (sudah login) | Otomatis terautentikasi — tidak perlu masukkan admin secret lagi di dalam console |

---

### Troubleshooting Hasura Cloud + Neon

| Masalah | Penyebab | Solusi |
|---|---|---|
| `Connect Database` gagal / timeout | Connection string salah atau pakai `-pooler` | Pastikan pakai direct connection string (tanpa `-pooler`) dari Neon, dan `?sslmode=require` ikut disertakan |
| Tabel tidak muncul di GraphQL API setelah run SQL | Lupa centang **"Track this"** saat run SQL, atau tabel belum di-track manual | Buka tab **Data** → klik tabel → klik **Track** |
| `curl .../v1/graphql` return 401 | Header `x-hasura-admin-secret` tidak disertakan/salah | Copy ulang dari **Project Settings → Env Vars** |
| Field `product`/`user` tidak muncul saat query nested di `orders` | Relationship belum ditambahkan | Tab **Data** → tabel `orders` → **Relationships** → **Add** untuk foreign key yang tersedia |
| Insert/update gagal "duplicate key" | Data seeder sudah pernah di-run sebelumnya | Wajar — seeder pakai `ON CONFLICT DO NOTHING`, data lama tetap ada, tidak perlu diulang |

---## 🌱 Database Seeder — Data Dummy E-Commerce

Untuk demo & testing, setiap service sebaiknya punya data dummy yang **saling terhubung** (UUID produk di Order Service harus benar-benar ada di Product Service, dst). Berikut seeder untuk ketiga service yang punya database.

### Seeder — User Service (Mahasiswa 1)

Buat file `database/seeders/UserSeeder.php`:

```bash
php artisan make:seeder UserSeeder
```

Isi `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id'       => 'a1111111-1111-1111-1111-111111111111',
                'name'     => 'Budi Santoso',
                'email'    => 'budi@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a2222222-2222-2222-2222-222222222222',
                'name'     => 'Siti Aminah',
                'email'    => 'siti@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a3333333-3333-3333-3333-333333333333',
                'name'     => 'Andi Wijaya',
                'email'    => 'andi@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a4444444-4444-4444-4444-444444444444',
                'name'     => 'Dewi Lestari',
                'email'    => 'dewi@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a5555555-5555-5555-5555-555555555555',
                'name'     => 'Rian Pratama',
                'email'    => 'rian@example.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['id' => $user['id']], $user);
        }
    }
}
```

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
```

Jalankan:

```bash
# Lokal
php artisan db:seed

# Docker
docker exec user-service-app php artisan db:seed --force
```

> **Catatan ID:** UUID di atas (`a1111111-...` dst) **sengaja dibuat fixed/hardcoded**, bukan random, agar bisa dirujuk secara konsisten oleh seeder Product Service dan Order Service. Catat ID-ID ini untuk dipakai di seeder Order Service.

---

### Seeder — Product Service (Mahasiswa 2)

```bash
php artisan make:seeder ProductSeeder
```

Isi `database/seeders/ProductSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id'          => 'b1111111-1111-1111-1111-111111111111',
                'code'        => 'PRD-001',
                'name'        => 'Kaos Polos Premium',
                'description' => 'Kaos polos bahan cotton combed 30s, nyaman dipakai harian.',
                'price'       => 75000,
                'stock'       => 100,
            ],
            [
                'id'          => 'b2222222-2222-2222-2222-222222222222',
                'code'        => 'PRD-002',
                'name'        => 'Sepatu Sneakers Casual',
                'description' => 'Sepatu sneakers ringan untuk aktivitas sehari-hari.',
                'price'       => 250000,
                'stock'       => 50,
            ],
            [
                'id'          => 'b3333333-3333-3333-3333-333333333333',
                'code'        => 'PRD-003',
                'name'        => 'Tas Selempang Kanvas',
                'description' => 'Tas selempang bahan kanvas tebal, cocok untuk kuliah.',
                'price'       => 120000,
                'stock'       => 75,
            ],
            [
                'id'          => 'b4444444-4444-4444-4444-444444444444',
                'code'        => 'PRD-004',
                'name'        => 'Topi Baseball',
                'description' => 'Topi baseball adjustable, bahan twill tebal.',
                'price'       => 45000,
                'stock'       => 150,
            ],
            [
                'id'          => 'b5555555-5555-5555-5555-555555555555',
                'code'        => 'PRD-005',
                'name'        => 'Jaket Hoodie Oversize',
                'description' => 'Hoodie oversize bahan fleece tebal, hangat dipakai.',
                'price'       => 180000,
                'stock'       => 60,
            ],
            [
                'id'          => 'b6666666-6666-6666-6666-666666666666',
                'code'        => 'PRD-006',
                'name'        => 'Celana Chino Slimfit',
                'description' => 'Celana chino bahan stretch, nyaman dan fleksibel.',
                'price'       => 165000,
                'stock'       => 40,
            ],
            [
                'id'          => 'b7777777-7777-7777-7777-777777777777',
                'code'        => 'PRD-007',
                'name'        => 'Dompet Kulit Pria',
                'description' => 'Dompet kulit asli dengan banyak slot kartu.',
                'price'       => 95000,
                'stock'       => 80,
            ],
            [
                'id'          => 'b8888888-8888-8888-8888-888888888888',
                'code'        => 'PRD-008',
                'name'        => 'Jam Tangan Digital',
                'description' => 'Jam tangan digital tahan air, cocok untuk olahraga.',
                'price'       => 220000,
                'stock'       => 35,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['id' => $product['id']], $product);
        }
    }
}
```

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
```

Jalankan:

```bash
# Lokal
php artisan db:seed

# Docker
docker exec product-service-app php artisan db:seed --force
```

---

### Seeder — Order Service (Mahasiswa 3)

> Seeder ini **mereferensikan UUID dari User Service dan Product Service** di atas — pastikan kedua seeder tersebut sudah dijalankan dulu agar data konsisten saat demo (terutama untuk fitur `GET /api/orders/:id` yang memanggil User & Product Service via Guzzle).

```bash
php artisan make:seeder OrderSeeder
```

Isi `database/seeders/OrderSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'id'          => 'c1111111-1111-1111-1111-111111111111',
                'code'        => 'OR-AAAAAAAA',
                'product_id'  => 'b1111111-1111-1111-1111-111111111111', // Kaos Polos Premium
                'user_id'     => 'a1111111-1111-1111-1111-111111111111', // Budi Santoso
                'status'      => 'completed',
                'total_price' => 150000, // 2 x 75000
                'quantity'    => 2,
            ],
            [
                'id'          => 'c2222222-2222-2222-2222-222222222222',
                'code'        => 'OR-BBBBBBBB',
                'product_id'  => 'b2222222-2222-2222-2222-222222222222', // Sepatu Sneakers Casual
                'user_id'     => 'a2222222-2222-2222-2222-222222222222', // Siti Aminah
                'status'      => 'completed',
                'total_price' => 250000, // 1 x 250000
                'quantity'    => 1,
            ],
            [
                'id'          => 'c3333333-3333-3333-3333-333333333333',
                'code'        => 'OR-CCCCCCCC',
                'product_id'  => 'b5555555-5555-5555-5555-555555555555', // Jaket Hoodie Oversize
                'user_id'     => 'a3333333-3333-3333-3333-333333333333', // Andi Wijaya
                'status'      => 'pending',
                'total_price' => 360000, // 2 x 180000
                'quantity'    => 2,
            ],
            [
                'id'          => 'c4444444-4444-4444-4444-444444444444',
                'code'        => 'OR-DDDDDDDD',
                'product_id'  => 'b3333333-3333-3333-3333-333333333333', // Tas Selempang Kanvas
                'user_id'     => 'a4444444-4444-4444-4444-444444444444', // Dewi Lestari
                'status'      => 'pending',
                'total_price' => 120000, // 1 x 120000
                'quantity'    => 1,
            ],
            [
                'id'          => 'c5555555-5555-5555-5555-555555555555',
                'code'        => 'OR-EEEEEEEE',
                'product_id'  => 'b7777777-7777-7777-7777-777777777777', // Dompet Kulit Pria
                'user_id'     => 'a5555555-5555-5555-5555-555555555555', // Rian Pratama
                'status'      => 'completed',
                'total_price' => 190000, // 2 x 95000
                'quantity'    => 2,
            ],
        ];

        foreach ($orders as $order) {
            Order::updateOrCreate(['id' => $order['id']], $order);
        }
    }
}
```

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrderSeeder::class,
        ]);
    }
}
```

Jalankan:

```bash
# Lokal
php artisan db:seed

# Docker
docker exec order-service-app php artisan db:seed --force
```

---

### Urutan Menjalankan Seeder (Penting!)

Karena Order Service mereferensikan UUID dari User & Product Service, jalankan seeder dengan urutan ini:

```bash
# 1. Seed User Service dulu
docker exec user-service-app php artisan db:seed --force

# 2. Seed Product Service
docker exec product-service-app php artisan db:seed --force

# 3. Terakhir, seed Order Service (referensi ke #1 dan #2)
docker exec order-service-app php artisan db:seed --force
```

Atau jika ingin migrate + seed sekaligus dari awal (database fresh):

```bash
docker exec user-service-app    php artisan migrate:fresh --seed --force
docker exec product-service-app php artisan migrate:fresh --seed --force
docker exec order-service-app   php artisan migrate:fresh --seed --force
```

### Verifikasi Data Seeder

```bash
# Cek user
curl http://localhost:8000/api/users

# Cek produk
curl http://localhost:8001/api/products

# Cek order — sekaligus menguji komunikasi sinkron ke User & Product Service
curl http://localhost:8002/api/orders/c1111111-1111-1111-1111-111111111111
```

Response terakhir harus menampilkan data `order` yang sudah digabung dengan detail `product` dan `user` (hasil panggilan Guzzle ke service lain) — ini bagus untuk demo fitur federasi data.

---



### ⚠️ Kenapa "start-all.sh" tidak muncul sebagai sesuatu di Docker Desktop?

`start-all.sh` **bukan komponen Docker** — ini hanyalah **script bash biasa** yang isinya kumpulan perintah `cd` dan `docker compose up -d --build` yang dijalankan berurutan. Docker Desktop **tidak menampilkan script shell**, Docker Desktop hanya menampilkan:

- **Containers** — container yang sedang berjalan (tab "Containers")
- **Images** — image yang sudah di-build
- **Volumes** — volume database

Jadi setelah `./start-all.sh` selesai dijalankan, **yang harus muncul di Docker Desktop adalah container-container-nya**, bukan script-nya. Cara verifikasi yang benar:

1. Buka Docker Desktop → tab **Containers**
2. Harus muncul **grup-grup container** (biasanya dikelompokkan per docker-compose project), masing-masing berisi:
   - `user-service` → `user-service-app`, `user-service-nginx`, `user-service-db`
   - `product-service` → `product-service-app`, `product-service-nginx`, `product-service-db`
   - `order-service` → `order-service-app`, `order-service-nginx`, `order-service-db`
   - `graphql-service` → `graphql-service-app`, `graphql-service-nginx`
   - `rabbitmq` → `rabbitmq`

> **Catatan:** Hasura **tidak ada di daftar ini** karena Hasura kita gunakan via **Hasura Cloud (online) dengan database Neon Postgres sendiri** — lihat Langkah 4.12. Tidak perlu container Hasura, tidak perlu ngrok, dan tidak ada ketergantungan ke GraphQL Service Laravel untuk bagian Hasura.

**Jika container tidak muncul sama sekali setelah menjalankan script, cek:**

```bash
# Cek apakah script benar-benar dijalankan dari folder yang benar
# (folder parent yang berisi user-service/, product-service/, dll sebagai subfolder)
ls -la

# Jalankan manual per baris untuk debug, contoh:
cd user-service && docker compose up -d --build
docker compose ps

# Cek semua container yang berjalan
docker ps -a
```

**Penyebab umum script "tidak terlihat hasilnya":**

| Penyebab | Solusi |
|---|---|
| Script dijalankan dari folder yang salah (folder `user-service/` itu sendiri, bukan parent) | Pindah ke folder parent yang berisi semua subfolder service, lalu jalankan `./start-all.sh` |
| Line ending file `.sh` menggunakan CRLF (dibuat di Windows Notepad) | Jalankan `dos2unix start-all.sh` atau buat file lewat editor yang set LF (VS Code) |
| Permission belum `chmod +x` | Jalankan `chmod +x start-all.sh` lalu `./start-all.sh` |
| Salah satu `cd` di tengah script gagal (folder tidak ada) karena typo nama folder | Pastikan nama folder sama persis dengan di script (`user-service`, `product-service`, dst) |
| Docker Desktop tab Containers belum di-refresh | Klik refresh / buka ulang Docker Desktop |

Buat file `start-all.sh` di folder parent (satu level di atas semua service):

```bash
#!/bin/bash

echo "============================================"
echo "  IAE Final Project — Start All Services"
echo "============================================"

echo ""
echo "📡 [1/6] Membuat Docker Network..."
docker network create laravel-net 2>/dev/null || echo "  Network sudah ada, skip."

echo ""
echo "🐇 [2/6] Menjalankan RabbitMQ..."
cd rabbitmq && docker compose up -d && cd ..
sleep 5

echo ""
echo "👤 [3/6] Menjalankan User Service..."
cd user-service && docker compose up -d --build && cd ..

echo ""
echo "📦 [4/6] Menjalankan Product Service..."
cd product-service && docker compose up -d --build && cd ..

echo ""
echo "🛒 [5/6] Menjalankan Order Service..."
cd order-service && docker compose up -d --build && cd ..

echo ""
echo "🔷 [6/6] Menjalankan GraphQL Service..."
cd graphql-service && docker compose up -d --build && cd ..

echo ""
echo "⏳ Menunggu semua container siap (15 detik)..."
sleep 15

echo ""
echo "🗃️  Menjalankan Migrasi Database..."
docker exec user-service-app    php artisan migrate --force
docker exec product-service-app php artisan migrate --force
docker exec order-service-app   php artisan migrate --force

echo ""
echo "🔄 Menjalankan Queue Worker (Product Service — RabbitMQ Consumer)..."
docker exec -d product-service-app php artisan queue:work \
  --queue=product-stock-update \
  --verbose \
  --tries=3 \
  --timeout=90

echo ""
echo "✅ Selesai! Buka Docker Desktop → tab Containers untuk verifikasi."
echo "   Harus terlihat 5 grup: rabbitmq, user-service, product-service,"
echo "   order-service, graphql-service — masing-masing dengan"
echo "   beberapa container di dalamnya."
echo ""
echo "============================================"
echo "  ✅ Semua service berjalan!"
echo "============================================"
echo ""
echo "  User Service    → http://localhost:8000/api/users"
echo "  Product Service → http://localhost:8001/api/products"
echo "  Order Service   → http://localhost:8002/api/orders"
echo "  GraphQL Manual  → http://localhost:8003/graphql"
echo "  RabbitMQ UI     → http://localhost:15672 (guest/guest)"
echo ""
echo "  ⚡ Hasura Cloud (online, terpisah dari Docker):"
echo "      https://<nama-project>.hasura.app/v1/graphql"
echo "      (database: Neon Postgres — lihat Langkah 4.12 di README)"
echo ""
```

```bash
chmod +x start-all.sh
./start-all.sh
```

**Stop semua service:**

```bash
#!/bin/bash
cd rabbitmq      && docker compose down && cd ..
cd user-service  && docker compose down && cd ..
cd product-service && docker compose down && cd ..
cd order-service && docker compose down && cd ..
cd graphql-service && docker compose down && cd ..
```

---

## ✅ Checklist Penilaian

### 1. GraphQL Implementation (20 poin)

- [ ] Schema GraphQL terdefinisi (Types: User, Product, Order)
- [ ] Query berjalan dengan benar (users, products, orders)
- [ ] **GraphQL Manual** (Laravel + `rebing/graphql-laravel`) berjalan
- [ ] **Hasura Cloud** project bisa dibuka, database Neon terhubung, tabel ter-track, query/mutation berjalan
- [ ] Dokumentasi GraphQL tersedia (screenshot query + response)

### 2. Docker Deployment (20 poin)

- [ ] Setiap service punya **3 container terpisah** (app/nginx/db)
- [ ] Menggunakan **Docker Network** (`laravel-net`)
- [ ] Database setiap service terpisah (port berbeda)
- [ ] `docker compose up -d --build` berjalan tanpa error
- [ ] Container berkomunikasi via nama service (internal DNS Docker)

### 3. RESTful & Message Broker (25 poin)

- [ ] RESTful CRUD lengkap di semua service dengan format response standar
- [ ] RabbitMQ berjalan sebagai message broker
- [ ] Order Service **dispatch job** ke RabbitMQ (producer)
- [ ] Product Service **consume job** dari RabbitMQ (consumer)
- [ ] Demo perbedaan sinkron (delay 5s) vs asinkron (langsung return)

### 4. Dokumentasi & Arsitektur (15 poin)

- [ ] Diagram arsitektur sistem (draw.io / Miro / Lucidchart)
- [ ] Deskripsi lengkap setiap service
- [ ] Link Postman Collection setiap service (public)
- [ ] Link GitHub repo setiap service
- [ ] Penjelasan flow fitur end-to-end

### 5. Presentasi & Demo (20 poin)

- [ ] Demo semua endpoint berjalan live
- [ ] Demo komunikasi asinkron (RabbitMQ)
- [ ] Demo GraphQL query (manual + Hasura)
- [ ] Setiap anggota menjelaskan service miliknya
- [ ] Alur fitur dijelaskan dengan jelas

---

## 📬 Panduan Dokumentasi & Postman

### Struktur Postman Collection

```
📁 User Service API
  📁 Users
    GET    /api/users          — Get All Users
    POST   /api/users          — Create User
    GET    /api/users/:id      — Get User by ID
    PUT    /api/users/:id      — Update User
    DELETE /api/users/:id      — Delete User

📁 Product Service API
  📁 Products
    GET    /api/products               — Get All Products
    POST   /api/products               — Create Product
    GET    /api/products/:id           — Get Product by ID
    PUT    /api/products/:id           — Update Product
    DELETE /api/products/:id           — Delete Product
    POST   /api/products/:id/update-stock — Update Stock (sync)

📁 Order Service API
  📁 Orders
    GET    /api/orders               — Get All Orders
    POST   /api/orders               — Create Order (async stock update)
    GET    /api/orders/:id           — Get Order + Detail
    PUT    /api/orders/:id           — Update Order
    DELETE /api/orders/:id           — Delete Order
    GET    /api/orders/user/:userId  — Orders by User

📁 GraphQL Service API
  POST   /graphql  — Query: users
  POST   /graphql  — Query: products
  POST   /graphql  — Query: orders
  POST   /graphql  — Query: federated (semua sekaligus)
```

---

## 🎤 Tips Presentasi & Demo

### Urutan Demo

1. `docker ps` — tunjukkan semua container berjalan
2. **User Service** — POST user, GET all users
3. **Product Service** — POST produk (stok = 10), GET product
4. **Order Service** — POST order → stok belum berkurang → buka RabbitMQ UI → tunggu → GET product lagi → stok berkurang
5. **GraphQL Manual** — POST ke `/graphql` dengan query federation
6. **Hasura Cloud** — buka `https://cloud.hasura.io` → Launch Console → tab API → jalankan query/mutation ke tabel users/products/orders

### Poin Penjelasan Tiap Anggota

| Anggota | Harus Dijelaskan |
|---|---|
| M1 | Kenapa UUID? Kenapa database terpisah? Bagaimana Nginx forward ke PHP-FPM? |
| M2 | Apa itu Queue Worker? Bagaimana RabbitMQ menerima job? Kenapa `sockets` extension dibutuhkan? |
| M3 | Perbedaan sync vs async? Bagaimana Guzzle HTTP memanggil service lain via nama container? |
| M4 | Apa bedanya GraphQL manual (Laravel) vs Hasura? Bagaimana Hasura otomatis generate API dari tabel Postgres? |

---

## 📞 Referensi Port Cepat

| Service | Container | Port Host | URL |
|---|---|---|---|
| User Service | user-service-nginx | 8000 | `http://localhost:8000/api/users` |
| Product Service | product-service-nginx | 8001 | `http://localhost:8001/api/products` |
| Order Service | order-service-nginx | 8002 | `http://localhost:8002/api/orders` |
| GraphQL Manual | graphql-service-nginx | 8003 | `http://localhost:8003/graphql` |
| Hasura Cloud Console | — | — | `https://cloud.hasura.io` |
| Hasura Cloud Endpoint | — | — | `https://<nama-project>.hasura.app/v1/graphql` |
| Neon Postgres (database Hasura) | — | — | dikelola via `https://neon.tech` dashboard |
| RabbitMQ AMQP | rabbitmq | 5672 | — |
| RabbitMQ UI | rabbitmq | 15672 | `http://localhost:15672` (guest/guest) |
| MySQL User | user-service-db | 3307 | — |
| MySQL Product | product-service-db | 3308 | — |
| MySQL Order | order-service-db | 3309 | — |

---

> **Referensi:** [github.com/purnamaanaking/iae](https://github.com/purnamaanaking/iae)
