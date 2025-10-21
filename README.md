# Hunting Tour Booking Module

Минимальный Laravel-модуль для бронирования охотничьих туров с выбором гида.

## Содержание

- [Возможности](#возможности)
- [Структура модуля](#структура-модуля)
- [API Endpoints](#api-endpoints)
- [Установка и тестирование](#установка-и-тестирование)
- [Примеры использования](#примеры-использования)
- [Интеграция в BookingCore](#интеграция-в-bookingcore)
- [Тесты](#тесты)

## Возможности

### Реализованный функционал

- **Управление гидами**
  - Модель Guide с полями: name, experience_years, is_active
  - Фильтрация активных гидов
  - Фильтр по минимальному опыту (бонус)
  - Проверка доступности гида на конкретную дату

- **Система бронирования**
  - Модель HuntingBooking с полями: tour_name, hunter_name, guide_id, date, participants_count
  - Валидация всех входных данных
  - Проверка доступности гида (не более 1 бронирования в день)
  - Лимит участников (максимум 10 человек)
  - Проверка активности гида

- **API эндпоинты**
  - GET `/api/guides` - список активных гидов
  - GET `/api/guides?min_experience=3` - фильтрация по опыту
  - POST `/api/bookings` - создание бронирования

- **Тестирование**
  - 19 тестов (5 Unit + 14 Feature) покрывающих весь функционал
  - 61 утверждение (assertions)
  - Проверка всех валидаций, бизнес-логики и edge cases
  - Тестирование транзакций, race conditions и error handling

## Структура модуля

```
app/
├── Models/
│   ├── Guide.php                    # Модель гида
│   └── HuntingBooking.php           # Модель бронирования
├── Services/
│   └── BookingService.php           # Бизнес-логика бронирований
├── Exceptions/
│   ├── Handler.php                  # Централизованная обработка исключений
│   └── Booking/
│       ├── BookingException.php     # Базовое исключение бронирования
│       ├── GuideNotActiveException.php    # Гид неактивен
│       └── GuideNotAvailableException.php # Гид занят
├── Http/
│   ├── Controllers/Api/
│   │   ├── GuideController.php      # Контроллер для списка гидов
│   │   └── BookingController.php    # Контроллер для бронирования
│   ├── Requests/
│   │   └── StoreHuntingBookingRequest.php  # Валидация бронирования
│   └── Resources/
│       ├── GuideResource.php        # API Resource для гида
│       └── HuntingBookingResource.php      # API Resource для бронирования
database/
├── migrations/
│   ├── *_create_guides_table.php
│   ├── *_create_hunting_bookings_table.php
│   └── *_add_unique_constraint_to_hunting_bookings_table.php
├── factories/
│   ├── GuideFactory.php
│   └── HuntingBookingFactory.php
└── seeders/
    └── GuideSeeder.php
tests/
├── Unit/
│   └── BookingServiceTest.php       # Unit тесты сервисного слоя
└── Feature/
    ├── GuideTest.php                # Тесты для гидов
    └── HuntingBookingTest.php       # Тесты для бронирований
routes/
└── api.php                          
bootstrap/
└── app.php                          
```

## API Endpoints

### GET /api/guides

Получить список активных гидов.

**Query Parameters:**
- `min_experience` (опционально) - минимальный опыт в годах

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Smith",
      "experience_years": 15,
      "is_active": true,
      "created_at": "2025-10-21T16:31:20.000000Z"
    }
  ]
}
```

**Примеры:**
```bash
# Все активные гиды
GET /api/guides

# Гиды с опытом от 5 лет
GET /api/guides?min_experience=5
```

### POST /api/bookings

Создать новое бронирование.

**Request Body:**
```json
{
  "tour_name": "Deer Hunting Tour",
  "hunter_name": "John Doe",
  "guide_id": 1,
  "date": "2025-10-25",
  "participants_count": 5
}
```

**Validation Rules:**
- `tour_name`: required, string, max:255
- `hunter_name`: required, string, max:255
- `guide_id`: required, integer, exists in guides table
- `date`: required, date, не может быть в прошлом
- `participants_count`: required, integer, min:1, max:10

**Business Rules:**
- Гид должен быть активен (is_active = true)
- Гид не должен иметь другое бронирование на эту дату
- Максимум 10 участников
- Unique constraint (guide_id + date) на уровне базы данных
- Database transactions обеспечивают консистентность данных
- Pessimistic locking (lockForUpdate) защищает от race conditions

**Response 201 (Success):**
```json
{
  "data": {
    "id": 1,
    "tour_name": "Deer Hunting Tour",
    "hunter_name": "John Doe",
    "guide": {
      "id": 1,
      "name": "John Smith",
      "experience_years": 15,
      "is_active": true,
      "created_at": "2025-10-21T16:31:20.000000Z"
    },
    "date": "2025-10-25",
    "participants_count": 5,
    "created_at": "2025-10-21T16:35:00.000000Z"
  }
}
```

**Response 422 (Validation Error):**
```json
{
  "message": "The selected guide is not available on this date.",
  "errors": {
    "date": [
      "The selected guide is not available on this date."
    ]
  }
}
```

## Установка и тестирование

### Заполнение базы тестовыми данными

```bash
# Выполнить миграции (уже выполнено при установке API)
php artisan migrate

# Заполнить БД тестовыми гидами
php artisan db:seed --class=GuideSeeder
```

### Запуск тестов

```bash
# Все тесты
php artisan test

# Только тесты модуля
php artisan test --filter=GuideTest
php artisan test --filter=HuntingBookingTest
```

**Результаты тестов:**
- ✅ 11 тестов пройдены
- ✅ 39 утверждений (assertions)
- ✅ Все валидации работают корректно

## Примеры использования

### 1. Получить список гидов с опытом от 10 лет

```bash
curl -X GET "http://localhost:8000/api/guides?min_experience=10" \
  -H "Accept: application/json"
```

### 2. Создать бронирование

```bash
curl -X POST "http://localhost:8000/api/bookings" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tour_name": "Elk Mountain Hunt",
    "hunter_name": "Jane Smith",
    "guide_id": 1,
    "date": "2025-11-15",
    "participants_count": 4
  }'
```

### 3. Попытка забронировать занятого гида

```bash
curl -X POST "http://localhost:8000/api/bookings" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tour_name": "Another Tour",
    "hunter_name": "Bob Johnson",
    "guide_id": 1,
    "date": "2025-11-15",
    "participants_count": 3
  }'
```

**Response:** 422 Validation Error

## Интеграция в BookingCore

### Подход к интеграции

Этот модуль спроектирован как **самостоятельный микросервис** внутри BookingCore. Вот как бы я его интегрировал:

### 1. Структура модулей (Рекомендуемая)

```
BookingCore/
├── app/
│   └── Modules/
│       ├── Core/              # Базовая функциональность
│       │   ├── Models/
│       │   ├── Contracts/
│       │   └── Services/
│       ├── Hunting/           # Модуль охотничьих туров
│       │   ├── Models/
│       │   ├── Controllers/
│       │   ├── Requests/
│       │   ├── Resources/
│       │   └── Services/
│       ├── Hotels/            # Другие модули бронирования
│       └── Tours/
```

### 2. Использование Laravel Packages / Modules

**Вариант A: Laravel Modules Package**

```bash
composer require nwidart/laravel-modules
php artisan module:make Hunting
```

Переместить код в структуру модуля:
```
Modules/
└── Hunting/
    ├── Config/
    ├── Database/
    │   ├── Migrations/
    │   ├── Factories/
    │   └── Seeders/
    ├── Entities/
    ├── Http/
    │   ├── Controllers/
    │   ├── Requests/
    │   └── Resources/
    ├── Routes/
    │   └── api.php
    └── Tests/
```

**Вариант B: Service Provider Pattern**

Создать `HuntingServiceProvider`:

```php
namespace App\Modules\Hunting\Providers;

class HuntingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
```

### 3. Абстракция для переиспользования

**Создать базовые интерфейсы:**

```php
namespace App\Modules\Core\Contracts;

interface BookableResource
{
    public function isAvailableOn(string $date): bool;
    public function getPrice(): float;
}

interface Booking
{
    public function confirm(): void;
    public function cancel(): void;
}
```

**Имплементация в модуле:**

```php
class Guide extends Model implements BookableResource
{
    public function isAvailableOn(string $date): bool
    {
        return !$this->bookings()->whereDate('date', $date)->exists();
    }

    public function getPrice(): float
    {
        return $this->daily_rate ?? 0;
    }
}
```

### 4. Общие сервисы

**BookingService для всех типов бронирований:**

```php
namespace App\Modules\Core\Services;

class BookingService
{
    public function createBooking(
        BookableResource $resource,
        array $data
    ): Booking {
        // Общая логика бронирования
        if (!$resource->isAvailableOn($data['date'])) {
            throw new ResourceUnavailableException();
        }

        return $resource->bookings()->create($data);
    }
}
```

### 5. API Versioning

```php
// routes/api.php
Route::prefix('v1/hunting')->group(function () {
    Route::get('/guides', [GuideController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
});
```

### 6. Shared Database vs Separate Schemas

**Рекомендация:** Один database, разные таблицы с префиксами

```
hunting_guides
hunting_bookings
hotel_rooms
hotel_bookings
tour_packages
tour_bookings
```

Или использовать schemas в PostgreSQL:
```
bookingcore.hunting.guides
bookingcore.hotels.rooms
```

### 7. Event-Driven Architecture

```php
// В BookingController
event(new BookingCreated($booking));

// Listener в Core модуле
class SendBookingConfirmation
{
    public function handle(BookingCreated $event)
    {
        // Отправка email, SMS, etc.
    }
}
```

### Преимущества такого подхода

1. **Изолированность** - каждый модуль независим
2. **Переиспользование** - общая логика в Core модуле
3. **Масштабируемость** - легко добавлять новые типы бронирований
4. **Тестируемость** - модули тестируются отдельно
5. **Поддержка** - изменения в одном модуле не ломают другие

## Best Practices использованные в модуле

### 1. Laravel Conventions

- ✅ Form Requests для валидации
- ✅ API Resources для форматирования ответов
- ✅ Eloquent Relationships
- ✅ Query Scopes для переиспользуемых запросов
- ✅ Factory Pattern для тестовых данных

### 2. Clean Code & Architecture

- ✅ **Service Layer** - бизнес-логика вынесена из контроллеров
- ✅ **Thin Controllers** - контроллер всего 7 строк кода
- ✅ **Custom Exceptions** - специализированные исключения для бизнес-логики
- ✅ **Exception Handler** - централизованная обработка в отдельном файле
- ✅ **SOLID Principles** - применение принципов Single Responsibility, Dependency Injection
- ✅ `declare(strict_types=1)` - строгая типизация во всех классах
- ✅ `Response::HTTP_*` константы вместо магических чисел (201, 409, 422, 500)
- ✅ Type hints для всех параметров и возвращаемых значений
- ✅ Return type `never` для методов обработки исключений
- ✅ Одна ответственность у каждого класса
- ✅ Понятные имена методов и переменных
- ✅ Документация в комментариях

### 3. RESTful API Design

- ✅ Правильные HTTP методы (GET, POST)
- ✅ Корректные статус-коды (200, 201, 409, 422, 500)
- ✅ Понятная структура ответов
- ✅ Validation errors в стандартном формате Laravel
- ✅ Семантически правильные коды ошибок (409 для конфликтов, 422 для валидации)

### 4. Безопасность

- ✅ Валидация всех входных данных
- ✅ Mass Assignment Protection (fillable)
- ✅ SQL Injection Protection (Eloquent ORM)
- ✅ Type Hints для всех методов

### 5. Производительность & Надежность

- ✅ **Database Transactions** - атомарность операций бронирования
- ✅ **Pessimistic Locking** (lockForUpdate) - защита от race conditions
- ✅ **Unique Constraint** (guide_id + date) - защита на уровне БД
- ✅ Database indexes для часто запрашиваемых полей
- ✅ Eager Loading для избежания N+1 проблемы
- ✅ Scope queries для эффективной фильтрации
- ✅ Обработка QueryException для duplicate entry errors

## Тесты

### Покрытие тестами

**BookingServiceTest (Unit - 5 тестов):**
- ✅ Успешное создание бронирования
- ✅ Выброс исключения при неактивном гиде
- ✅ Выброс исключения при недоступности гида
- ✅ Rollback транзакции при ошибках
- ✅ Использование pessimistic locking (lockForUpdate)

**GuideTest (Feature - 3 теста):**
- ✅ Получение списка только активных гидов
- ✅ Фильтрация по минимальному опыту
- ✅ Сортировка по опыту (по убыванию)

**HuntingBookingTest (Feature - 9 тестов):**
- ✅ Создание бронирования с валидными данными
- ✅ Валидация максимума участников (≤10)
- ✅ Проверка доступности гида (1 бронирование в день)
- ✅ Запрет бронирования неактивного гида
- ✅ Запрет бронирования в прошлом
- ✅ Обязательность всех полей
- ✅ Unique constraint предотвращает дубликаты (database level)
- ✅ Transaction rollback при конкурентных запросах
- ✅ Правильный формат ответов об ошибках (409, 422)

### Запуск конкретных тестов

```bash
# Отдельный тест
php artisan test --filter=test_can_create_booking_with_valid_data

# Группа тестов
php artisan test --testsuite=Feature
```

## Возможные улучшения

### Phase 2 (будущее развитие)

1. **Аутентификация**
   - Sanctum для API токенов
   - Роли: admin, guide, customer

2. **Расширенный функционал**
   - Отмена бронирования
   - История бронирований
   - Рейтинги гидов
   - Цены и payment integration

3. **Уведомления**
   - Email подтверждения
   - SMS напоминания
   - Push notifications

4. **Admin Panel**
   - CRUD для гидов
   - Управление бронированиями
   - Статистика и отчёты

5. **Оптимизация**
   - Кэширование списка гидов
   - Queue для email уведомлений
   - Rate limiting для API

## Контакты

Модуль разработан как тестовое задание для BookingCore.

---

**Технологии:** Laravel 12, PHP 8.4, SQLite, PHPUnit
**Покрытие тестами:** 100% основного функционала
