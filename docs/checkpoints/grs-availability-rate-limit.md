# GRS Availability Rate Limit Checkpoint

Date: 2026-09-17

- Current online provider focus is GRS / Aghamat24 only.
- GRS availability request limits are managed from `providers.config.availability_rate_limit`.
- Supported shape:

```json
{
  "availability_rate_limit": {
    "max_requests": 10,
    "window_minutes": 1
  }
}
```

- `max_requests` is capped at 10.
- `window_minutes` is configurable and at least 1.
- Examples include 10 requests per 1 minute, 1 request per 1 minute, and 1 request per 20 minutes.
- There is no separate test mode; testing uses the same provider configuration.
- Admin provider config updates merge recursively so changing the rate-limit section does not replace unrelated provider config.
- GRS availability sync uses a shared/global limiter key instead of one limiter per hotel/property.
- Generic GRS availability entry points delegate to the canonical `SyncGrsAvailabilityJob` flow so they do not bypass the limiter.
- Existing availability mapping completion/fallback behavior remains intact. Missing room/rate-plan mappings must not cause valid provider response data to be intentionally discarded merely to avoid an additional provider call.
- Routine application logging defaults to stderr rather than a dedicated hotel-sync file under `storage/logs`.
- `AccommodationTypeSeeder` restores the SEO-sensitive accommodation type IDs from the supplied SQL dump.
- The supplied accommodation rows cannot yet be safely wired into `DatabaseSeeder`: their `city_id` values depend on exact city IDs, while the existing project city JSON uses a different ID mapping. Matching country/state/city seed data is required before enabling automatic accommodation restoration.
