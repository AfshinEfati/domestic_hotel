# Domestic Hotel — Project Context & Working Memory

> این فایل مرجع دائمی تصمیم‌ها، معماری، وضعیت فعلی و کارهای بعدی پروژه `domestic_hotel` است.
> در شروع هر ادامه‌ی کار روی این پروژه باید ابتدا این فایل و سپس کد فعلی پروژه بررسی شود.
> این فایل جایگزین کد نیست؛ اگر بین این فایل و کد فعلی اختلافی وجود داشت، اختلاف باید مشخص شود و قبل از تغییر تصمیم گرفته شود.

**آخرین بروزرسانی context:** 2026-09-10

---

## 1) قوانین قطعی همکاری و معماری

- هیچ تغییر کدی بدون تأیید صریح کاربر انجام نشود.
- هیچ پیشنهاد یا refactor صرفاً به دلیل «بهتر بودن» اجرا نشود مگر کاربر تأیید کند.
- در صورت ابهام، حدس نزن؛ سؤال بپرس.
- ساختار معماری پروژه باید رعایت شود؛ مخصوصاً Service / Repository Pattern.
- Controller نباید query مستقیم داشته باشد.
- Repository یک domain/model نباید برای convenience مستقیم روی Model یک domain دیگر query بزند.
- اطلاعات محرمانه مثل token/password/credential نباید داخل این فایل نوشته شود.
- `main` به سرور deploy می‌شود؛ توسعه‌ی Reservation فعلاً فقط روی branch `feature/reservation-foundation` انجام می‌شود.

### قانون قطعی Configuration / Runtime Settings

- business/runtime setting قابل تغییر داخل `config/*.php` قرار داده نشود.
- business/runtime setting قابل تغییر داخل `.env` یا `.env.example` قرار داده نشود.
- بعد از تحویل پروژه، تمام رفتارها و مقادیر عملیاتی باید بدون تغییر کد از APIهای Admin قابل مدیریت باشند.
- مقادیر اولیه از Seeder ایجاد شوند.
- تنظیمات عمومی runtime سیستم در مدل/جدول `SystemSetting` / `system_settings` نگهداری می‌شوند.
- `SystemSetting` تنظیمات سراسری Hotel GDS است و ارتباطی با یک Accommodation/Hotel مشخص ندارد.
- pricing provider-specific در `ProviderPricingRule` نگهداری می‌شود و نباید با `SystemSetting` مخلوط شود.

### قانون قطعی Migration

- هر جدول یک migration مستقل دارد.
- یک migration نباید چند جدول domain پروژه را ایجاد کند.
- برای index/constraintهایی که نام خودکار Laravel ممکن است از محدودیت MySQL عبور کند، نام کوتاه و صریح تعیین شود.

### قانون قطعی تست و مستندسازی API

- در این پروژه PHPUnit/Test Suite مبنای acceptance و تست endpointهای جدید نیست؛ وضعیت فعلی تست‌های قدیمی به‌دلیل ناسازگاری با schema/routes فعلی قابل اتکا نیست.
- هر endpoint جدید باید هم‌زمان در `app/Docs` با Swagger/OpenAPI مستند شود.
- تست عملی endpointها توسط Swagger و Postman انجام می‌شود.
- برای تست‌های API تا جای ممکن از داده DB استفاده شود و provider call غیرضروری، مخصوصاً GRS، انجام نشود.
- گروه‌بندی Swagger بر اساس endpoint/controller انجام شود، نه صرفاً بر اساس Model. Endpointهایی که یک Controller/flow مشترک دارند باید در Doc منطقی همان Controller/flow کنار هم قرار بگیرند؛ نمونه: `availability` و `available-rooms` هر دو متعلق به Accommodation/Availability flow هستند.

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

- Hotel GDS مستقیماً با End User کار نمی‌کند.
- مصرف‌کننده APIها backendهای بالادستی هستند.
- ما فروشنده/مرجع سرویس هستیم و سیستم‌های بالادستی باید با contract ما هماهنگ شوند.
- Reservation Reference را Hotel GDS صادر می‌کند.
- سیستم‌های بالادستی با reference ما وضعیت رزرو، ویرایش، پرداخت و عملیات بعدی را پیگیری می‌کنند.

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

---

## 4) Providerها و اولویت فعلی

Providerهای شناخته‌شده:

- `grs`
- `parto`
- `iho`
- `snap`

تمرکز فعلی روی GRS / اقامت24 است. محدودیت request آن جدی است؛ تست‌های Reservation/Front تا جای ممکن با DB انجام شوند و provider call غیرضروری زده نشود.

---

## 5) Reservation Foundation

ساختار پایه:

```text
Reservation
  ↓
ReservationHotel(s)
  ↓
ReservationRoom(s)
  ↓
ReservationPurchaseSegment(s)
```

### قواعد قطعی Reservation

- Reservation شماره مرجع داخلی GDS دارد که توسط خود GDS تولید می‌شود.
- یک Reservation می‌تواند چند هتل requested/candidate/replacement داشته باشد.
- فقط یک `ReservationHotel` می‌تواند final باشد.
- Fulfillment می‌تواند بین چند provider تقسیم شود.
- خرید online و offline/manual پشتیبانی می‌شود.
- خرید آنلاین ناموفق می‌تواند با خرید دستی همان هتل یا هتل جایگزین ادامه پیدا کند.
- segment خرید قابلیت `quantity` دارد تا split فقط محدود به شب نباشد.

### Statusهای Reservation

1. درخواست رزرو
2. رزرو شده
3. رزرو ناموفق
4. در صف خرید
5. در حال تکمیل خرید
6. صدور ناقص
7. صدور ناموفق
8. نیازمند تکمیل پرداخت
9. صدور موفق
10. در دست بررسی
11. استرداد شده

Transitionهای state machine هنوز نهایی نشده‌اند.

---

## 6) Pricing / final_rate

- نرخ خام provider حفظ می‌شود.
- نرخ فروش `final_rate` به‌صورت runtime محاسبه می‌شود و در RoomCalendar ذخیره نمی‌شود.
- فرمول پایه فعلی:

```text
final_rate = base_rate + percentage(base_rate) + fixed_amount
```

- مقدار اولیه درصد عمومی 5٪ و fixed amount عمومی صفر است؛ این مقادیر فقط از Seeder وارد `system_settings` می‌شوند.
- rule مخصوص هر provider در `provider_pricing_rules` نگهداری می‌شود.
- اگر provider rule فعال داشته باشد همان استفاده می‌شود؛ در غیر این صورت fallback عمومی از `SystemSettingService` خوانده می‌شود.
- تغییر rule نباید قیمت رزرو ثبت‌شده گذشته را تغییر دهد؛ قیمت Reservation باید snapshot فروش باشد.

### قانون قطعی Pricing در Availability/Search

- `availability` یک پاسخ **نرخ و ظرفیت / discovery** است، نه فاکتور نهایی و نه checkout calculation.
- تعداد و سن مسافران در request برای پیدا کردن اتاق مناسب و composition پیش‌فرض search استفاده می‌شوند، اما response باید building blockهای قیمت را به شکلی برگرداند که Front بتواند تغییرات احتمالی مسافر را بدون round-trip جدید به Main/GDS مقایسه کند.
- نرخ‌های Adult / Child / Infant / Extra باید مطابق policy همان هتل قابل مشاهده باشند؛ Child/Infant شدیداً وابسته به `childPolicy` هستند (`adult`, `half`, `percent`, `fixed`, `free` و قواعد سنی/پوشش).
- `final_rate` در Availability فقط **نسخه‌ی دارای روکش همان rate پایه‌ای است که در response وجود دارد**؛ نباید به تعداد مسافر ضرب شود و نباید از آن total invoice ساخته شود.
- واحد/سطح `final_rate` باید همان واحد/سطح base rate متناظر باقی بماند. اگر base rate یک room rate است، final_rate نیز room rate است؛ اگر base rate per-passenger است، final_rate نیز per-passenger است.
- `AvailabilityRateDecoratorService` نباید passenger count را وارد فرمول markup کند.
- محاسبه‌ی مبلغ قطعی قابل پرداخت / invoice مربوط به مرحله‌ی Booking/Reservation confirmation است و باید از search/discovery pricing جدا بماند.

---

## 7) System Settings

`SystemSetting` منبع تنظیمات عمومی runtime/business خود Domestic Hotel GDS است، نه تنظیمات یک هتل خاص.

لایه‌ها:

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

Seeded system settings فعلی:

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

Seeder باید `firstOrCreate` باشد تا اجرای مجدد Seeder تنظیمات تغییرکرده توسط Admin را overwrite نکند.

---

## 8) Migrationهای Reservation/System Settings روی feature branch

هر جدول migration مستقل دارد:

```text
2026_09_10_090000_create_reservations_table.php
2026_09_10_090010_create_reservation_hotels_table.php
2026_09_10_090020_create_reservation_rooms_table.php
2026_09_10_090030_create_reservation_purchase_segments_table.php
2026_09_10_090100_create_provider_pricing_rules_table.php
2026_09_10_090110_create_system_settings_table.php
```

در `reservation_purchase_segments` نام indexهای composite به‌صورت صریح کوتاه شده‌اند:

- `rps_room_dates_idx`
- `rps_provider_method_idx`

---

## 9) Front / Availability checkpoint

Front APIهای شناخته‌شده:

- POST `/api/v1/front/accommodations/list`
- POST `/api/v1/front/accommodations/availability`
- POST `/api/v1/front/accommodations/available-rooms`

`availability` و `available-rooms` هر دو در یک Accommodation/Availability flow و Controller قرار دارند و Swagger آنها باید در یک Doc منطقی کنار هم نگهداری شود.

`available-rooms` داده RoomCalendar یک هتل مشخص را می‌دهد و `availability` برای search نرخ/ظرفیت استفاده می‌شود.

`final_rate` از Pricing Service فقط به rateهای response روکش اضافه می‌کند و نباید search response را به invoice تبدیل کند.

---

## 10) GRS Availability Settings

تنظیمات runtime مربوط به GRS Availability دیگر از config/env خوانده نمی‌شوند و از `SystemSettingService` می‌آیند.

Known issueهای rate-limit/global limiter همچنان checkpoint هستند و بدون درخواست کاربر refactor نمی‌شوند.

---

## 11) Swagger / Admin API checkpoint

Swagger docs برای endpointهای جدید فعلی:

- `app/Docs/AvailableRoomsDoc.php`
- `app/Docs/ProviderPricingRuleDoc.php`
- `app/Docs/SystemSettingDoc.php`

توجه: در ادامه Doc مربوط به `available-rooms` باید از نظر grouping با Doc مربوط به `availability` و Controller مشترک آنها هماهنگ نگه داشته شود؛ نام فایل به‌تنهایی مرز domain محسوب نمی‌شود.

Admin APIهای جدید:

```text
/api/v1/admin/provider-pricing-rules
/api/v1/admin/system-settings
```

هر دو API شامل index/store/show/update/delete هستند و `apiResource` همچنین PUT/PATCH را برای update پوشش می‌دهد.

---

## 12) Pending Reservation Decisions

مواردی که هنوز باید از کاربر گرفته/نهایی شوند:

- تمام فیلدهای نهایی Reservation
- فیلدهای مالی
- statusهای purchase/fulfillment/provider segment
- payment behavior
- issue/voucher rules
- guest/passenger fields
- edit rules
- cancel/refund rules
- manual operator data
- provider confirmation/reference fields
- جزئیات دقیق split در سطح Room/Night
- audit/history مورد نیاز

---

## 13) Current Next Step

1. تست migration/seed/runtime branch `feature/reservation-foundation`.
2. تست APIهای `system-settings` و `provider-pricing-rules` از Swagger/Postman.
3. تست `availability` / `available-rooms` و محاسبه‌ی markup-only `final_rate` از Swagger/Postman بدون provider call غیرضروری.
4. ادامه طراحی Reservation بعد از دریافت fieldها و قوانین مالی/عملیاتی از کاربر.
