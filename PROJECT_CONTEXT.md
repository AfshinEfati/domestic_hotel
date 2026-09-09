# Domestic Hotel — Project Context & Working Memory

> این فایل مرجع دائمی تصمیم‌ها، معماری، وضعیت فعلی و کارهای بعدی پروژه `domestic_hotel` است.
> در شروع هر ادامه‌ی کار روی این پروژه باید ابتدا این فایل و سپس کد فعلی پروژه بررسی شود.
> این فایل جایگزین کد نیست؛ اگر بین این فایل و کد فعلی اختلافی وجود داشت، اختلاف باید مشخص شود و قبل از تغییر تصمیم گرفته شود.

**آخرین بروزرسانی context:** 2026-09-09

---

## 1) قانون اصلی همکاری روی این پروژه

- هیچ تغییر کدی بدون تأیید صریح کاربر انجام نشود.
- هیچ پیشنهاد یا refactor صرفاً به دلیل «بهتر بودن» اجرا نشود مگر کاربر تأیید کند.
- در صورت ابهام، حدس نزن؛ سؤال بپرس.
- ساختار معماری پروژه باید رعایت شود؛ مخصوصاً Service / Repository Pattern.
- Controller نباید query مستقیم داشته باشد.
- Repository یک domain/model نباید برای convenience مستقیم روی Model یک domain دیگر query بزند؛ اگر data متعلق به RoomCalendar است، query باید در `RoomCalendarRepository` باشد.
- این فایل (`PROJECT_CONTEXT.md`) تنها فایلی است که کاربر اجازه داده برای نگهداری context پروژه بدون گرفتن اجازه‌ی جداگانه بروزرسانی شود.
- تغییر هر فایل دیگر همچنان نیازمند تأیید صریح است.
- اطلاعات محرمانه مثل token/password/credential نباید داخل این فایل نوشته شود.

---

## 2) مرز سیستم و نقش Domestic Hotel GDS

این پروژه **Hotel GDS** است و مرز آن به شکل زیر است:

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
- مصرف‌کننده‌ی APIهای ما backendهای بالادستی هستند.
- ما فروشنده/مرجع سرویس هستیم؛ سیستم‌های بالادستی باید با قرارداد ما هماهنگ باشند، نه برعکس.
- **Reservation Reference را خود Hotel GDS صادر می‌کند.**
- سیستم‌های بالادستی با reference صادرشده توسط ما برای مواردی مثل این‌ها مراجعه می‌کنند:
  - مشاهده وضعیت رزرو
  - درخواست ویرایش
  - ثبت پرداخت
  - عملیات بعدی رزرو
- نباید طراحی Hotel GDS را به reference داخلی White Label/Main وابسته کنیم، مگر بعداً نیاز مشخصی تعریف شود.
- منطق‌هایی مثل login کاربر نهایی، wallet کاربر، cart و UI کاربر در محدوده این پروژه نیستند مگر صراحتاً بعداً به قرارداد API افزوده شوند.

---

## 3) معماری پروژه

الگوی فعلی و مورد انتظار:

```text
Controller
  ↓
Service / Service Interface
  ↓
Repository / Repository Interface
  ↓
Model
```

قانون مهم:

```text
Query مربوط به room_calendars
→ RoomCalendarRepository

منطق grouping / transformation / orchestration
→ RoomCalendarService یا Service مناسب domain

Request/Response
→ Controller + FormRequest + Resource
```

نمونه‌ای که **نباید** انجام شود:

```php
// داخل AccommodationRepository
RoomCalendar::query();
```

چون `RoomCalendar` repository/service مستقل خودش را دارد.

---

## 4) Providerها و اولویت فعلی

Providerهای شناخته‌شده در پروژه:

- `grs` — اقامت24 / GRS
- `parto`
- `iho`
- `snap` — SnappTrip

### اولویت فعلی

تمرکز فعلی روی **GRS / اقامت24** است.

IHO و سایر providerها فعلاً اولویت بعدی هستند، مگر کاربر صراحتاً جهت را عوض کند.

### محدودیت مهم GRS

GRS محدودیت request جدی دارد و قبلاً account limit تمام شده است.

بنابراین:

- تست bulk روی provider انجام نشود مگر لازم و تأییدشده باشد.
- تا جای ممکن برای تست Front/API از داده DB استفاده شود.
- provider callها در تست محدود و کوچک باشند.
- اصلاح limiter مربوط به GRS قرار است **بعداً** انجام شود؛ فعلاً نباید خودسرانه تغییر داده شود.

---

## 5) وضعیت sync و دریافت داده GRS

### `fetch:grs-hotel`

Flow فعلی دریافت لیست هتل‌های GRS:

```text
fetch:grs-hotel
  ↓
FetchGrsHotelJob
  ↓
GRSAdapter::fetchProperties()
  ↓
ProcessGrsHotelPropertyJob per property
```

نکات مهم:

- `GRSAdapter::fetchProperties()` یک درخواست `/v1/properties` با count بزرگ می‌زند.
- `FetchGrsHotelJob` فقط propertyهای ایران (`country_id = 222`) را dispatch می‌کند.
- `ProcessGrsHotelPropertyJob` persistence/transform انجام می‌دهد و provider call جدید نمی‌زند.
- این flow برای sync لیست هتل‌ها نسبت به crawlهای چنددرخواستی مناسب‌تر است.

### RoomType / RatePlan / Facilities / Rules

Flow فعلی:

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

در GRS، property detail برای هر property می‌تواند همزمان داده‌های زیر را بدهد:

- Room Types
- Rate Plans
- Facilities
- Rules

`HotelDataSyncService` آن‌ها را در مدل‌های داخلی sync می‌کند.

### نکته‌ی rate limit

Dispatch فعلی room-type jobs delay دارد، ولی count/delay در chunkها reset می‌شود؛ بنابراین برای اجرای وسیع همچنان باید احتیاط شود.

---

## 6) Availability sync و محدودیت rate limiter

Availability GRS از jobهای per-property استفاده می‌کند.

نکته‌ی شناخته‌شده:

- limiter فعلی availability property را داخل key دارد.
- اگر محدودیت واقعی GRS account-wide/provider-wide باشد، این طراحی global limit را تضمین نمی‌کند.
- throttle داخلی هر job هم global بین workerها نیست.

این مورد شناخته شده ولی طبق تصمیم کاربر **فعلاً برای اصلاح در اولویت نیست**.

همچنین `hotel:sync grs` برای bulk testing مناسب نیست، چون direct crawl انجام می‌دهد و می‌تواند request زیادی تولید کند.

---

## 7) Scheduler شناخته‌شده

Scheduler فعلی شامل این flowها بوده است:

- daily GRS availability sync
- weekly hotel data fetch
- weekly GRS hotel fetch

یک mismatch شناخته‌شده بین command scheduler و signature واقعی `hotel:fetch-data` دیده شده بود (`fetch-hotel-data` در scheduler).

این مورد صرفاً checkpoint است و بدون درخواست کاربر نباید خودکار اصلاح شود.

---

## 8) مدل‌های مهم داده فعلی

### RoomCalendar

فیلدهای مهم شامل:

- `accommodation_id`
- `room_type_id`
- `rate_plan_id`
- `day`
- `rack_rate`
- `daily_rate`
- `grs_rate`
- baby cot rates
- extra/extend bed rates
- `min_stay`
- `max_stay`
- `cta`
- `ctd`
- `closed`
- `inventory`
- `provider_id`
- provider property/room/rate-plan IDs

روابط مهم:

- accommodation
- roomType
- ratePlan
- provider

### RoomType

شامل ظرفیت، extra capacity، تعداد تخت‌ها، out_of_service و `room_type_name_id` است.

### RatePlan

شامل meal/board/cancelable/sleeps/min_stay/max_stay/facilities است.

### RoomTypeName

ماژول مستقل برای normalize/name mapping اتاق‌ها اضافه شده است.

### HotelChildPolicy

ماژول مستقل child policy اضافه شده و از ruleهای provider قابل استخراج/sync است.

### Rule schema

ساختار جدید شامل:

- `rule_categories`
- `rules`
- `hotel_child_policy`

است.

---

## 9) Front API فعلی

Routeهای Front شناخته‌شده زیر `/api/v1/front`:

- `POST /accommodations/list`
- `POST /accommodations/availability`
- `POST /facility-groups/list`
- `POST /facilities/list`
- `POST /room-types/list`
- `POST /rules/list`
- `POST /rules/child-policy`

### Availability فعلی

`/api/v1/front/accommodations/availability` برای search/availability موجود است و منطق خودش را دارد.

قانون مهم برای توسعه جدید:

**برای endpoint جدید اتاق‌های یک هتل نباید Availability فعلی تغییر داده شود یا reused شود مگر بعداً صراحتاً تصمیم گرفته شود.**

Availability فعلی بر اساس:

- state/city
- check-in/check-out
- rooms/passengers
- ظرفیت
- child pricing

کار می‌کند.

برای هر شب در بازه باید calendar موجود باشد و:

```text
closed = false
inventory > 0
```

باشد؛ وگرنه room برای آن درخواست unavailable محسوب می‌شود.

---

## 10) Endpoint جدید «Available Rooms by Hotel» — تصمیم فعلی

نیاز جدید:

- endpoint کاملاً جدید باشد.
- با Availability فعلی کاری نداشته باشد.
- method حتماً `POST` باشد.
- فقط ID هتل را بگیرد.
- تمام Roomهای دارای availability آینده و قیمت‌های روزانه را برگرداند.

Route پیشنهادی/مورد توافق در بحث:

```http
POST /api/v1/front/accommodations/available-rooms
```

Request:

```json
{
  "hotel_id": 1
}
```

معیار اولیه داده:

```text
accommodation_id = hotel_id
day >= today
closed = false
inventory > 0
room_type.out_of_service = false
```

ساختار response مورد نظر:

```text
hotel/accommodation
  ↓
rooms[]
  ↓
rate_plans[]
  ↓
calendar[]
      day
      inventory
      rates
      min/max stay
      cta/ctd
      ...
```

### معماری صحیح این endpoint

Query باید در `RoomCalendarRepository` باشد، نه `AccommodationRepository`.

Flow مورد انتظار:

```text
AvailableRoomsRequest
  ↓
AccommodationController (یا controller مناسب Front)
  ↓
RoomCalendarServiceInterface
  ↓
RoomCalendarService
  ↓
RoomCalendarRepositoryInterface
  ↓
RoomCalendarRepository
  ↓
RoomCalendar
```

Repository فقط query/data retrieval انجام دهد.
Service grouping و ساخت ساختار Room → RatePlan → Calendar را انجام دهد.

### Swagger

Swagger این endpoint باید nested arrayها را با `@OA\Items` تعریف کند و هرگز wildcardهای Laravel مثل `rooms.*...` را property واقعی OpenAPI قرار ندهد.

---

## 11) مشکل Swagger / Postman شناخته‌شده

برای endpoint Availability، generator فعلی validation keyهای Laravel مثل:

```text
rooms.*.passengers
rooms.*.passengers.*.type
```

را به property واقعی Swagger تبدیل کرده بود.

این از نظر OpenAPI غلط است.

مشکل نتیجه:

- Postman collection از Swagger import شده.
- Postman برای Exampleهای `Created` و `Validation error` schema/body غلط ساخته.
- در Body request حتی وقتی JSON واقعی درست است validation UI خط قرمز می‌زند.

ساختار صحیح OpenAPI برای nested arrayها باید با:

```text
@OA\Property(type="array")
  @OA\Items(type="object")
    @OA\Property(...)
```

باشد، نه keyهای دارای `*`.

برای collection موجود Postman، می‌توان Exampleهای `Created` و `Validation error` را دستی edit کرد؛ re-import اجباری نیست.

در آینده generator Swagger باید اصلاح شود تا wildcardهای FormRequest را به nested OpenAPI schema تبدیل کند.

---

## 12) DB testing / RoomCalendar checkpoint

برای تست Front در زمانی که GRS limit اجازه provider call نمی‌دهد، استفاده از داده‌ی موجود `room_calendars` مجاز و مفید است.

قبلاً جدول حدود 25k row داشته و تاریخ‌های نمونه قدیمی بوده‌اند.

برای تست می‌توان تاریخ‌ها را با SQL جلو برد، ولی قبل از UPDATE باید collision/unique constraint بررسی و backup گرفته شود.

Front availability برای کار کردن نیاز دارد رکوردهای روزانه:

```text
inventory > 0
closed = 0
```

داشته باشند.

---

## 13) Reservation — نیازمندی‌های قطعی تا این لحظه

**این بخش هنوز طراحی نهایی schema نیست.** کاربر قرار است statusها، fieldهای کامل و مالی را بعداً بدهد.

### Reservation اصلی

نیاز به جدول/aggregate اصلی رزرو داریم که حداقل مفاهیم زیر را دارد:

- یک total price کلی
- یک reservation/reference number که خود GDS به صورت خودکار صادر می‌کند
- mobile
- email
- فیلدهای مالی که بعداً مشخص می‌شوند

### Reference authority

- reference رسمی رزرو توسط **Hotel GDS** صادر می‌شود.
- White Label/Main بر اساس reference ما عملیات بعدی را انجام می‌دهند.
- ما نباید به reference داخلی آن‌ها وابسته شویم.

### سناریوی Hotel Replacement

ممکن است مشتری در upstream هتل A با اتاق‌های AA/AB/AC را درخواست کند، ولی تهیه آن ممکن نباشد و تیم فروش/پشتیبانی ما در نهایت هتل B با اتاق‌های BA/BB/BC تهیه کند.

نیاز:

- تمام هتل‌های درگیر/کاندید/جایگزین برای یک reservation قابل ثبت باشند.
- فقط **یک هتل** می‌تواند confirmation نهایی reservation را داشته باشد.

بنابراین reservation نباید صرفاً یک `hotel_id` ساده و غیرقابل تاریخچه داشته باشد.

### Split Provider Purchase

ممکن است یک اقامت 5 شبه از چند provider تأمین شود، مثلاً:

```text
2 nights → SnappTrip
2 nights → GRS / Eghamat24
1 night  → Third Provider
```

پس fulfillment/provider purchase باید قابلیت چند segment داشته باشد.

موضوعی که باید در طراحی نهایی روشن شود:

- split فقط بر اساس شب است؟
- یا می‌تواند بر اساس Room + Night هم باشد؟

مثلاً در یک شب، Room 1 از GRS و Room 2 از SnappTrip.

### Online / Offline / Manual

Hotel fulfillment/purchase حتماً باید هر دو حالت را پشتیبانی کند:

- Online
- Offline / Manual

### Online failure → Manual fallback

سناریوی مهم:

- خرید آنلاین شروع شده است.
- عملیات سمت ما تا نقطه‌ای جلو رفته است.
- provider مثل SnappTrip نمی‌تواند خرید/issue را کامل کند.
- پشتیبان باید بتواند:
  - همان هتل را دستی ثبت/تهیه کند، یا
  - هتل جایگزین ثبت کند،
  - و fulfillment دستی را به همان reservation متصل کند.

بنابراین شروع آنلاین نباید reservation را در flow غیرقابل تغییر قفل کند.

### تفکیک مفهومی پیشنهادی برای ادامه طراحی

هنوز schema نهایی نشده، ولی باید سه مفهوم از هم جدا بمانند:

```text
GDS Reservation
    ↓
Candidate / Fulfillment Hotel(s)
    ↓
Provider Purchase Segment(s)
```

Reservation = قرارداد/شناسه اصلی ما با سیستم بالادستی.

Hotel candidates/final hotel = هتل‌هایی که در جریان fulfillment بررسی/انتخاب شده‌اند و فقط یکی final confirmed می‌شود.

Provider purchase segments = خریدهای واقعی از supplierها که می‌توانند چندتایی، online یا manual و بر اساس شب/اتاق تقسیم شوند.

---

## 14) چیزهایی که برای Reservation هنوز باید از کاربر گرفته شود

قبل از migration/schema نهایی Reservation باید منتظر این اطلاعات بمانیم:

- statusهای دقیق reservation
- statusهای purchase/fulfillment/provider segment
- تمام فیلدهای اصلی reservation
- فیلدهای مالی
- مفهوم و زمان ثبت payment
- قوانین issue/voucher
- اطلاعات passenger/guest که باید داخل GDS ذخیره شود
- edit rules
- cancel/refund rules
- manual operator data
- provider confirmation/reference fields
- اینکه split fulfillment دقیقاً در سطح Night، Room یا Room+Night مجاز است
- constraint دقیق «یک final hotel»
- تاریخچه تغییر هتل/تأمین‌کننده و audit مورد نیاز

تا این موارد مشخص نشده‌اند نباید schema نهایی رزرو را با حدس پیاده‌سازی کرد.

---

## 15) موارد شناخته‌شده‌ای که بعداً باید بررسی شوند

این‌ها checkpoint هستند، نه مجوز برای تغییر خودکار:

- GRS availability global rate limiter
- room type fetch dispatch rate limiting across chunks
- scheduler command mismatch برای hotel data fetch
- GRS reservation endpoint/response contract با مستند provider در چند method نیاز به بازبینی دارد
- public/debug `test-hotel` route/controller باید در زمان مناسب تعیین تکلیف شود
- بعضی Swagger docs قدیمی response/request schemaهای auto-generated اشتباه دارند
- Swagger generator برای nested Laravel validation نیاز به اصلاح ساختاری دارد
- multi-provider pricing/availability باید قبل از production behavior نهایی شود؛ چون `RoomCalendar` provider-aware است

---

## 16) وضعیت کلی کارهای انجام‌شده

در نسخه فعلی پروژه، بخش‌های مهم زیر وجود دارند یا اخیراً اضافه شده‌اند:

- provider adapters و GRS integration
- accommodation/provider mapping
- room types
- room type names
- rate plans
- facilities
- rules/rule categories
- hotel child policy
- room calendars
- provider room/rate-plan mappings
- hotel data synchronization service
- GRS bulk hotel fetch flow
- room-type/provider-data fetch jobs
- availability synchronization jobs
- Front accommodation/availability APIs
- Swagger/OpenAPI docs پایه
- Service/Repository structure برای domainهای اصلی از جمله RoomCalendar

---

## 17) روش نگهداری این فایل

بعد از هر تصمیم مهم پروژه، این فایل باید بروزرسانی شود، مخصوصاً وقتی یکی از این موارد تغییر می‌کند:

- architecture decision
- API contract
- database schema decision
- provider behavior
- reservation state machine
- finance/payment behavior داخل Hotel GDS
- completed milestone
- new known issue
- current next step

هنگام بروزرسانی:

1. تصمیم قطعی را از ایده/پیشنهاد جدا کن.
2. چیزی که هنوز مشخص نیست با `Pending Decision` مشخص شود.
3. secret یا credential نوشته نشود.
4. اگر یک تصمیم قدیمی با تصمیم جدید جایگزین شد، متن قدیمی اصلاح شود تا دو source of truth متناقض باقی نماند.
5. «کار انجام‌شده» فقط وقتی completed نوشته شود که در کد/تست یا توسط کاربر تأیید شده باشد.

---

## 18) Current Next Step

تمرکز بعدی مکالمه:

1. ادامه طراحی Reservation بعد از دریافت statusها، fieldها و قوانین مالی/عملیاتی از کاربر.
2. نهایی کردن مدل fulfillment با قابلیت:
   - hotel replacement
   - one final confirmed hotel
   - multi-provider purchase
   - online/offline/manual
   - online failure → manual fallback
3. مشخص کردن granularity خرید چندتأمین‌کننده (Night vs Room+Night).

تا قبل از دریافت اطلاعات بعدی Reservation، طراحی migration نهایی نباید حدسی انجام شود.
