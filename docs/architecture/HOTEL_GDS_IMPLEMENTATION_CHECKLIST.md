# چک‌لیست وضعیت پیاده‌سازی Hotel GDS

آخرین بازبینی: 2026-10-17  
Branch: `snapptrip`

این فایل یک چک‌لیست زنده است. هر تغییر معماری یا تکمیل feature باید وضعیت این فایل را هم به‌روزرسانی کند.

## معنی وضعیت‌ها

| علامت | معنی |
|---|---|
| ✅ | پیاده‌سازی شده و برای این سطح از کار تأیید قابل اتکا داریم؛ در بعضی بخش‌ها live verification هم انجام شده |
| ☑️ | پیاده‌سازی در کد وجود دارد، اما هنوز production/E2E verification کامل نشده |
| 🟡 | بخشی انجام شده یا یک مشکل/ابهام شناخته‌شده باقی مانده |
| ⬜ | هنوز پیاده‌سازی نشده |
| ⚪ | نیازمند تصمیم معماری/محصول قبل از پیاده‌سازی |

> نکته: ✅ به معنی «برای همیشه تمام‌شده» نیست. regression test، monitoring و تغییر contract Provider می‌تواند دوباره کار ایجاد کند.

---

# 1. معماری Provider V2

| وضعیت | مورد | توضیح |
|---|---|---|
| ✅ | ساختار `App\Modules\HotelProviders\V2` | `Grs`, `SnappTrip`, `Shared` وجود دارند |
| ✅ | `HotelProviderRegistry` | extension point عمومی Providerها |
| ✅ | Adapter registration | GRS از module adapter استفاده می‌کند |
| ✅ | Price refresh handler registration | GRS و SnappTrip مستقل ثبت شده‌اند |
| ✅ | Online purchase handler registration | SnappTrip ثبت شده |
| ✅ | Online purchase preflight registration | SnappTrip ثبت شده |
| ✅ | حذف GRS legacy queue aliases | aliasهای موقت قدیمی حذف شدند |
| ✅ | حذف مسیر قدیمی `fetch:grs-hotel` | command/job قدیمی حذف شد |
| ✅ | حذف مسیر تستی قدیمی all-provider availability | `TestJobCommand` و jobهای وابسته حذف شدند |
| 🟡 | audit کامل باقی‌مانده legacy Provider code | Providerهای قدیمی مثل IHO/Parto و adapterهای legacy هنوز باید جداگانه audit شوند |
| ⬜ | generic cancellation handler registry | هنوز در `HotelProviderRegistry` وجود ندارد |
| ⬜ | template رسمی برای Provider جدید | بهتر است test contract مشترک ساخته شود |

---

# 2. Provider lifecycle و outbound guard

| وضعیت | مورد | توضیح |
|---|---|---|
| ✅ | `ProviderOutboundGuard` | outbound بر اساس `is_active` کنترل می‌شود |
| ✅ | `is_active` به‌عنوان integration switch | rule فعلی Core |
| ✅ | `is_online` به‌عنوان procurement mode | در Shared guard درست رعایت شده |
| ✅ | Provider inactive در purchase به manual/offline می‌رود | `PurchaseResolver` |
| ✅ | Provider active ولی `is_online=false` به manual/offline می‌رود | procurement فقط |
| ✅ | `purchase.online_enabled=false` به manual/offline می‌رود | safe default |
| 🟡 | GRS catalog هنوز `is_online` را شرط می‌گیرد | **نیازمند اصلاح** در `SyncGrsHotelCatalogJob` |
| ✅ | GRS details فقط `is_active` را بررسی می‌کند | مطابق rule فعلی |
| ☑️ | regression test همه outbound pathها برای عدم استفاده نادرست از `is_online` | audit کامل هنوز انجام نشده |

---

# 3. Shared hotel-centric price refresh

| وضعیت | مورد | توضیح |
|---|---|---|
| ✅ | Shared SSP source schedule | `hotel_price_refresh_schedules` |
| ✅ | `gds_id` = local `accommodations.id` | قرارداد شناسه تثبیت شده |
| ✅ | generic dispatcher | `hotel:provider-refresh-dispatch` |
| ✅ | اجرای هر دقیقه | در Scheduler فعلی |
| ✅ | `onOneServer()` | جلوگیری از چند dispatcher همزمان |
| ✅ | `withoutOverlapping()` | جلوگیری از overlap dispatcher |
| ✅ | load کردن Provider handler از Registry | بدون chain بزرگ provider-code |
| ✅ | fan-out per hotel × provider | state مستقل هر Provider |
| ✅ | quota مستقل Provider | handler-specific capacity |
| ✅ | Shared schedule فقط بعد از terminal شدن stateها جلو می‌رود | `ProviderRefreshCoordinator` |
| ✅ | cycle key | جلوگیری از mutation cycle جدید توسط job قدیمی |
| ✅ | cycle superseded handling | outcome مستقل |
| ✅ | duplicate map collapse | انتخاب deterministic map |
| ☑️ | stuck cycle diagnostic | status command وجود دارد؛ recovery/alert خودکار هنوز کامل نیست |
| ⬜ | dashboard cycle latency | monitoring آینده |

---

# 4. `hotel_provider_refresh_states`

| وضعیت | مورد |
|---|---|
| ✅ | state برای `hotel × provider` |
| ✅ | `pending` |
| ✅ | `queued` |
| ✅ | `processing` |
| ✅ | `retry` |
| ✅ | `done` |
| ✅ | `attempted` |
| ✅ | `attempts` counter |
| ✅ | `next_attempt_at` |
| ✅ | `queued_at` / `started_at` / `last_attempt_at` |
| ✅ | `completed_at` |
| ✅ | lease/claim semantics |
| ✅ | reset در cycle جدید |
| ✅ | retry واقعی GRS روی سرور بررسی شد |
| ✅ | terminal `provider_empty` روی سرور بررسی شد |
| ☑️ | documentation کامل تمام status codeهای عددی | این سند concept را پوشش می‌دهد ولی enum/state reference جدا می‌تواند بهتر شود |
| 🟡 | auto alert برای state stuck | هنوز کامل نیست |

---

# 5. Accommodation / Room / RatePlan mapping

## Accommodation map

| وضعیت | مورد |
|---|---|
| ✅ | `accommodation_provider_maps` |
| ✅ | `provider_id` |
| ✅ | `accommodation_id` |
| ✅ | `provider_property_id` |
| ✅ | `is_disabled` |
| ✅ | skip outbound در صورت نبود map |
| ✅ | skip outbound در صورت disabled map |
| ✅ | skip outbound در صورت property id خالی |
| ✅ | نمونه واقعی GRS روی سرور بررسی شد |
| ✅ | duplicate historical maps در scheduler collapse می‌شوند |
| ☑️ | repair tooling برای missing GRS maps | service/job وجود دارد |
| 🟡 | audit عمومی missing maps برای تمام Providerها | نیازمند ابزار generic بهتر |

## Room map

| وضعیت | مورد |
|---|---|
| ✅ | `room_type_provider_maps` |
| ✅ | local `room_type_id` ↔ provider room ID |
| ✅ | scope به accommodation/provider |
| ✅ | GRS mapping واقعی روی Hotel `1813` بررسی شد |
| ☑️ | SnappTrip room mapping implementation | در module وجود دارد، live verification کامل نیست |
| 🟡 | orphan/stale mapping audit | ابزار عمومی کامل نشده |

## Rate plan map

| وضعیت | مورد |
|---|---|
| ✅ | `rate_plan_provider_maps` |
| ✅ | GRS provider rate-plan ID |
| ✅ | GRS mapping واقعی روی Hotel `1813` بررسی شد |
| ☑️ | SnappTrip stable room-scoped mapping key | implementation وجود دارد |
| 🟡 | stale rate-plan map audit | نیازمند تکمیل |

---

# 6. GRS Module — ساختار و Static Data

| وضعیت | مورد | توضیح |
|---|---|---|
| ✅ | `GrsServiceProvider` | Registry ownership داخل module |
| ✅ | `GrsAdapter` | module-owned adapter |
| ✅ | Hotel catalog job | `SyncGrsHotelCatalogJob` |
| ✅ | Hotel details job | `SyncGrsHotelDetailsJob` |
| ✅ | facilities persistence | موجود |
| ✅ | room persistence | موجود |
| ✅ | rate plan persistence | موجود |
| ✅ | child policy/details persistence | موجود |
| ✅ | accommodation map repair | `GrsAccommodationMapRepairService` |
| ✅ | queue `grs-hotels` | Horizon listens |
| ✅ | queue `grs-details` | supervisor مستقل |
| 🟡 | حذف شرط `is_online` از catalog | TODO مهم معماری |
| ☑️ | full static-data production regression بعد از cleanup | بهتر است یک بار بعد از deploy cleanup دوباره verify شود |

---

# 7. GRS Module — نرخ و ظرفیت

| وضعیت | مورد |
|---|---|
| ✅ | `GrsScheduledPriceRefresh` |
| ✅ | queue `grs-prices` |
| ✅ | generic scheduler integration |
| ✅ | provider-specific quota |
| ✅ | map validation قبل از request |
| ✅ | disabled map handling |
| ✅ | provider property ID به‌جای local GDS ID |
| ✅ | availability API call |
| ✅ | room/rate mapping validation |
| ✅ | persistence در `room_calendars` |
| ✅ | retry |
| ✅ | rate-limit handling |
| ✅ | `provider_empty` terminal handling |
| ✅ | Shared cycle coordination |
| ✅ | اجرای واقعی production/server تأیید شد |
| ✅ | `room_calendars` تازه در حال update مشاهده شد |
| ✅ | throughput و queue progression بررسی شد |
| ✅ | retry -> success واقعی مشاهده شد |
| ✅ | retry -> provider_empty واقعی مشاهده شد |
| 🟡 | long-run monitoring چند ساعته/روزانه | برای confidence عملیاتی بلندمدت مفید است |
| 🟡 | metric اختصاصی latency/error rate | monitoring آینده |

## Snapshot بررسی 2026-10-17

- 1861 map برای GRS؛
- 1828 usable map؛
- 593025 calendar row برای `provider_id=1`؛
- 1735 Hotel دارای calendar row؛
- latest writes در زمان بررسی در حال جلو رفتن بودند؛
- Hotel `1813` / Provider Property `3503` / Room mappings و RatePlan mapping بررسی شدند.

این اعداد runtime snapshot هستند و دائماً تغییر می‌کنند.

---

# 8. SnappTrip Module — ساختار عمومی

| وضعیت | مورد |
|---|---|
| ☑️ | module مستقل V2 |
| ☑️ | `SnappTripServiceProvider` |
| ☑️ | API key authentication |
| ☑️ | `ProviderOutboundGuard` integration |
| ☑️ | provider-specific rate limit |
| ☑️ | Toman → IRR boundary normalization |
| ☑️ | queues اختصاصی |
| ☑️ | health command |
| ☑️ | balance sync |
| 🟡 | full live integration regression | به دلیل IP-sensitive API باید روی سرور کامل شود |

---

# 9. SnappTrip Static Data

| وضعیت | مورد |
|---|---|
| ☑️ | catalog sync |
| ☑️ | details sync |
| ☑️ | facilities |
| ☑️ | room types |
| ☑️ | rate plans from board type |
| ☑️ | child policy data |
| ☑️ | provider details persistence |
| ☑️ | daily static scheduler |
| ☑️ | queue `snapptrip-static` |
| 🟡 | production verification mapping coverage | نیازمند بررسی live |
| 🟡 | 404/disabled static behavior live verification | نیازمند تست روی سرور |

---

# 10. SnappTrip نرخ و ظرفیت

| وضعیت | مورد |
|---|---|
| ☑️ | `SnappTripScheduledPriceRefresh` |
| ☑️ | generic shared scheduler integration |
| ☑️ | queue `snapptrip-prices` |
| ☑️ | 90-day default requested horizon |
| ☑️ | domestic + foreign request separation |
| ☑️ | request count based capacity |
| ☑️ | calendar window splitting |
| ☑️ | learned per-hotel smaller window behavior |
| ☑️ | rate limit -> retry |
| ☑️ | missing mapping targeted recovery |
| ☑️ | provider empty terminal handling |
| ☑️ | 404 can disable map through refresh flow |
| 🟡 | live 30-day verification |
| 🟡 | live حدود 40-day provider behavior verification |
| 🟡 | live 90-day adaptive/window verification |
| 🟡 | live `room_calendars` persistence verification |
| 🟡 | live retry verification |
| 🟡 | بررسی دقیق provider range error text با API واقعی |

---

# 11. `room_calendars`

| وضعیت | مورد |
|---|---|
| ✅ | `provider_id` dimension |
| ✅ | `accommodation_id` |
| ✅ | `room_type_id` |
| ✅ | `rate_plan_id` |
| ✅ | provider property/room/rate IDs |
| ✅ | `day` |
| ✅ | `rack_rate` |
| ✅ | `daily_rate` |
| ✅ | `grs_rate` |
| ☑️ | `child_daily_rate` |
| ☑️ | `infant_daily_rate` |
| ✅ | `inventory` |
| ✅ | `closed` |
| ✅ | GRS runtime persistence verified |
| 🟡 | SnappTrip runtime persistence verified | هنوز کامل نشده |
| 🟡 | explicit stale-offer semantics documentation | pruning وجود دارد ولی consumer semantics باید کامل مستند شود |

---

# 12. پاسخ Availability/Search محلی

| وضعیت | مورد |
|---|---|
| ✅ | معماری بر local persisted availability متکی است |
| ✅ | Provider refresh از request/response محلی جداست |
| ✅ | خاموشی Provider نباید Core endpoint را خاموش کند | rule معماری تثبیت شده |
| 🟡 | regression test: هیچ Provider فعالی وجود ندارد |
| 🟡 | regression test: یک Provider disabled، دیگری active |
| 🟡 | regression test: همه Provider maps disabled |
| 🟡 | regression test: mixed multi-provider availability |
| 🟡 | documentation دقیق انتخاب cheapest valid full stay | رفتار معماری تعریف شده، تست جامع ارزشمند است |

---

# 13. Reservation Create

| وضعیت | مورد |
|---|---|
| ☑️ | ساخت Reservation ticket قبل از provider validation |
| ☑️ | snapshot انتخاب room calendar |
| ☑️ | multi-room foundation |
| ☑️ | check-in/check-out full-stay validation |
| ☑️ | selected Provider preservation |
| ☑️ | price increase handling |
| ☑️ | lower validated provider price به‌عنوان internal margin |
| ☑️ | provider failure بدون حذف ticket |
| ☑️ | unavailable/missing calendar outcome |
| ☑️ | nationality / identity validation |
| ☑️ | birthday-based age calculation |
| ☑️ | child policy based on calculated age |
| 🟡 | full regression multi-room + repeated room inventory |
| 🟡 | edge cases تولد در مرز سنی child/infant |
| 🟡 | full production/E2E reservation regression بعد از Provider cleanup |

---

# 14. Purchase / Procurement

| وضعیت | مورد |
|---|---|
| ✅ | `PurchaseResolver` |
| ✅ | inactive Provider -> offline/manual |
| ✅ | `is_online=false` -> offline/manual |
| ✅ | `purchase.online_enabled=false` -> offline/manual |
| ✅ | manual rules |
| ✅ | handler-missing fallback to manual |
| ✅ | idempotent purchase-plan reuse |
| ✅ | provider grouping delegated to handler |
| ✅ | Provider I/O خارج از request transaction |
| ✅ | preflight job قبل از provider booking |
| ☑️ | online provider balance preflight |
| ☑️ | admin recovery services |
| 🟡 | end-to-end production purchase verification برای SnappTrip | safe default هنوز false است |

---

# 15. SnappTrip Online Purchase

| وضعیت | مورد |
|---|---|
| ☑️ | create booking |
| ☑️ | optional lock |
| ☑️ | confirm |
| ☑️ | mandatory status inquiry |
| ☑️ | async execution on `snapptrip-operations` |
| ☑️ | unique purchase job |
| ☑️ | provider booking code persistence |
| ☑️ | safe retry after booking code exists |
| ☑️ | ambiguous create failure -> under review |
| ☑️ | price increase safety |
| ☑️ | final price difference -> payment required where applicable |
| ☑️ | retry recovery action |
| ☑️ | convert selected purchase to manual |
| ☑️ | mixed online/manual protection |
| 🟡 | live create/confirm/status booking test |
| 🟡 | تصمیم نهایی برای `lock_enabled` بر اساس contract واقعی |
| 🟡 | فعال‌سازی `purchase.online_enabled` در production | فقط بعد از verification |

---

# 16. Cancellation / Refund

## Provider-specific capability

| وضعیت | مورد |
|---|---|
| ☑️ | `provider_cancellations` model/table |
| ☑️ | SnappTrip create cancellation |
| ☑️ | SnappTrip inquiry |
| ☑️ | SnappTrip accept |
| ☑️ | SnappTrip reject |
| ☑️ | SnappTrip pending cancellation polling |
| ☑️ | GRS Adapter دارای `cancel()` |

## Generic Reservation Cancellation — کار باقی‌مانده

| وضعیت | مورد |
|---|---|
| ⬜ | generic cancellation contract |
| ⬜ | cancellation handler registry |
| ⬜ | Reservation cancellation API |
| ⬜ | purchase-level cancellation orchestration |
| ⬜ | partial cancellation |
| ⬜ | full cancellation |
| ⬜ | generic inquiry/calculation step |
| ⬜ | generic accept/reject workflow |
| ⬜ | refund amount normalization |
| ⬜ | update `ReservationPurchase` refund status |
| ⬜ | aggregate Reservation refund status |
| ⬜ | mapping partial refund -> `PURCHASE_REFUND` |
| ⬜ | mapping full refund -> `REFUNDED` |
| ⬜ | cancellation idempotency |
| ⬜ | cancellation retry policy |
| ⬜ | cancellation audit trail / actor tracking |
| ⬜ | provider-neutral tests |
| ⬜ | E2E tests GRS/SnappTrip |

---

# 17. Queue / Horizon

| وضعیت | مورد |
|---|---|
| ✅ | Redis queue |
| ✅ | Horizon running در server verification |
| ✅ | `default` |
| ✅ | `grs-hotels` |
| ✅ | `grs-details` |
| ✅ | `grs-prices` |
| ✅ | `snapptrip-static` |
| ✅ | `snapptrip-prices` |
| ✅ | `snapptrip-operations` |
| ✅ | supervisor جدا برای GRS details |
| ✅ | supervisor جدا برای SnappTrip |
| ✅ | async queue guard برای generic refresh command |
| 🟡 | queue starvation alerting |
| 🟡 | failed-jobs operational runbook |
| 🟡 | queue wait alert integration |
| 🟡 | worker saturation metrics |

---

# 18. Scheduler

| وضعیت | مورد |
|---|---|
| ✅ | generic provider refresh هر دقیقه |
| ✅ | GRS catalog schedule |
| ✅ | GRS details schedule |
| ✅ | SnappTrip static schedule |
| ✅ | SnappTrip balance schedule |
| ✅ | SnappTrip pending cancellation polling |
| ✅ | model pruning |
| 🟡 | missed scheduler run detection |
| 🟡 | scheduler health alert |

---

# 19. Logging / Telegram / Diagnostics

| وضعیت | مورد |
|---|---|
| ✅ | کاهش logهای اطلاع‌رسانی بی‌ارزش در مسیرهای اصلاح‌شده |
| ✅ | provider empty به‌عنوان error داخلی گزارش نمی‌شود |
| ✅ | خطاهای داخلی مهم قابلیت Telegram دارند |
| ✅ | ProviderRequest pruning در scheduler |
| 🟡 | audit کامل همه `Log::*`های legacy |
| 🟡 | correlation ID بین refresh state / job / provider request |
| 🟡 | latency metric per Provider |
| 🟡 | HTTP error-rate metric per Provider |
| 🟡 | stuck-state alert |
| ⬜ | dashboard جامع operations |

---

# 20. Tests

## GRS

| وضعیت | مورد |
|---|---|
| ✅ | module boundary tests |
| ✅ | scheduler tests |
| ✅ | shared refresh tests |
| ✅ | availability/rate-limit tests |
| ✅ | mapping-related tests |
| ✅ | production runtime verification نرخ/ظرفیت |
| 🟡 | full test suite بعد از آخرین legacy cleanup روی deployment target | باید بعد از pull اجرا و ثبت شود |

## SnappTrip

| وضعیت | مورد |
|---|---|
| ☑️ | module/unit tests |
| ☑️ | price refresh tests |
| ☑️ | purchase flow tests |
| ☑️ | balance/preflight tests |
| ☑️ | cancellation service tests در سطح codebase |
| 🟡 | live API integration tests روی server IP |
| 🟡 | 30/40/90-day calendar tests |
| 🟡 | live booking tests |
| 🟡 | live cancellation tests |

## Core

| وضعیت | مورد |
|---|---|
| ☑️ | Reservation create tests |
| ☑️ | Purchase resolver tests |
| ☑️ | single-provider purchase protection |
| ☑️ | preflight dispatch tests |
| 🟡 | all-providers-inactive local response regression |
| 🟡 | mixed-provider complete-stay selection regression |
| ⬜ | generic cancellation tests |

---

# 21. Documentation

| وضعیت | مورد |
|---|---|
| ✅ | architecture map | `docs/architecture/HOTEL_GDS_ARCHITECTURE.md` |
| ✅ | implementation checklist | همین فایل |
| ✅ | historical GRS checkpoints |
| ✅ | historical SnappTrip module checkpoint |
| ✅ | SnappTrip online purchase checkpoint |
| ✅ | Telegram alert document |
| 🟡 | نشانه‌گذاری checkpointهای stale | بهتر است header واضح «historical» در فایل‌های قدیمی اضافه شود |
| 🟡 | API endpoint catalogue برای Hotel GDS | می‌تواند سند مستقل باشد |
| 🟡 | operations runbook | restart/retry/failure handling |
| 🟡 | Provider onboarding template | سند مستقل برای Provider سوم |

---

# 22. اولویت کارهای بعدی

## P0 — قبل از اینکه معماری را «پایدار» حساب کنیم

- [ ] اصلاح `SyncGrsHotelCatalogJob` تا `is_online` catalog را block نکند.
- [ ] اجرای test suite مرتبط بعد از آخرین legacy cleanup روی سرور/deployment target.
- [ ] live verification نرخ SnappTrip برای 30 روز.
- [ ] live verification محدودیت حدود 40 روز Provider و ثبت response واقعی.
- [ ] live verification درخواست 90 روز و adaptive window flow.
- [ ] بررسی persistence واقعی SnappTrip در `room_calendars` و mappingها.

## P1 — قابلیت اصلی باقی‌مانده

- [ ] طراحی generic cancellation contract.
- [ ] اضافه کردن cancellation handler registry.
- [ ] generic Reservation/Purchase cancellation orchestration.
- [ ] partial/full refund state calculation.
- [ ] اتصال SnappTrip cancellation implementation موجود به Core generic flow.
- [ ] اتصال GRS cancellation capability به همان contract.
- [ ] API و testهای cancellation/refund.

## P2 — سخت‌سازی عملیاتی

- [ ] stuck refresh-state detection/alert.
- [ ] Provider latency/error metrics.
- [ ] queue starvation/saturation monitoring.
- [ ] audit نهایی loggingهای legacy.
- [ ] audit Provider-specific logic خارج از V2 modules.
- [ ] operations runbook.
- [ ] Provider onboarding contract/test template.

---

# 23. تعریف Done برای Provider جدید

Provider جدید فقط با «API جواب می‌دهد» Done نیست. حداقل باید موارد زیر تکمیل شوند:

- [ ] Provider module مستقل V2
- [ ] outbound guard
- [ ] authentication/config
- [ ] static mapping
- [ ] accommodation mapping
- [ ] room mapping
- [ ] rate-plan mapping یا strategy مستند جایگزین
- [ ] price refresh handler
- [ ] queue + Horizon config
- [ ] rate limit
- [ ] timeout/retry policy
- [ ] 404/empty behavior
- [ ] currency normalization
- [ ] persistence verification
- [ ] shared-cycle coordination
- [ ] unit/feature tests
- [ ] live server verification
- [ ] online purchase handler یا صریحاً manual-only
- [ ] cancellation capability یا صریحاً unsupported
- [ ] update این architecture/checklist

---

# 24. آخرین نتیجه کلی

وضعیت پروژه در زمان این بازبینی:

- **GRS rate/availability:** از نظر معماری ماژولار و اجرای واقعی، در وضعیت خوب و تأییدشده است.
- **GRS static:** عملیاتی است، ولی شرط `is_online` در catalog یک بدهی معماری مشخص است.
- **SnappTrip module:** بخش عمده integration، price refresh، online purchase و provider cancellation در کد وجود دارد؛ اما بخش مهم verification واقعی به دلیل IP-sensitive بودن Provider باید روی سرور ادامه پیدا کند.
- **Reservation/Purchase Core:** foundation و online/manual procurement flow وجود دارد.
- **Generic Cancellation/Refund:** بزرگ‌ترین feature معماری باقی‌مانده است.
- **Monitoring/Operations:** کار می‌کند ولی برای مقیاس و پشتیبانی بلندمدت به metric، alert و runbook دقیق‌تر نیاز دارد.
