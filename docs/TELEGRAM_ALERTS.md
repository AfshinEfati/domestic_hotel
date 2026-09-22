# Domestic Hotel — Telegram operational alerts

## What is wired

- Laravel HTTP `ResponseReceived` and `ConnectionFailed` listeners inspect **tagged supplier requests** only, without forwarding the request/response body, credentials or passenger data.
- `BaseAdapter::client()` marks GRS, Parto, IHO and SnappTrip traffic using private Laravel request attributes (not HTTP headers). The independent GRS catalog/details clients and Parto login have the same tag.
- Supplier HTTP >=400, connection failures, recognisable top-level API business errors, and malformed small JSON payloads produce alerts. Sold-out inventory and ordinary validation failures do not.
- Laravel exception reporting feeds unexpected application exceptions into the same alert manager. Supplier HTTP exceptions and Telegram delivery failures are excluded to avoid duplicate/recursive alerts.
- A five-minute shared-cache fingerprint suppresses duplicate alerts. Messages dispatch after database commit to the `default` queue; the transport job uses bounded timeouts and three attempts. A failed Telegram delivery never changes the supplier's result.

## Server configuration

Store the actual values in the **untracked** `.env` on the application server; never commit credentials or put them in a class constant:

```dotenv
TELEGRAM_ALERT_TOKEN=your_rotated_token
TELEGRAM_ALERT_CHAT_ID=your_chat_id
TELEGRAM_ALERT_PARSE_MODE_FIELD=parse_mode
```

The bot token posted into a conversation has been exposed and should be revoked/replaced through BotFather. Use the chat ID supplied separately by the operator. Keep the production queue asynchronous (`database` or `redis`); `sync` is intentionally disabled for alerts to avoid making an API request wait for Telegram. Ensure a worker consumes the `default` queue.

After deployment:

```bash
php artisan config:clear
php artisan horizon:terminate
```

Or restart the actual queue worker if Horizon is not used. This change introduces **no database migration, no API endpoint and no new automated tests**.

## Message layout and HTML limitation

`TelegramAlertFormatter` creates Persian labels, isolated left-to-right values, escaped `<b>` headings and an optional `<pre><code>` technical section. The sending job posts to the existing company endpoint at `https://ehotelo.com/api/telegram/send-message` with `token`, `chatId`, `message`, and (by default) `parse_mode: HTML`; `tags` is omitted.

**The published four-field example does not prove that the company proxy accepts and forwards `parse_mode` to the Telegram Bot API.** Confirm this with its owner or a safe manual send. If the proxy rejects unknown fields, set `TELEGRAM_ALERT_PARSE_MODE_FIELD=` until it is extended. Without forwarding the parse mode, Telegram cannot be guaranteed to render bold or fenced code as actual rich text. Do not claim this integration has been end-to-end tested before a live send.

Do not insert raw exception messages, SQL statements with bindings, request/response bodies, URLs with query strings, API headers, customer identifiers or personal data into the alerts. `internalFailure()` reports exception **class and source location**, not exception text. `custom()` is only for explicitly sanitized application messages; its optional `technicalSnippet` may include harmless code for the HTML code block.

## Scope and limitations

The central HTTP observer catches tagged calls even when an adapter handles its own HTTP error. Errors detected only after complex response parsing, or swallowed internally without `report()`, cannot be inferred from generic HTTP events; use `TelegramAlertService::custom()` explicitly for those exceptional cases. The system does not forward every `Log::error()` or alert on non-critical normal business outcomes.

Provider calls using a new independent HTTP client must attach `withAttributes(['domestic_provider' => ['code' => $providerCode]])`; the Telegram proxy request must **not** have this attribute. Do not use provider URL/header matching, as it can expose credentials and misclassify traffic.
