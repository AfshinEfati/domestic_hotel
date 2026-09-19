# Domestic Hotel — Project Context & Working Memory

> این فایل مرجع دائمی تصمیم‌ها، معماری، وضعیت فعلی و کارهای بعدی پروژه `domestic_hotel` است.
> در شروع هر ادامه‌ی کار روی این پروژه باید ابتدا این فایل و سپس کد فعلی branch توسعه بررسی شود.
> این فایل جایگزین کد نیست؛ اگر بین این فایل و کد فعلی اختلافی وجود داشت، کد فعلی باید بررسی شود و اختلاف صریحاً مشخص شود.

**آخرین بروزرسانی context:** 2026-09-17  
**Branch توسعه فعال:** `feature/reservation-foundation`  
**مبنای بازسازی branch:** `main` @ `097533b7a5e8c07acb1c0d42d0564f1484b514a8`  
**وضعیت sync در زمان این بروزرسانی:** branch از HEAD فعلی `main` ساخته شده و در شروع هیچ اختلاف کدی با `main` ندارد.

---

## 1) قوانین قطعی همکاری و معماری

- هیچ تغییر کدی بدون تأیید صریح کاربر انجام نشود.
- هیچ پیشنهاد یا refactor صرفاً به دلیل «بهتر بودن» اجرا نشود مگر کاربر تأیید کند.
- در صورت ابهام بیزینسی، تصمیم جدید اختراع نشود؛ مورد به‌عنوان open decision مشخص شود.
- ساختار معماری پروژه باید رعایت شود؛ مخصوصاً Service / Repository Pattern.
- Controller نباید query مستقیم داشته باشد.
- Repository یک domain/model نباید برای convenience مستقیم روی Model یک domain دیگر query بزند.
- اطلاعات محرمانه مثل token/password/credential نباید داخل این فایل نوشته شود.
- `main` branch deploy است. توسعه‌ی Reservation فعلاً روی `feature/reservation-foundation` انجام می‌شود مگر کاربر صریحاً branch دیگری تعیین کند.
- قبل از هر تغییر جدید، branch فعلی و اختلاف آن با `main` بررسی شود.
- کد فعلی source of truth اجرایی است؛ این context source of truth تصمیم‌ها و checkpointهاست.

### قانون قطعی Configuration / Runtime Settings

- business/runtime setting قابل تغییر داخل `config/*.php` قرار داده نشود.
- business/runtime setting قابل تغییر داخل `.env` یا `.env.example` قرار داده نشود.
- بعد از تحویل پروژه، رفتارها و مقادیر عملیاتی باید تا جای ممکن بدون تغییر کد از APIهای Admin قابل مدیریت باشند.
- مقادیر اولیه runtime از Seeder ایجاد شوند.
- تنظیمات عمومی runtime سیستم در مدل/جدول `SystemSetting` / `system_settings` نگهداری می‌شوند.
- `SystemSetting` تنظیمات سراسری Domestic Hotel GDS است و ارتباطی با یک Accommodation/Hotel مشخص ندارد.
- pricing provider-specific در `ProviderPricingRule` نگهداری می‌شود و نباید با `SystemSetting` مخلوط شود.

### قانون قطعی Migration

- هر جدول یک migration مستقل دارد.
- یک migration نباید چند جدول domain پروژه را ایجاد کند.
- برای index/constraintهایی که نام خودکار Laravel ممکن است از محدودیت MySQL عبور کند، نام کوتاه و صریح تعیین شود.
- schema فعلی قبل از ساخت migration اصلاحی جدید بررسی شود؛ migrationهای موجود نباید صرفاً بر اساس context قدیمی فرض شوند.

### قانون قطعی تست و مستندسازی API

- تست عملی endpointهای جدید توسط Swagger/Postman انجام می‌شود.
- هر endpoint جدید باید هم‌زمان در `app/Docs` با Swagger/OpenAPI مستند شود.
- برای تست API تا جای ممکن از داده DB استفاده شود و provider call غیرضروری، مخصوصاً GRS، انجام نشود.
- گروه‌بندی Swagger بر اساس flow/controller انجام شود، نه صرفاً بر اساس Model.
- `availability` و `available-rooms` هر دو متعلق به Accommodation/Availability flow هستند.
- Test Suite قدیمی مبنای acceptance کلی پروژه نیست؛ با این حال تست‌های regression جدیدی که برای منطق مشخص Availability/Child Policy اضافه شده‌اند باید هنگام تغییر همان منطق حفظ و سبز نگه داشته شوند.

---

## 2) مرز سیستم و نقش Domestic Hotel GDS

```text
End User
   ↓
White Label Site
   ↓
Main Backend
   ↓
Domestic Hotel GDS
   ↓
Hotel Providers
```

- Domestic Hotel GDS مستقیماً با End User کار نمی‌کند.
- مصرف‌کننده APIها backendهای بالادستی هستند.
- ما فروشنده/مرجع سرویس هستیم و سیستم‌های بالادستی باید با contract ما هماهنگ شوند.
- Reservation Reference را Domestic Hotel GDS صادر می‌کند.
- سیستم‌های بالادستی با reference ما وضعیت رزرو و عملیات بعدی را پیگیری می‌کنند.

---

## 3) معماری پروژه

```text
Controller
  ↓
Service / Service Interface
  ↓
Repository / Repository Interface
  ↓
Model
```

Query هر domain باید در Repository همان domain قرار بگیرد.

برای Provider Integration همچنان Adapter layer وجود دارد:

```text
App\Domain\Hotel\Providers\GRSAdapter
App\Domain\Hotel\Providers\PartoAdapter
App\Domain\Hotel\Providers\IHOAdapter
App\Domain\Hotel\Providers\SnappTripAdapter
```

---

## 4) Providerها و حالت Online / Offline

Providerهای شناخته‌شده:

- `grs`
- `parto`
- `iho`
- `snap`

تمرکز فعلی integration روی GRS / اقامت24 است. محدودیت request آن جدی است؛ تست‌های Front/Reservation تا جای ممکن با DB انجام شوند و provider call غیرضروری زده نشود.

### فیلد `is_online`

Provider اکنون علاوه بر `is_active` دارای `is_online` است.

- `is_active=false` یعنی provider غیرفعال است.
- `is_online=false` یعنی خرید از provider به‌صورت API/online انجام نمی‌شود.
- providerهای قابل استفاده در flow آنلاین باید هم active و هم online باشند.

### Offline Provider اختصاصی هتل

Admin endpoint:

```text
POST /api/v1/admin/providers/offline
```

Request:

```json
{
  "accommodation_id": 123
}
```

رفتار فعلی `ProviderService::storeOfflineByAccommodationId()`:

- Accommodation باید وجود داشته باشد.
- Provider با `updateOrCreate` و code زیر ساخته/به‌روزرسانی می‌شود:

```text
hotel-{accommodation_id}
```

- `fa_name` و `en_name` از Accommodation کپی می‌شوند.
- `config = null`
- `is_active = true`
- `is_online = false`

این provider نماینده‌ی خرید دستی/مستقیم همان هتل است.

---

## 5) Reservation Domain — وضعیت فعلی

Reservation دیگر فقط foundation چهارجدولی اولیه نیست. ساختار فعلی procurement/reservation به‌صورت زیر است:

```text
Reservation
  ↓
ReservationHotel(s)
  ↓
ReservationRoom(s)
  ↓
ReservationGuest(s)

ReservationHotel
  ↓
ReservationPurchase(s)
      ↓
      ReservationPurchaseSegment(s)
      ReservationPurchasePayment(s)
      ReservationProviderOperation(s)
      ReservationManualPurchase (0..1)

Reservation
  ↓
ReservationManualReason(s)
```

### قواعد قطعی Reservation

- Reservation شماره مرجع داخلی GDS دارد که توسط خود GDS تولید می‌شود.
- فیلد فعلی آن `reservation_number` است و در DB unique است.
- یک Reservation می‌تواند چند هتل requested/candidate/replacement داشته باشد.
- فقط یک `ReservationHotel` باید final باشد.
- Fulfillment می‌تواند بین چند provider/purchase تقسیم شود.
- خرید online و offline/manual پشتیبانی می‌شود.
- خرید آنلاین ناموفق می‌تواند با خرید دستی همان هتل یا هتل جایگزین ادامه پیدا کند.
- Reservation قیمت فروش را snapshot می‌کند؛ ruleهای pricing آینده نباید مبلغ فروش رزرو گذشته را تغییر دهند.

### Reservation fields فعلی

`reservations` اکنون حداقل شامل این فیلدهاست:

- `reservation_number`
- `agency_id`
- `status`
- `check_in`
- `check_out`
- `sale_amount`
- `tax_amount`
- `commission_amount`
- `booker_first_name`
- `booker_last_name`
- `booker_mobile`
- `booker_email`
- `acc_code`

`commission_amount` signed است و می‌تواند منفی باشد.

### Statusهای Reservation

کد فعلی `ReservationStatus`:

1. `REQUESTED` — درخواست رزرو
2. `RESERVED` — رزرو شده
3. `RESERVATION_FAILED` — رزرو ناموفق
4. `PURCHASE_QUEUED` — در صف خرید
5. `PURCHASE_IN_PROGRESS` — در حال تکمیل خرید
6. `PARTIALLY_ISSUED` — صدور ناقص
7. `ISSUE_FAILED` — صدور ناموفق
8. `PAYMENT_REQUIRED` — نیازمند تکمیل پرداخت
9. `ISSUED` — صدور موفق
10. `UNDER_REVIEW` — در دست بررسی
11. `REFUNDED` — استرداد شده
12. `PURCHASE_REFUND` — استرداد خرید

Transitionهای state machine هنوز نهایی و enforce نشده‌اند.

---

## 6) Reservation Create API

Front endpoint فعلی:

```text
POST /api/v1/front/reservations/create
```

Controller:

```text
App\Http\Controllers\Api\V1\Reservation\ReservationController
```

Service flow:

```text
ReservationService::createRequest()
```

این flow داخل DB transaction موارد زیر را ایجاد می‌کند:

1. `Reservation`
2. یک `ReservationHotel` اولیه با `is_final=true`
3. `ReservationRoom`های درخواست
4. `ReservationGuest`های هر اتاق

### Request فعلی Reservation

سطح Reservation:

- `agency_id`
- `check_in`
- `check_out`
- `sale_amount`
- `acc_code` nullable

Booker:

- `first_name`
- `last_name`
- `mobile`
- `email` nullable

Hotel:

- `accommodation_id`
- `rooms[]`

Room:

- `room_type_id` nullable
- `rate_plan_id` nullable
- `room_name` nullable
- حداقل یکی از `room_type_id` یا `room_name` باید وجود داشته باشد.
- `guests[]` الزامی است.

Guest:

- `type`
- `first_name`
- `last_name`
- `gender` nullable
- `birth_date` nullable
- `country_id` nullable
- `national_id` nullable
- `passport_number` nullable
- `passport_issuer_country_id` در صورت passport required
- `passport_expiry_date` در صورت passport required و باید آینده باشد.

### Swagger gap

در زمان بروزرسانی این context برای Reservation Create هنوز `ReservationDoc.php` یا Swagger Doc متناظر در `app/Docs` وجود ندارد. این مورد باید قبل از نهایی دانستن API برطرف شود.

---

## 7) Reservation Purchase / Procurement

### `reservation_purchases`

Purchase در سطح `ReservationHotel` ساخته می‌شود و شامل این مفاهیم است:

- `reservation_hotel_id`
- `provider_id`
- `status`
- `quoted_provider_id`
- `provider_quoted_amount`
- `purchase_amount`
- `confirmation_code`
- `provider_status`
- `expires_at`
- `issued_at`
- `purchase_mode`
- `manual_reason`
- `manual_rule_id`

`provider_quoted_amount` مبلغ quote اولیه provider است و `purchase_amount` مبلغ واقعی نهایی procurement است.

### Purchase mode

`PurchaseMethod` فعلی:

```text
ONLINE = 1
OFFLINE = 2
```

### Manual/offline reasonهای فعلی

`PurchaseManualReason`:

1. `MATCHED_RULE`
2. `OFFLINE_PROVIDER`
3. `INACTIVE_PROVIDER`
4. `INSUFFICIENT_CREDIT`
5. `CREDIT_UNAVAILABLE`

---

## 8) Purchase Resolver — منطق فعلی تصمیم Online/Offline

Service:

```text
App\Services\PurchaseResolver
```

Interface:

```text
App\Services\Contracts\PurchaseResolverInterface
```

### ترتیب تصمیم‌گیری فعلی

برای `resolve(reservationNumber, providerId)`:

1. Reservation باید وجود داشته باشد.
2. Reservation باید دقیقاً یک final hotel داشته باشد.
3. Provider باید وجود داشته باشد.
4. Provider انتخابی باید به Accommodation map شده باشد؛ استثنا provider اختصاصی هتل با code `hotel-{accommodation_id}` است.
5. اگر provider inactive باشد ⇒ `OFFLINE / INACTIVE_PROVIDER`.
6. اگر provider online نباشد ⇒ `OFFLINE / OFFLINE_PROVIDER`.
7. credit snapshot provider خوانده می‌شود.
8. اگر credit وجود نداشته باشد یا `synced_at` نداشته باشد ⇒ `OFFLINE / CREDIT_UNAVAILABLE`.
9. اگر `balance < orderAmount` ⇒ `OFFLINE / INSUFFICIENT_CREDIT`.
10. manual purchase rule منطبق بررسی می‌شود.
11. اگر rule منطبق باشد ⇒ `OFFLINE / MATCHED_RULE`.
12. در غیر این صورت ⇒ `ONLINE`.

### نکته مهم amount

تا قبل از دریافت quote واقعی provider، resolver از `reservation.sale_amount` به‌عنوان amount موجود برای eligibility استفاده می‌کند.

این فقط تصمیم اولیه است. قبل از خرید واقعی باید quote واقعی provider دوباره از نظر اعتبار/amount بررسی شود.

### نکته مهم orchestration

`PurchaseResolver` **provider API call یا خرید واقعی انجام نمی‌دهد**.

خروجی ONLINE فقط eligibility برای orchestration بعدی است.

`resolveAndRecord()` در حالت offline دلیل را در `reservation_manual_reasons` ثبت می‌کند، اما idempotency command اصلی باید در orchestration خرید تضمین شود.

---

## 9) Manual Purchase Rules

جدول:

```text
purchase_manual_rules
```

فیلدهای فعلی:

- `name`
- `provider_id` nullable
- `accommodation_id` nullable
- `minimum_amount` nullable
- `maximum_amount` nullable
- `start_time` nullable
- `end_time` nullable
- `is_active`

Rule matching فعلی می‌تواند بر اساس provider، hotel، amount و ساعت جاری تصمیم خرید دستی بگیرد.

Seeder فعلی:

```text
PurchaseManualRuleSeeder
```

---

## 10) Provider Credit Balance

جدول:

```text
provider_credit_balances
```

برای هر provider حداکثر یک رکورد وجود دارد.

فیلدهای فعلی:

- `provider_id` unique
- `balance`
- `low_balance_threshold`
- `low_balance_notified_at`
- `synced_at`

مبالغ IRR هستند.

`PurchaseResolver` snapshot بدون `synced_at` را معتبر حساب نمی‌کند.

---

## 11) Manual Purchase Execution

جدول:

```text
reservation_manual_purchases
```

ارتباط:

```text
ReservationPurchase 1 ─── 0..1 ReservationManualPurchase
```

فیلدهای فعلی:

- `reservation_purchase_id` unique
- `acc_code`
- `purchased_at`
- `description`

این جدول metadata اجرای دستی purchase را نگه می‌دارد؛ دلیل رفتن به حالت manual در خود purchase / manual reason flow نگهداری می‌شود.

---

## 12) Purchase Payments

جدول:

```text
reservation_purchase_payments
```

یک Purchase می‌تواند چند payment داشته باشد.

فیلدهای اصلی:

- `reservation_purchase_id`
- `amount` IRR
- `source`
- `bank_account_id` nullable
- `card_id` nullable
- `paid_at` nullable
- `reference` nullable
- `receipt_document_id` nullable
- `status`
- `description` nullable

Support class فعلی برای source:

```text
App\Support\Reservation\PaymentSource
```

جزئیات نهایی payment status/business transition هنوز checkpoint است.

---

## 13) Provider Operation Audit

جدول:

```text
reservation_provider_operations
```

برای ثبت timeline عملیات provider ساخته شده و به `reservation_purchase_id` متصل است.

اطلاعات فعلی شامل:

- operation type
- execution status
- attempt number
- handler class/method
- provider URL
- sanitized request headers
- request body
- HTTP status
- response headers/body
- idempotency key
- exception class/message/line
- started_at / finished_at
- duration_ms

هدف این جدول audit/debug/retry trace عملیات provider است.

---

## 14) Purchase Segments

جدول فعلی:

```text
reservation_purchase_segments
```

Segment اکنون به هر دو مورد زیر متصل است:

- `reservation_purchase_id`
- `reservation_room_id`

فیلدهای فعلی قیمت procurement در segment:

- `from_date`
- `to_date` end-exclusive
- `nightly_purchase_amount`
- `nightly_extra_bed_purchase_amount`
- `nightly_child_purchase_amount`
- `nightly_infant_purchase_amount`

### Gap مهم

در تصمیم معماری قبلی گفته شده بود segment باید `quantity` داشته باشد تا split فقط محدود به شب نباشد، اما migration فعلی `reservation_purchase_segments` هنوز `quantity` ندارد.

این اختلاف باید قبل از نهایی کردن procurement schema تصمیم‌گیری/اصلاح شود.

---

## 15) Pricing / `final_rate`

- نرخ خام provider حفظ می‌شود.
- نرخ فروش `final_rate` به‌صورت runtime محاسبه می‌شود و در RoomCalendar ذخیره نمی‌شود.
- فرمول پایه فعلی:

```text
final_rate = base_rate + percentage(base_rate) + fixed_amount
```

- مقدار اولیه درصد عمومی 5٪ و fixed amount عمومی صفر است.
- rule مخصوص هر provider در `provider_pricing_rules` نگهداری می‌شود.
- اگر provider rule فعال داشته باشد همان استفاده می‌شود؛ در غیر این صورت fallback عمومی از `SystemSettingService` خوانده می‌شود.
- تغییر rule نباید قیمت رزرو ثبت‌شده گذشته را تغییر دهد.

### قانون قطعی Pricing در Availability/Search

- `availability` پاسخ discovery نرخ/ظرفیت است، نه invoice نهایی.
- passenger composition برای انتخاب اتاق و policy استفاده می‌شود.
- نرخ Adult / Child / Infant / Extra باید مطابق child policy قابل محاسبه/نمایش باشد.
- `final_rate` فقط نسخه markup شده همان base rate متناظر است.
- `AvailabilityRateDecoratorService` نباید passenger count را داخل markup ضرب کند.
- total قطعی checkout/booking باید از discovery pricing جدا بماند.

---

## 16) System Settings

`SystemSetting` منبع تنظیمات عمومی runtime/business خود Domestic Hotel GDS است.

لایه‌های فعلی:

```text
SystemSetting
SystemSettingDTO
SystemSettingRepositoryInterface
SystemSettingRepository
SystemSettingServiceInterface
SystemSettingService
SystemSettingServiceProvider
SystemSettingController
SystemSettingResource
StoreSystemSettingRequest
UpdateSystemSettingRequest
SystemSettingSeeder
```

Support classes:

```text
App\Support\System\SystemSettingKey
App\Support\System\SystemSettingValueType
```

Admin API:

```text
/api/v1/admin/system-settings
```

Seeded settings فعلی:

- `pricing.default_percentage = 5`
- `pricing.default_fixed_amount = 0`
- `reservation.reference_prefix = DH`
- `reservation.reference_random_length = 10`
- `reservation.reference_max_attempts = 10`
- `provider.grs.availability.days = 60`
- `provider.grs.availability.chunk_size = 20`
- `provider.grs.availability.throttle_ms = 500`
- `provider.grs.availability.max_attempts = 1`
- `provider.grs.availability.requests_per_minute = 10`

Seeder باید `firstOrCreate` باشد تا اجرای مجدد تنظیمات تغییرکرده Admin را overwrite نکند.

نام قدیمی `HotelSetting` حذف/rename شده و مبنا `SystemSetting` است.

---

## 17) Reservation / Procurement Migrations فعلی

Migrationهای مهم فعلی:

```text
2026_09_10_090000_create_reservations_table.php
2026_09_10_090010_create_reservation_hotels_table.php
2026_09_10_090020_create_reservation_rooms_table.php
2026_09_10_090025_create_reservation_guests_table.php
2026_09_10_090027_create_reservation_purchases_table.php
2026_09_10_090030_create_reservation_purchase_segments_table.php
2026_09_10_090100_create_provider_pricing_rules_table.php
2026_09_10_090110_create_system_settings_table.php
2026_09_12_150000_add_is_online_to_providers_table.php
2026_09_13_120000_create_reservation_provider_operations_table.php
2026_09_13_130000_create_reservation_manual_purchases_table.php
2026_09_13_140000_create_reservation_purchase_payments_table.php
2026_09_14_120000_create_purchase_manual_rules_table.php
2026_09_14_140000_add_manual_purchase_fields_to_reservation_purchases_table.php
2026_09_14_150000_create_provider_credit_balances_table.php
2026_09_16_110000_create_reservation_manual_reasons_table.php
```

---

## 18) Front / Availability — وضعیت فعلی

Front APIهای فعلی مهم:

```text
POST /api/v1/front/accommodations/list
POST /api/v1/front/accommodations/availability
POST /api/v1/front/accommodations/available-rooms
POST /api/v1/front/reservations/create
POST /api/v1/front/rules/child-policy
```

### Availability request

فیلدهای اصلی:

- `state`
- `city`
- `check_in`
- `check_out`
- `rooms[]`
- `rooms[].passengers[]`

Passenger typeهای پذیرفته‌شده:

```text
adult / adl
child / chd
infant / inf
```

برای child/infant، `age` الزامی است.

### سقف جستجو

در هر availability request حداکثر **۵ اتاق** مجاز است.

این محدودیت explicit validation دارد و پیام فارسی برای `rooms.max` تعریف شده است.

### Mixed Room Allocation

منطق Availability اکنون درخواست چند اتاق مشابه را مجبور نمی‌کند همگی از یک `room_type` تأمین شوند.

الگوریتم فعلی:

- ترکیب قابل‌قبول room typeها را برای کل request بررسی می‌کند.
- full request باید قابل fulfil باشد؛ در غیر این صورت hotel رد می‌شود.
- inventory باید برای **تمام شب‌های اقامت** کافی باشد.
- ظرفیت واقعی یک room type در اقامت چندشبه بر اساس کمترین inventory آن در بازه محدود می‌شود.
- از بین ترکیب‌های معتبر، ترکیب با هزینه کمتر انتخاب می‌شود.
- requestهای constrained باید طوری allocate شوند که total ترکیب کمینه بماند، نه صرفاً greedy بر اساس اولین room.

Regression tests فعلی:

```text
tests/Unit/AvailabilityMixedRoomAllocationTest.php
tests/Feature/AvailabilityRoomLimitTest.php
```

### `available-rooms`

`available-rooms` از `RoomCalendarService::getAvailableRoomsByAccommodationId()` استفاده می‌کند.

Response شامل room typeها، rate planها و calendar روزانه است و برای rateهای GRS، extra bed و baby cot، `final_rate` runtime محاسبه می‌شود.

---

## 19) Child Policy — coverage logic فعلی

منطق child policy از حالت ساده age bucket جلوتر رفته و limitهای coverage را در Availability لحاظ می‌کند.

فیلدهای مهم فعلی policy شامل:

- `max_infant_age`
- `max_child_age`
- `max_children_covered`
- `max_infants_covered`
- `infant_when_disabled`
- `child_when_disabled`
- `infant_pricing_type`
- `child_pricing_type`
- service conditionها

### قواعد فعلی coverage

- `max_children_covered` limit مشترک coverage کودک/نوزاد است.
- `max_infants_covered` می‌تواند sub-limit مخصوص infant باشد.
- وقتی shared limit پر شود، کودک اضافه طبق fallback policy تبدیل/محاسبه می‌شود.
- در allowance مشترک، free infant نسبت به half-rate child اولویت دارد تا کمترین قیمت معتبر حاصل شود.
- `max_children_covered = 0` یعنی هیچ کودک/نوزادی از مزیت child policy استفاده نمی‌کند و fallback اعمال می‌شود.
- `max_children_covered = null` به معنی unlimited shared coverage است و `max_infants_covered` در صورت وجود همچنان اعمال می‌شود.
- parser متن policy عبارت‌های مربوط به «رایگان و نیم‌بها فقط برای N کودک» و infant sub-limit را تشخیص می‌دهد.

Regression test فعلی:

```text
tests/Unit/ChildPolicyCoverageTest.php
```

---

## 20) GRS Availability Settings

تنظیمات runtime مربوط به GRS Availability از `SystemSettingService` خوانده می‌شوند، نه config/env.

Known issueهای rate-limit/global limiter همچنان checkpoint هستند و بدون درخواست کاربر refactor نمی‌شوند.

---

## 21) Swagger / Admin API checkpoint

Swagger docs مهم فعلی که در تغییرات اخیر اضافه/فعال شده‌اند:

```text
app/Docs/AvailableRoomsDoc.php
app/Docs/OfflineProviderDoc.php
app/Docs/ProviderPricingRuleDoc.php
app/Docs/SystemSettingDoc.php
```

Admin APIهای مهم:

```text
/api/v1/admin/providers/offline
/api/v1/admin/provider-pricing-rules
/api/v1/admin/system-settings
```

### Gap مستندسازی

`POST /api/v1/front/reservations/create` route و controller دارد، اما در زمان این checkpoint Swagger Doc اختصاصی Reservation در `app/Docs` پیدا نشد.

---

## 22) Database Architecture Docs

فایل‌های فعلی:

```text
docs/database/domestic_hotel_schema.dbml
docs/database/management-database-architecture.md
```

`management-database-architecture.md` یک snapshot مدیریتی مفید است ولی در زمان این context حداقل دو نکته‌ی آن نسبت به کد فعلی stale است:

1. نوشته بود `reservations` reference ندارد؛ درحالی‌که اکنون `reservation_number` وجود دارد و unique است.
2. هنوز درست اشاره می‌کند که `reservation_purchase_segments.quantity` وجود ندارد.

برای تصمیم schema همیشه migration فعلی ملاک بررسی است.

---

## 23) Known Gaps / Open Decisions

مواردی که هنوز نهایی نیستند یا نیاز به تصمیم دارند:

- state machine و transitionهای مجاز Reservation.
- status workflow مستقل/نهایی برای Purchase و Payment.
- orchestration واقعی خرید آنلاین provider بعد از `PurchaseResolver`.
- idempotency command سطح business برای retry خرید.
- زمان/نحوه recheck اعتبار provider بر اساس quote واقعی.
- رفتار نهایی partial issue / issue failure / retry.
- voucher/issue rules.
- cancel/refund flow کامل.
- edit rules بعد از ایجاد Reservation.
- نحوه نهایی محاسبه commission و tax در lifecycle رزرو.
- `quantity` در `reservation_purchase_segments` طبق تصمیم قبلی هنوز پیاده نشده است.
- single-final guarantee برای `ReservationHotel` در حال حاضر در business flow مدیریت می‌شود؛ constraint اختصاصی DB برای «فقط یک final» باید در صورت نیاز جداگانه تصمیم‌گیری شود.
- Swagger Doc برای Reservation Create هنوز اضافه نشده است.
- برخی schemaهای legacy پروژه هنوز DB ENUM دارند؛ این موضوع جزو migrationهای قدیمی است و بدون درخواست کاربر تغییر داده نشود.

---

## 24) وضعیت تغییرات از Context قبلی

Context قبلی عملاً در 2026-09-10 متوقف شده بود.

بین آخرین baseline آن context (`39718b01...`) و snapshot فعلی `main` (`097533b7...`) تعداد **142 commit** وجود دارد.

مهم‌ترین دسته تغییرات این بازه:

- تکمیل Reservation fields/reference.
- اضافه شدن Reservation Guest.
- اضافه شدن Reservation Purchase و procurement relationships.
- اضافه شدن Provider Operation audit.
- اضافه شدن Manual Purchase execution.
- اضافه شدن Purchase Payments.
- اضافه شدن Manual Purchase Rules.
- اضافه شدن Provider Credit Balance.
- اضافه شدن Purchase Resolver و دلایل offline/manual.
- اضافه شدن Reservation Manual Reasons.
- اضافه شدن `is_online` به Provider.
- اضافه شدن Offline Provider Admin flow.
- تکمیل Reservation Create endpoint.
- rename کامل `HotelSetting` به `SystemSetting`.
- تغییرات گسترده Availability allocation.
- explicit سقف 5-room search.
- mixed room-type allocation بر اساس cheapest valid combination.
- inventory validation در تمام شب‌ها.
- تکمیل Child Policy coverage/sub-limit parsing و pricing behavior.
- اضافه شدن regression testهای focused برای Availability/Child Policy.
- اضافه شدن DBML/management database architecture docs.

---

## 25) Current Next Step

مبنای ادامه کار از این checkpoint:

1. همه تغییرات جدید Reservation روی `feature/reservation-foundation` انجام شوند.
2. قبل از توسعه orchestration خرید، contract دقیق مراحل Reserve/Purchase/Issue با providerها نهایی شود.
3. gap `reservation_purchase_segments.quantity` تعیین تکلیف شود.
4. Swagger Doc برای `POST /api/v1/front/reservations/create` اضافه شود.
5. Purchase/Payment statusها و state transitionها نهایی شوند.
6. سپس flow واقعی provider purchase با audit/idempotency و fallback دستی پیاده شود.

در شروع جلسه‌ی بعدی ابتدا همین فایل، HEAD branch `feature/reservation-foundation` و diff آن با `main` بررسی شود.
