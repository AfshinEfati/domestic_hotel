# Domestic Hotel — Project Context & Working Memory

> مرجع دائمی تصمیم‌ها، قوانین معماری، وضعیت فعلی و کارهای بعدی پروژه `domestic_hotel`.
> در هر ادامه‌ی کار روی این پروژه ابتدا این فایل و سپس کد فعلی branch مربوطه بررسی شود.
> اگر این فایل با کد فعلی اختلاف داشت، اختلاف باید مشخص شود و قبل از هر تغییر تصمیم گرفته شود.

**آخرین بروزرسانی:** 2026-09-10

---

## 1) قوانین قطعی همکاری و تغییر کد

- هیچ تغییر کدی بدون تأیید صریح کاربر انجام نشود.
- هیچ پیشنهاد، refactor یا تغییر معماری صرفاً به دلیل «بهتر بودن» اجرا نشود مگر کاربر تأیید کند.
- در صورت ابهام، حدس نزن؛ سؤال بپرس.
- `main` به سرور متصل است و تغییر روی آن می‌تواند deploy شود؛ کارهای توسعه‌ای فعلی فقط روی branch جدا انجام شوند.
- branch فعلی توسعه Reservation/Pricing:

```text
feature/reservation-foundation
```

- `PROJECT_CONTEXT.md` برای نگهداری context پروژه می‌تواند بدون اجازه‌ی جداگانه به‌روزرسانی شود.
- secret/token/password/credential داخل این فایل نوشته نشود.

---

## 2) قانون قطعی Configuration / Runtime Settings

در Domestic Hotel برای business/runtime configuration نباید به `config/*.php` یا `.env` وابسته شویم.

قانون نهایی:

- هیچ business setting جدید داخل `config/*.php` قرار نگیرد.
- هیچ business setting جدید داخل `.env` یا `.env.example` قرار نگیرد.
- فرض طراحی این است که بعد از تحویل پروژه، تمام مقادیر و رفتارهای قابل تنظیم بدون تغییر حتی یک خط کد از طریق APIهای Admin قابل تغییر باشند.
- settingهای runtime باید در DB ذخیره شوند.
- settingها باید Repository/Service مناسب داشته باشند.
- برای مدیریت settingها API Admin وجود داشته باشد.
- مقدار اولیه فقط با Seeder ایجاد شود.
- Seeder نباید هنگام اجرای مجدد مقدار تغییرکرده توسط Admin را reset کند؛ برای defaultها از الگوی `firstOrCreate` استفاده شود.
- markup، fixed amount، provider settings، reservation settings، availability scheduling/throttling settings و موارد مشابه مشمول این قانون هستند.
- env/config فقط برای نیازهای پایه framework/infrastructure که ذاتاً خارج از business configuration هستند باقی می‌مانند.

### وضعیت اجرای این قانون

در branch `feature/reservation-foundation`:

- business configهای جدید pricing/reference از `.env.example` حذف شده‌اند.
- `config/hotel.php` حذف شده است.
- تنظیمات GRS availability که قبلاً از `config/hotel.php` خوانده می‌شدند به DB setting منتقل شده‌اند.
- `HotelSetting` + Repository + Service + Admin API + Seeder اضافه شده‌اند.

---

## 3) قانون قطعی Migration

از این به بعد:

- هر Model/Table باید migration مستقل خودش را داشته باشد.
- در یک migration چند جدول جدید ساخته نشود.
- نام migration باید مشخص کند متعلق به کدام table است.
- هدف این قانون، ساده شدن rollback، بررسی schema، تغییرات بعدی و نگهداری پروژه است.

نمونه‌ی ممنوع:

```text
create_reservation_foundation_tables.php
  ├── reservations
  ├── reservation_hotels
  ├── reservation_rooms
  └── reservation_purchase_segments
```

ساختار صحیح فعلی:

```text
2026_09_10_090000_create_reservations_table.php
2026_09_10_090010_create_reservation_hotels_table.php
2026_09_10_090020_create_reservation_rooms_table.php
2026_09_10_090030_create_reservation_purchase_segments_table.php
2026_09_10_090100_create_provider_pricing_rules_table.php
2026_09_10_090110_create_hotel_settings_table.php
```

migration چندجدولی اولیه Reservation حذف شده است.

---

## 4) مرز سیستم و نقش Domestic Hotel GDS

معماری ارتباطی:

```text
End User
   ↓
White Label Site
   ↓
Main Backend
   ↓
Domestic Hotel GDS   ← پروژه فعلی
   ↓
Hotel Providers
```

نکات قطعی:

- Hotel GDS مستقیماً با End User کار نمی‌کند.
- مصرف‌کننده مستقیم APIهای ما backendهای بالادستی هستند.
- Hotel GDS فروشنده/مرجع سرویس است؛ سیستم‌های بالادستی باید با contract ما هماهنگ شوند.
- Reservation Reference رسمی را Hotel GDS صادر می‌کند.
- Main/White Label با reference ما برای status، edit، payment registration و عملیات بعدی مراجعه می‌کنند.
- طراحی GDS نباید به reference داخلی سیستم‌های بالادستی وابسته باشد مگر نیاز جدیدی صریحاً تعریف شود.
- login/cart/wallet/UI کاربر نهایی در scope این پروژه نیست مگر بعداً صریحاً اضافه شود.

---

## 5) معماری کدنویسی پروژه

الگوی مورد انتظار:

```text
Controller
  ↓
Service / Service Interface
  ↓
Repository / Repository Interface
  ↓
Model
```

قواعد:

- Controller نباید query مستقیم داشته باشد.
- Service مسئول orchestration/business logic است.
- Repository مالک queryهای model/domain خودش است.
- Repository یک domain نباید برای convenience روی Model domain دیگری query مستقیم بزند.

مثال:

```text
room_calendars query
→ RoomCalendarRepository

pricing rule query
→ ProviderPricingRuleRepository

hotel setting query
→ HotelSettingRepository

reservation query
→ ReservationRepository
```

نمونه‌ی ممنوع:

```php
// داخل AccommodationRepository
RoomCalendar::query();
```

---

## 6) Providerها و اولویت فعلی

Providerهای شناخته‌شده:

- `grs` — GRS / اقامت24
- `parto`
- `iho`
- `snap` — SnappTrip

اولویت فعلی روی GRS است.

### محدودیت مهم GRS

GRS request limit جدی دارد و قبلاً limit account تمام شده است.

بنابراین:

- bulk provider call بدون نیاز و تأیید انجام نشود.
- برای Front/API تا جای ممکن از DB/sample data استفاده شود.
- تست provider کوچک و کنترل‌شده باشد.
- اصلاح ساختاری global limiter همچنان کار جداگانه‌ای است و خودکار انجام نشود.

### GRS runtime settings در DB

این مقادیر اکنون `HotelSetting` هستند و از Admin API قابل تغییرند:

```text
provider.grs.availability.days
provider.grs.availability.chunk_size
provider.grs.availability.throttle_ms
provider.grs.availability.max_attempts
provider.grs.availability.requests_per_minute
```

Seeder مقدار اولیه فعلی را ایجاد می‌کند:

```text
days                = 60
chunk_size          = 20
throttle_ms         = 500
max_attempts        = 1
requests_per_minute = 10
```

`SyncGrsAvailabilityJob` این مقدارها را از `HotelSettingServiceInterface` می‌خواند.

---

## 7) Sync و دریافت داده GRS

### هتل‌ها

```text
fetch:grs-hotel
  ↓
FetchGrsHotelJob
  ↓
GRSAdapter::fetchProperties()
  ↓
ProcessGrsHotelPropertyJob
```

- list هتل‌ها در یک fetch بزرگ دریافت می‌شود.
- propertyهای ایران filter می‌شوند.
- Process job persistence انجام می‌دهد و provider call جدید ندارد.

### RoomType / RatePlan / Facility / Rule

```text
hotel:fetch-data
  ↓
DispatchRoomTypeFetchJobs
  ↓
FetchRoomTypesFromProviderJob
  ↓
provider adapter::fetchRoomTypes(propertyId)
  ↓
HotelDataSyncService
```

GRS property detail می‌تواند RoomType، RatePlan، Facility و Rule را همزمان برگرداند.

### Known issue

- dispatch room-type jobها delay دارد ولی chunk reset می‌تواند rate-limit behavior را ناقص کند.
- scheduler command mismatch بین `fetch-hotel-data` و `hotel:fetch-data` قبلاً مشاهده شده است.
- این موارد checkpoint هستند و بدون تأیید کاربر خودکار اصلاح نشوند.

---

## 8) داده‌های اصلی Hotel

### RoomCalendar

مفاهیم مهم:

```text
accommodation_id
room_type_id
rate_plan_id
day
rack_rate
daily_rate
grs_rate
baby_cot_*_rate
extend_bed_*_rate
min_stay
max_stay
cta
ctd
closed
inventory
provider_id
provider_property_id
provider_room_type_id
provider_rate_plan_id
```

### RoomType

ظرفیت، extra capacity، bed counts، out_of_service و room_type_name.

### RatePlan

meal/board/cancelable/sleeps/min_stay/max_stay/facilities.

### HotelChildPolicy

ماژول مستقل child policy وجود دارد.

### Rule schema

ساختار rule فعلی شامل `rule_categories`, `rules`, `hotel_child_policy` است.

---

## 9) Front API

Routeهای شناخته‌شده زیر `/api/v1/front`:

```text
POST /accommodations/list
POST /accommodations/availability
POST /accommodations/available-rooms
POST /facility-groups/list
POST /facilities/list
POST /room-types/list
POST /rules/list
POST /rules/child-policy
```

### available-rooms

Endpoint جدید مستقل از Availability قدیمی است:

```http
POST /api/v1/front/accommodations/available-rooms
```

Request:

```json
{
  "hotel_id": 1
}
```

Flow:

```text
AvailableRoomsRequest
  ↓
AccommodationController
  ↓
RoomCalendarServiceInterface
  ↓
RoomCalendarService
  ↓
RoomCalendarRepositoryInterface
  ↓
RoomCalendarRepository
```

معیار فعلی:

```text
accommodation_id = hotel_id
day >= today
closed = false
inventory > 0
room_type.out_of_service = false
```

Response به شکل Room → RatePlan → Calendar گروه‌بندی می‌شود.

Availability قبلی نباید برای این endpoint تغییر داده یا reuse شود مگر تصمیم جدیدی گرفته شود.

---

## 10) Pricing / Final Rate

نیاز قطعی:

- هنگام نرخ‌دهی، قیمت فروش GDS باید از قیمت provider جدا باشد.
- فیلد `final_rate` به response نرخ اضافه شود.
- markup فعلی 5% است.
- fixed amount هم در فرمول پیش‌بینی شده است؛ مقدار اولیه فعلی تا اعلام عدد جدید `0` است.
- این منطق نباید hard-code در Availability/Resource/Adapter شود.
- provider-specific rule باید پشتیبانی شود.

فرمول فعلی:

```text
final_rate = base_rate
           + (base_rate × percentage / 100)
           + fixed_amount
```

برای provider rate فعلی base معمولاً `grs_rate` است.

### معماری Pricing

```text
HotelRatePricingService
  ↓
ProviderPricingRuleRepository
  ↓
provider_pricing_rules
```

اگر provider rule فعال وجود نداشته باشد، fallback از DB settings خوانده می‌شود:

```text
pricing.default_percentage
pricing.default_fixed_amount
```

هیچ fallback عددی داخل config/env/business code وجود ندارد؛ نبودن setting لازم باید خطای واضح ایجاد کند.

### Provider pricing rule

Table:

```text
provider_pricing_rules
- provider_id (unique)
- percentage
- fixed_amount
- is_active
```

Seeder برای providerهای موجود rule اولیه 5% + 0 ایجاد می‌کند و هنگام اجرای مجدد admin changes را overwrite نمی‌کند.

### خروجی نرخ

در `available-rooms` نرخ‌های زیر قابلیت `final_rate` دارند:

```text
grs_rate                → final_rate
baby_cot_grs_rate       → baby_cot_final_rate
extend_bed_grs_rate     → extend_bed_final_rate
```

Availability قدیمی نیز با decorator مستقل final rate را برای adult/child/infant/extra اضافه می‌کند.

`final_rate` در `room_calendars` ذخیره نمی‌شود؛ runtime محاسبه می‌شود تا تغییر markup نیاز به update هزاران calendar row نداشته باشد.

قیمت ثبت‌شده داخل Reservation بعداً باید snapshot باشد و با تغییر pricing rule گذشته تغییر نکند.

---

## 11) Admin Runtime Configuration APIs

دو resource جدید Admin برای تنظیمات runtime اضافه شده‌اند:

```text
/api/v1/admin/hotel-settings
/api/v1/admin/provider-pricing-rules
```

هر دو با `Route::apiResource` CRUD کامل دارند.

### hotel_settings

مفاهیم:

```text
key
Group
value
value_type
is_active
```

value typeهای فعلی:

```text
string
integer
float
boolean
json
```

Settingهای اولیه از Seeder ایجاد می‌شوند و بعد از آن Admin API source of truth است.

---

## 12) Reservation — Foundation فعلی

ساختار فعلی:

```text
reservations
    ↓
reservation_hotels
    ↓
reservation_rooms
    ↓
reservation_purchase_segments
```

### reservations

فعلاً شامل پایه‌های زیر است:

```text
reservation_number
total_price
status
email
mobile
```

فیلدهای مالی کامل بعداً اضافه می‌شوند.

### Reservation Reference

- reference توسط خود Hotel GDS ساخته می‌شود.
- سیستم بالادستی با reference ما کار می‌کند.
- تنظیمات reference DB-backed هستند:

```text
reservation.reference_prefix
reservation.reference_random_length
reservation.reference_max_attempts
```

Seeder فعلی:

```text
prefix        = DH
random_length = 10
max_attempts  = 10
```

فرمت پایه فعلی مشابه زیر است:

```text
DH-YYYYMMDD-RANDOM
```

این مقادیر از Admin API قابل تغییرند و از config/env خوانده نمی‌شوند.

---

## 13) Reservation Statuses

Statusهای فعلی قطعی:

```text
1  درخواست رزرو
2  رزرو شده
3  رزرو ناموفق
4  در صف خرید
5  در حال تکمیل خرید
6  صدور ناقص
7  صدور ناموفق
8  نیازمند تکمیل پرداخت
9  صدور موفق
10 در دست بررسی
11 استرداد شده
```

فعلاً transition matrix نهایی تعریف نشده است؛ قوانین دقیق انتقال وضعیت بعداً از کاربر گرفته می‌شود.

---

## 14) Hotel Replacement / Final Hotel

سناریوی قطعی:

ممکن است کاربر هتل A را درخواست کند ولی تیم فروش در نهایت هتل B تهیه کند.

نیازها:

- تمام هتل‌های درگیر reservation قابل ثبت باشند.
- requested/replacement distinction نگهداری شود.
- فقط یک `reservation_hotel` در هر لحظه final باشد.
- final کردن هتل در Service داخل transaction و lock انجام شود.
- تغییر final hotel نباید تاریخچه candidateها را حذف کند.

---

## 15) Multi-provider Purchase

یک اقامت می‌تواند از چند supplier تهیه شود، مثلاً:

```text
2 شب SnappTrip
2 شب GRS
1 شب Provider سوم
```

همچنین ساختار باید split در سطح Room + Night را پوشش دهد.

برای همین `reservation_purchase_segments` زیر `reservation_rooms` قرار گرفته است و شامل پایه‌های زیر است:

```text
provider_id
purchase_method
quantity
from_date
to_date
provider_reference
provider_property_id
provider_room_type_id
provider_rate_plan_id
purchased_at
issued_at
notes
```

Purchase method فعلی:

```text
online
offline/manual
```

سناریوی online failure → manual fallback باید قابل ثبت باشد؛ شروع online نباید Reservation را در flow غیرقابل تغییر قفل کند.

---

## 16) فایل‌ها/لایه‌های Reservation Foundation

روی branch فعلی foundationهای زیر ایجاد شده‌اند:

```text
Models:
Reservation
ReservationHotel
ReservationRoom
ReservationPurchaseSegment
ProviderPricingRule
HotelSetting

DTOs:
ReservationDTO
ReservationHotelDTO
ReservationRoomDTO
ReservationPurchaseSegmentDTO
ProviderPricingRuleDTO
HotelSettingDTO

Repositories + Interfaces:
ReservationRepository
ReservationHotelRepository
ReservationRoomRepository
ReservationPurchaseSegmentRepository
ProviderPricingRuleRepository
HotelSettingRepository

Services + Interfaces:
ReservationService
ReservationReferenceGenerator
HotelRatePricingService
ProviderPricingRuleService
HotelSettingService
AvailabilityRateDecoratorService
```

Bindings در service providerهای مستقل قرار گرفته‌اند.

---

## 17) موارد Pending برای Reservation

هنوز باید از کاربر نهایی شوند:

- تمام فیلدهای مالی Reservation
- payment registration model
- payment status/financial status
- purchase/fulfillment status جداگانه در صورت نیاز
- guest/passenger fields
- voucher/issue fields
- edit rules
- cancel/refund rules
- operator/manual purchase audit fields
- provider confirmation data
- exact transition matrix بین Reservation Statusها
- validation دقیق overlap/split purchase segmentها
- نحوه snapshot کردن قیمت در سطح room/night/segment

تا مشخص شدن این موارد، foundation قابل توسعه است ولی schema Reservation نهایی تلقی نشود.

---

## 18) Swagger / Postman Rule

OpenAPI nested arrays باید با `@OA\Items` نوشته شوند.

Laravel wildcard validation مثل:

```text
rooms.*.passengers.*.type
```

نباید به property واقعی Swagger تبدیل شود.

Swagger/Postman mismatch قدیمی Availability یک known issue است.

---

## 19) Current Checkpoint

Branch توسعه:

```text
feature/reservation-foundation
```

کارهای انجام‌شده در این checkpoint:

- Reservation foundation ایجاد شده.
- migration چندجدولی Reservation حذف و به migrationهای مستقل تقسیم شده.
- pricing service و provider-specific pricing rule اضافه شده.
- `final_rate` به جریان‌های نرخ متصل شده.
- business pricing/reference settings از config/env حذف شده‌اند.
- `HotelSetting` DB-backed اضافه شده.
- Admin APIs برای settings و provider pricing rules اضافه شده‌اند.
- defaultها با Seeder ایجاد می‌شوند.
- Seederها admin changes موجود را overwrite نمی‌کنند.
- GRS availability config نیز به DB settings منتقل شده و `config/hotel.php` حذف شده است.

### قبل از merge به main

باید روی محیط توسعه تست شوند:

```text
migrations
seeders
container bindings
Admin CRUD routes
pricing calculation
provider-specific override
fallback pricing settings
reservation reference generation
available-rooms final rates
old availability decorated final rates
GRS availability job setting resolution
```

`main` تا زمان تأیید کاربر نباید تغییر کند.
