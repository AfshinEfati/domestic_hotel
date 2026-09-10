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

Endpoint `available-rooms` مستقل از Availability flow قدیمی طراحی شده است و داده RoomCalendar را از RoomCalendar Service/Repository می‌گیرد.

`final_rate` از Pricing Service روی response نرخ‌ها اضافه می‌شود.

---

## 10) GRS Availability Settings

تنظیمات runtime مربوط به GRS Availability دیگر از config/env خوانده نمی‌شوند و از `SystemSettingService` می‌آیند.

Known issueهای rate-limit/global limiter همچنان checkpoint هستند و بدون درخواست کاربر refactor نمی‌شوند.

---

## 11) Pending Reservation Decisions

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

## 12) Current Next Step

1. تست migration/seed/runtime branch `feature/reservation-foundation`.
2. تست Admin APIs برای `system-settings` و `provider-pricing-rules`.
3. تست محاسبه `final_rate` بدون provider call غیرضروری.
4. ادامه طراحی Reservation بعد از دریافت fieldها و قوانین مالی/عملیاتی از کاربر.
