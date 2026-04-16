## Shiprocket integration (Laravel API + React frontend)

This project uses **Shiprocket V2 API** from the backend only. React calls your Laravel APIs; React never calls Shiprocket directly.

### Backend setup

- **1) Run migrations**

This adds optional columns to `orders`:
- `shiprocket_order_id`
- `shiprocket_shipment_id`
- `shiprocket_awb`

And creates the Shiprocket master table:
- `shiprocket_settings`

Run:

```bash
php artisan migrate
```

### Shiprocket master settings (DB, not .env)

Create an active Shiprocket setting (like your payment settings) using:

- `POST /api/admin/shiprocket-settings/store`

Example body:

```json
{
  "mode": "live",
  "status": true,
  "base_url": "https://apiv2.shiprocket.in/v1/external",
  "live_email": "you@company.com",
  "live_password": "your_password",
  "pickup_location": "Primary",
  "channel_id": "12345",
  "default_weight": 0.5,
  "default_length": 10,
  "default_breadth": 10,
  "default_height": 5,
  "token_cache_minutes": 720
}
```

Notes:
- Only **one row should be active** (`status=true`). When you activate a row, the API disables other active rows automatically.
- `.env` is treated as a fallback only (recommended: keep Shiprocket creds in DB).

### API auth (important)

All Shiprocket endpoints are **admin protected**:

- `auth:sanctum`
- `role:admin,sales,accounts`

So React must send:

- `Authorization: Bearer <sanctum_token>`
- `Accept: application/json`

### Shiprocket API endpoints you can call from React

Base URL (typical Laravel): `/api`

- **0) Manage Shiprocket master settings**
  - `GET /api/admin/shiprocket-settings`
  - `GET /api/admin/shiprocket-settings/{id}`
  - `POST /api/admin/shiprocket-settings/store`
  - `POST /api/admin/shiprocket-settings/status/{id}` with `{ "status": true }`

- **1) Check credentials / list pickup locations**
  - `GET /api/admin/shiprocket/pickup-locations`

- **2) Create Shiprocket shipment for an order**
  - `POST /api/admin/shiprocket/orders/{orderId}/create`
  - Optional JSON body (all optional):

```json
{
  "pickup_location": "Primary",
  "payment_method": "cod",
  "weight": 0.7,
  "length": 12,
  "breadth": 10,
  "height": 6,
  "is_insurance_opt": false,
  "is_document": false
}
```

What it does:
- Reads your local `orders` + `order_items`
- Builds Shiprocket payload
- Calls Shiprocket `POST /orders/create/adhoc`
- Saves returned `shiprocket_shipment_id` / `shiprocket_order_id` / `shiprocket_awb` into `orders` if present

- **3) Generate AWB**
  - `POST /api/admin/shiprocket/orders/{orderId}/awb`
  - Body:

```json
{
  "courier_company_id": 123,
  "shipment_id": 456
}
```

Notes:
- If `shipment_id` is not passed, backend tries `orders.shiprocket_shipment_id`.

- **4) Track by AWB**
  - `GET /api/admin/shiprocket/track/awb/{awb}`

### React usage example (fetch)

```js
const API_BASE = import.meta.env.VITE_API_BASE_URL; // e.g. https://your-domain.com
const token = localStorage.getItem("token");

async function shiprocketCreate(orderId) {
  const res = await fetch(`${API_BASE}/api/admin/shiprocket/orders/${orderId}/create`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
      "Authorization": `Bearer ${token}`,
    },
    body: JSON.stringify({
      pickup_location: "Primary",
      payment_method: "prepaid",
      weight: 0.5
    }),
  });

  const json = await res.json();
  if (!json.status) throw new Error(json.message || "Shiprocket create failed");
  return json.data;
}
```

### Dynamic values (what you can change without code)

- **Credentials / base URL**: `.env` values.
- **Pickup location**: set default in `.env` OR pass per request in React.
- **Parcel dimensions**: default in `.env` OR pass per request.
- **COD/prepaid**: backend uses the order’s `payment_method` OR you can override with `payment_method` in the request.

