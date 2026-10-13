# Courier app — requirements and backend plan

Everything needed to build the courier mobile app and the backend it talks to.

Sources:
- the client's prototype (`emojisushi_bonus_delivery_dashboard_v6.html`, screen «Приложение курьера» and the admin screens it feeds);
- the owner's working test page («Emoji Courier — приложение курьера»);
- this codebase;
- Poster's API docs;
- read-only calls against the Poster test account.

Every fact carries a tag:

- **[verified]**: checked in the code, against the Poster test account, or confirmed by the owner from the live account.
- **[docs]**: from Poster's API documentation, not yet called from our backend.
- **[prototype]** / **[owner page]**: a requirement from the client's mock-up or the owner's test page.
- **[decided]**: a product decision the owner has made.
- **[unverified]**: assumed; confirm before relying on it.

---

## 1. Decisions and open questions

### Decided
| # | Decision |
|---|----------|
| D1 | **Trip state lives in Poster [decided].** «Я поехал» sets the order's `processing_status` to 40, «Доставил» sets it to 50, through `transactions.updateTransaction` (§3.2). We keep no trip tables. |
| D2 | **Receipts are closed at the POS, not by the courier [decided].** Pay counts closed receipts (status 2) by their local close date, which is what the payment page already does. |
| D3 | **Undoing «Доставил» [decided].** Poster refuses 50 → 40 [verified, 2026-10-13: `updateTransaction` answered `error 32 "processing_status is undefined"` for 50 → 40 and 50 → 30, after accepting 30 → 40 → 50]. So:<br>• **10-second undo window**: «Доставил» is sent to Poster only after a 10 s «Отменить» bar (§2.3).<br>• **Return from the archive is local to the app**: the order goes back into the courier's list as «в пути», while Poster keeps it at 50 (§2.5). |
| D4 | **Every request must succeed or be retried; after 3 failures in a row the app shows a connection warning and keeps retrying [decided]** (§7). |
| D5 | **`spot_tablet_id` = the tablet linked to the spot [decided, verified].** The receipt's Poster `spot_id` → our `Spot` (by `poster_id`) → `Spot.tablet.tablet_id`. Both spots on the test account have their tablet linked. |
| D6 | **Couriers aren't tied to a spot [decided].** The spot comes from each order. The point to return to is the spot of the orders in the trip. |
| D7 | **Transfer card: keep the owner's page behaviour for now [decided].** The UI stays (button, dialog, copy). Where the number is stored on the backend is deferred; until then the app shows «Карта для этой точки не настроена». |
| D8 | **Admins see the courier's live state [decided, replaces "app only"].** Orders at 40 are blue on the admin map. The app reports «Везу сейчас» and «Возвращается / Я на точке» to `POST /api/courier/state`, through its retry queue (latest report wins), stored in `courier_states` (migration 1.2.21). The admin map rings the order a courier is driving to right now; the Курьеры page shows each courier's status (`CourierLiveStatus`): Везёт заказ #… / В рейсе / Возвращается / На точке · ждёт выезда / На точке / Не в приложении, plus when they last used the app. Being on a trip still comes from Poster. |
| D9 | **A web app, remade from the owner's page [decided]**, hosted on its own at `courier.emojisushi.com.ua` (static files in `courier-app/` at the repo root) and talking to `api.emojisushi.com.ua/api/courier/*` (§6, §11). |
| D10 | **Couriers can't delete or edit their extra trips; only admins can [decided]**, on the payment page (which already does this). |

---

## 2. What the app does

### 2.1 Login [prototype]
- Login and password from the courier card the admin creates (Доставка → Курьеры).
- The session lasts until logout or until the admin deactivates the courier (90 days on the owner's page **[owner page]**).
- After login the courier sees **only their own** orders.

### 2.2 Orders tab [prototype + owner page]
- Header: tab name, phase, number of active orders, number delivered today.
- A one-line status hint (the owner's page uses a compact strip):
  - «Выберите готовые заказы для рейса»;
  - «Выберите заказ: «Везу сейчас» или точку на карте»;
  - «Возвращайтесь на точку» with an «Я на точке» button.
- **List / Map** switch.
- Each order card:
  - receipt number, address, due time («до 13:05»), client, phone, amount;
  - a badge: `ГОТОВИТСЯ`, `ГОТОВ · ЗАБРАТЬ`, `В РЕЙСЕ` or `ВЕЗУ СЕЙЧАС`;
  - **payment** (cash / card / online / …) and the **order comment** **[owner page]**;
  - buttons: ☎ call, Google Maps, Waze.
- «＋ Довоз / Закупка / Магазин» at the bottom.

### 2.3 Trip flow [prototype + decided]
1. **At the point:** the courier ticks ready orders and taps **«🛵 Я поехал · N»**. The app sets each selected order to **40** in Poster, and the admin map turns them blue.
2. **On a trip:** the courier picks the order being delivered now («📍 Везу сейчас», in the list or on the map). That order shows ☎, 🧭 Путь (Google Maps / Waze) and **«✓ Доставил»**.
   - «Доставил» asks for confirmation. The order then leaves the list, and a bar at the bottom shows «#105923 доставлен · Отменить» with a 10-second countdown.
     - **Отменить** within 10 s → the order comes straight back; nothing was sent.
     - After 10 s → the 50 goes into the outgoing queue (§7) and is sent to Poster.
     - Closing the app within the 10 s still sends it: the pending 50 is saved with the queue and goes out on the next start.
3. When no order is left at 40, the app shows «Возвращайтесь на точку».
4. **«✓ Я на точке»** returns the app to waiting for new orders.

How the phase is worked out (nothing stored on our server, D8):
- `on_trip` = the courier has at least one order at 40 in Poster;
- `returning` = no order at 40, and the courier hasn't tapped «Я на точке» since their last «Доставил»;
- `at_point` = otherwise.

### 2.4 Map [prototype + owner page]
- A real map (Leaflet + OpenStreetMap) with a pin per order. Since couriers aren't tied to a spot (D6), the spot pin is the spot of the courier's orders:
  - yellow: ready;
  - grey: preparing;
  - blue: in the trip;
  - black: being delivered now;
  - **dashed outline** when only the street or housing complex was found, not the building **[owner page]**.
- A pin's popup has the address, payment, comment, Google Maps / Waze, and «Добавить в рейс» or «Везу сейчас».
- A note under the map says how many orders couldn't be placed («откройте маршрут из списка»).

### 2.5 Archive tab [prototype + decided]
- A date picker. That day's list holds **receipts** («ДОСТАВЛЕН» / «Закрыт в Poster») and **extra trips** («ДОП. ПОЕЗДКА»), with time, address or details, comment and payment.
- A ☎ call button with the client's phone **[owner page]**.
- **«↩ Вернуть в доставку»** on a delivered receipt, **local to the app (D3)**, as on the owner's page:
  - asks for confirmation: «Вернуть заказ #… в список? В Poster он останется доставленным.»;
  - the order reappears in the Orders tab as «в пути», marked «возвращён вами», and works like any trip order (Везу сейчас, Путь, Звонок);
  - **Poster isn't touched**: it keeps the order at 50, and the admin map keeps showing it as delivered, not blue;
  - pressing «Доставил» on it again only removes the local mark (Poster is already at 50), still with the confirmation and the 10-second bar;
  - the mark is stored on the device (`localStorage`, per courier login). It isn't visible on another phone, and it's dropped once the receipt is closed at the POS or reassigned to someone else.
  - Shown for any of the courier's delivered receipts in the archive that are still open in Poster (`can_return`).

### 2.6 Add an extra trip [prototype]
- Type: **Довоз / Закупка / Магазин** (required). **Details** («Что именно / куда») and **Comment**, both optional.
- **Saved on our server [decided]**, not only in the browser as on the owner's page. Each one counts as one delivery on the payment page's «Довозы / закупки» column.
- **The courier can't delete or edit it afterwards (D10).** Mistakes are fixed by an admin on the payment page.

### 2.7 Transfer card [owner page, D7]
- A «Карта для перевода» button shows the point's 16-digit card number in large digits with «Скопировать номер», so the courier can give it to a client who pays by transfer.
- Kept exactly as on the owner's page. Where the number is stored is deferred; with no number the app shows «Карта для этой точки не настроена».

### 2.8 Profile tab [prototype]
- Done today (including extras), remaining, number of extras, name, login, status, «Выйти».

### 2.9 Admin side [prototype + owner page]
- **Map pins:** yellow while waiting (assigned or not), **blue once the order is at 40** (the courier tapped «Я поехал»). Today's map colours by "courier assigned"; it should colour by `processing_status` instead.
- **No courier board for now (D8).** «Возвращается» has nowhere to be shown. A board can be added later from Poster data alone (orders at 40, receipts remaining, extras today).
- **"View as courier"**: the owner's page has `?admin_courier=<name>`, which opens the app as that courier. If we keep this, the server must check that the caller is a signed-in backend admin, **never** trust the header alone.

---

## 3. Poster

### 3.1 What we read [verified]

| Data | Field | Notes |
|------|-------|-------|
| Courier | `delivery.courier_id` | Poster employee id; `Courier.poster_user_id` maps it to our courier. Survives closing. |
| Receipt status | `status` | 0 = online order not yet accepted, 1 = open, 2 = closed, 3 = removed, 4 = seen on a cancelled order. |
| Delivery status | `processing_status` | 10 open, **30 ready**, **40 in route [docs]**, **50 delivered [docs]**, 60 closed, 70 removed. 20 (preparing) is **[unverified]**: the app treats anything below 30 as preparing. |
| Due time | `delivery.delivery_time` | Local Kyiv time. |
| Address | `delivery.address1/2`, `delivery.comment` («город, район») | `delivery.lat/lng` are often empty, so we geocode ourselves. |
| Money | `sum`, `payed_cash`, `payed_card`, `payed_cert`, `payed_bonus`, `payed_third_party`, … | In kopecks. Payment method also appears in the comment («Спосіб оплати: Готівкою»). |
| Closed receipts by day | `dash.getTransactions` `status=2` | Filed under the **local close date**. |

### 3.2 What we write [docs]

`POST transactions.updateTransaction`, called through the library's generic `PosterApi::makeApiRequest('transactions.updateTransaction', 'post', $params)` (the library has no wrapper for it **[verified]**).

| Param | Value |
|---|---|
| `spot_id` | Poster spot id (`Spot.poster_id`) |
| `spot_tablet_id` | `Spot.tablet.tablet_id`, for the `Spot` whose `poster_id` is the receipt's `spot_id` (D5) |
| `transaction_id` | receipt |
| `processing_status` | 40 (in route) or 50 (delivered) |
| `courier_id` | optional, Poster employee id (lets admins assign couriers from our map later) |

The response is `{"response": {"error_code": 0}}` on success. Anything else is treated as a failure, and the app retries (§7).

### 3.3 Webhooks
- The `transaction` webhook **fires when a courier is assigned [verified, owner]**. The operator feed and push notifications (§5.2) can rely on it.
- Whether it also fires after our own `updateTransaction` is **[unverified]**. Polling covers the gap either way.

---

## 4. What the backend already has [verified]

| Piece | Where | Reuse |
|-------|-------|-------|
| Courier registry + Poster mapping | `models/Courier.php` | Identity of the logged-in courier. |
| Delivery receipts from Poster, Kyiv-time helpers | `classes/delivery/OpenDeliveries.php` | Orders list, due times. |
| Receipts + tasks in one history | `classes/couriers/CourierLedger.php` (`history($courier, $from, $to)`) | Archive tab. **Never run yet.** |
| Tasks with model-level validation, `source = app` | `models/CourierTask.php` | Create-task endpoint. |
| Pay per day, frozen on close | `classes/couriers/CourierPayroll.php` | Profile counts; the "closed day" lock for tasks. |
| Webhook → events (courier assigned, status changed, closed…) | `classes/delivery/DeliveryEventRecorder.php` | Push "new order assigned to you". |
| Geocoding to building/street | `GeocodeService` + `resolvePoint()` in `controllers/DeliveryMap.php` | Map pins; needs moving into a shared service. |
| FCM sender | `restapi/services/FcmService.php` | Push. **Caution:** it deletes invalid tokens from the *customer* `fcm_tokens` table. |
| Customer JWT auth | `reazzon/jwtauth` | **Not reusable** for couriers (§6). |
| Spot → tablet | `Spot.tablet` → `Tablet.tablet_id` | `spot_tablet_id` for §3.2 (D5). |

---

## 5. What the backend is missing

### 5.1 Tables

**`courier_tokens`**: app sessions.

| column | type | note |
|---|---|---|
| id | bigint | |
| courier_id | bigint, index | |
| token_hash | char(64), unique | sha256 of the bearer token; the raw token is returned once at login |
| device_name | string null | |
| last_used_at | timestamp null | |
| expires_at | timestamp null | e.g. 90 days, extended on use |
| timestamps | | |

**`courier_push_tokens`**: separate from the customer `fcm_tokens`.

| column | type |
|---|---|
| id, courier_id (index), fcm_token (unique), platform, last_used_at, timestamps | |

**`courier_tasks.client_uuid`**: a new nullable column with a unique index. The app generates the UUID, so a retried «Добавить» can't create the same extra trip twice (§7).

**`couriers`** change: `password` → hashed. Existing values are plain text: hash them in place, or have the admin reset them all.

No trip, state, or courier→spot tables (D1, D6, D8). The transfer card's storage is deferred (D7).

### 5.2 Code
- **Shared geocoding**: move `resolvePoint` / `streetCoordinates` / `houseOf` out of `DeliveryMap` into `classes/delivery/DeliveryLocations.php`.
- **Navigation address cleanup [owner page]**: strip apartment, entrance, floor, intercom code and old street names in brackets; add «Одеська область, Україна». Send coordinates to Waze/Google only when the building was found.
- **Courier auth middleware**: `Authorization: Bearer <token>` → `Courier`. Rejects inactive couriers and expired tokens.
- **`PosterDeliveryStatus` service**: wraps `transactions.updateTransaction`, checks `error_code`, and logs each change (who, which receipt, from/to).
- **Cached open-receipts read** (§8).
- **`CourierLedger::history()` must also include open receipts already at 50.** Today it reads only closed receipts, so an order delivered but not yet closed at the POS (the one «Вернуть в доставку» is for) would be missing from the archive.
- **Push on assignment**: in `DeliveryEventRecorder`, on `courier_assigned` / `courier_changed`, push to that courier's devices.
- **Admin map**: colour pins by `processing_status` (40 → blue), not by "courier assigned".

---

## 6. Auth

Our own **opaque bearer tokens**, stored hashed, checked by our own middleware.

Why not the existing JWT plugin: its guard and user resolver are tied to RainLab users (customers), and its tokens never expire.

- **Built [verified locally]:** `classes/couriers/CourierTokens.php`, `AuthenticateCourier.php`, `controllers/CourierAuthController.php`.
- **The web app's session is an HttpOnly cookie** (`courier_session`, `Path=/api/courier`, `SameSite=Strict`, `Secure` over HTTPS). Page scripts can't read it, so the token never reaches the page.
  - Every change sent with the cookie must carry `X-Courier-App: 1` and come from our own origin, otherwise 403 (plugin routes have no CSRF middleware).
  - Other clients send `token_mode: "bearer"` at login and get the token in the JSON, to use as `Authorization: Bearer`.
- `POST /api/courier/auth/login` returns `{ courier, expires_at }` (+ `token` in bearer mode). 5 failed tries per login + IP → 60 s pause.
- Passwords checked with `Hash::check` once they're hashed.
- `courier.active = false` → login refused, and existing tokens stop working.
- `POST /api/courier/auth/logout` deletes the token and its push token.
- **It's a web app (D9),** hosted on its own at `https://courier.emojisushi.com.ua` (`courier-app/`, see its README). Same site as `api.emojisushi.com.ua`, so the cookie is sent with its requests; the backend accepts browser requests only from `COURIER_APP_ORIGINS`:
  - every value from Poster (addresses, comments, names) is escaped before display;
  - no inline scripts or handlers. A Content-Security-Policy header allows scripts only from our domain and one pinned Leaflet version with SRI;
  - tokens are revocable: logout, or a new password ends every session.
- **The Poster API token never leaves the server.**
- Remember the Poster finding: a Poster courier account is an ordinary employee with management-console access. The app must **not** ask for or store Poster credentials.

---

## 7. Reliability: every request must land [decided]

### App
- **Every request is checked.** Success means HTTP 2xx and, for status changes, the server confirming Poster accepted it. Anything else counts as a failure, including timeouts (suggested: 15 s).
- **Actions go into an outgoing queue** (status changes, extra trips, «Я на точке»), sent in order and **retried until they succeed**: backoff 2 s → 4 s → 8 s → … capped at 30 s, plus an immediate retry when the device comes back online.
  - The queue survives closing the app (persisted on the device).
  - While an action is queued, its button shows a pending state, and that order can't be acted on again.
- **After 3 failed requests in a row**, show a banner at the **bottom** of the screen: «Нет соединения с интернетом. Продолжаем отправлять…».
  - It closes with an **×** button or a **swipe down**.
  - Closing it doesn't stop the retries.
  - It comes back if failures continue (on the next failure after it was closed).
  - It disappears by itself as soon as any request succeeds.
- **Background refresh** (every 15–30 s, §8) uses the same failure counter, so losing the connection is noticed even when the courier isn't tapping anything.
- Errors that retrying can't fix (`401` signed out, `403` not your order, `409` already closed at the POS) **leave the queue**: the app shows a plain message and refreshes the order list instead of retrying forever.

### Server
- **Status changes are idempotent.** Setting 40 or 50 twice gives the same result, so a retry after a lost response is harmless.
- **Extra trips carry the app's `client_uuid`.** A repeat with the same UUID returns the existing task instead of creating a second one.
- If Poster fails or times out, answer **`503`**, so the app treats it as retryable.

---

## 8. Load and freshness
- If every courier's app polled Poster directly, Poster calls would grow with the number of couriers.
- **One shared, cached open-receipts read** (15–30 s in the Laravel cache), filtered per courier. **After any status change the cache is cleared**, so the courier sees their own change at once.
- **Push** on courier assignment is the fast path (the webhook is confirmed); polling stays as the fallback.
- **[unverified]** `dash.getTransactions` has a `next_tr` cursor, so results may be capped on busy days. Check before trusting counts.

---

## 9. API contract (`/api/courier/*`)

General rules:
- JSON. Times in **Europe/Kyiv** as `"YYYY-MM-DD HH:mm"`, plus `HH:mm` for display. Money in UAH.
- Every endpoint except login needs the bearer token.
- **Ownership is checked on the server:** a courier only sees and changes receipts where `delivery.courier_id == courier.poster_user_id`, and only their own tasks.
- Errors:
  - `401` no or expired token;
  - `403` not your receipt or task;
  - `409` state conflict (e.g. the receipt is already closed);
  - `422` validation, with a `{field: [messages]}` body;
  - `503` Poster unavailable, **retry**.

### Auth / profile
| Method | Path | Body → Response |
|---|---|---|
| POST | `/auth/login` | `{login, password, device_name?, token_mode?}` → `{courier, expires_at}` and the `courier_session` cookie. With `token_mode: "bearer"`: also `token`, and no cookie. `401` wrong login/password (same answer for both), `422` empty fields, `429` too many tries, `403` cookie mode without `X-Courier-App: 1` / from another origin. |
| POST | `/auth/logout` | → `204`, cookie cleared |
| GET | `/me` | → `{courier: {id, name, login, phone, linked_to_poster}}` |
| POST | `/push-token` | `{fcm_token, platform}` → `204` *(not built, step 4)* |

Cookie-authenticated `POST`s must send `X-Courier-App: 1`; the web app does this on every request.

### Orders
`GET /orders` returns `{orders: [...], server_time: "YYYY-MM-DD HH:mm"}`: the courier's receipts for yesterday + today that are still open in Poster (`status` 1), including those already at 50, so the app can show a local return. Each order:
```json
[{
  "id": "105923",
  "processing_status": 30,
  "state": "preparing | ready | in_trip | delivered",
  "due": "2026-10-13 13:05", "due_time": "13:05", "late_minutes": 0,
  "address": "ул. Академика Сахарова, 50", "place": "Odessa, Таїрове",
  "client": "Ольга", "phone": "+380674452190", "amount": 1240,
  "payment": {"cash": 0, "card": 0, "certificate": 0, "bonus": 0, "thirdParty": 1240, "wallet": 0, "paid": 0, "type": "0"},
  "comment": "…",
  "lat": 46.43, "lng": 30.70, "location_precision": "building | street | null",
  "spot": {"id": 1, "name": "Базарная, 69"}
}]
```
- `state` comes from `processing_status`: below 30 → preparing, 30 → ready, 40 → in_trip, 50 → delivered.
- «Везу сейчас» isn't part of the server state (D8); the app tracks it.

### Status
| Method | Path | Body | Rules |
|---|---|---|---|
| POST | `/orders/{id}/status` | `{status: 40 \| 50}` | The receipt must be the caller's and open (`status` 1), else 403 / 409. Allowed changes: **30 → 40** (Я поехал), **40 → 50** (Доставил) [verified]. ~~50 → 40~~ is refused by Poster [verified]. Setting the status it already has returns `200` (idempotent). Calls `transactions.updateTransaction`; returns the updated order. |

«Я поехал» with several orders = one request per order, each queued and retried on its own (§7). The app shows the trip as started once all of them have gone through.

### History and extra trips
| Method | Path | Notes |
|---|---|---|
| GET | `/history?date=YYYY-MM-DD` | `CourierLedger::history()` → items `{kind: receipt\|task, id, type, date, time, title, note, excluded, can_return}`. `can_return` = still open in Poster at 50. |
| POST | `/tasks` | `{client_uuid, type: dovoz\|zakupka\|magazin, details?, comment?}` → task. `source = app`, `performed_at = now`, `spot_id` = the spot of the courier's current orders if any. Repeat `client_uuid` → the same task. `409` if today is already closed on the payment page. No delete or edit for couriers (D10). |

---

## 10. Edge cases

- **Receipt reassigned to another courier mid-trip:** it disappears from the list on the next refresh. A queued action for it gets `403` and is dropped with a message.
- **Receipt closed at the POS while the courier still holds it:** it moves to the archive as «Закрыт в Poster». A queued 50 for it gets `409` and is dropped (the order is done anyway).
- **«Вернуть в доставку» after the POS closed the receipt:** the button isn't shown (`can_return` false). If the receipt gets closed while it's locally returned, the next refresh drops the local mark, and the order moves to the archive as «Закрыт в Poster».
- **Two devices for one courier:** Poster is the single source of truth. Both see the same statuses after the next refresh.
- **Courier deactivated mid-shift:** tokens stop working (`401`). The app returns to the login screen.
- **Unmapped courier** (`poster_user_id` empty): login works but the list is empty. Show «Вас ещё не привязали к сотруднику Poster».
- **Day boundary:** an evening trip that ends after midnight. Pay follows the POS close date (D2).
- **Unaccepted online orders** (`status` 0) are never shown to couriers.

---

## 11. Build order

The web app (D9) is the owner's page remade:
- static files in `courier-app/` (repo root), uploaded to the subdomain `courier.emojisushi.com.ua`;
- the demo data and the browser-only parts (extra trips, «Вернуть в заказы») removed;
- every call pointed at `/api/courier/*`, going through the retry queue (§7).

Status (2026-10-13): steps 1–3 are **written, not yet run end to end** (migrations 1.2.18 and 1.2.19 pending).

| Step | What | Where |
|---|---|---|
| 1 ✅ | Auth: tokens, hashed passwords, cookie session, login/logout/me | `CourierTokens`, `AuthenticateCourier`, `CourierAuthController`, migration 1.2.18 |
| 1 ✅ | Orders list, cached 20 s for all couriers, map points from the admin map's code | `CourierOrders`, `DeliveryLocations` (moved out of `DeliveryMap`) |
| 2 ✅ | Status 30 → 40, 40 → 50; Poster refusal → 409, unreachable → 503 | `PosterDeliveryStatus`, `POST /orders/{id}/status` |
| 2 ✅ | Admin map: blue for 40 | `assets/js/delivery-map.js` |
| 3 ✅ | Archive with delivered-but-open orders (`can_return`); extra trips with `client_uuid` | `CourierLedger::history()`, `POST /tasks`, migration 1.2.19 |
| ✅ | Web app: queue + retries, 3-failure banner, 10 s undo, local return, map | `courier-app/` (index.html, app.js, app.css, .htaccess) |
| 4 | Push: courier push tokens, push on assignment (needs a service worker for a web app; optional, polling works) | — |
| 5 | Optional: admin "view as courier"; transfer card number source (D7) | — |

---

## 12. Unverified, needs live testing

- ~~That `updateTransaction` moves 30 → 40 → 50 → 40~~ **Done 2026-10-13 on receipt #875:** 30 → 40 and 40 → 50 succeed with `spot_tablet_id` from D5; 50 → 40 and 50 → 30 are refused (error 32). #875 was left at 50.
- ~~Does Poster fire the `transaction` webhook after our own `updateTransaction`?~~ **Yes [verified]:** the local feed recorded #875 at the moment of the test.
- `processing_status` 20.
- Is `dash.getTransactions` capped or paginated (`next_tr`)?
- `CourierLedger::history()` and `forDate()` haven't run against a real database yet.
