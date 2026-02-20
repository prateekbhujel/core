# Notification Automation Tutorial

You can create rule-based notifications from **Settings > Notifications > Automation Rules**.

## Supported model/event

- `Request / CRUD Activity`
- Event: `Request Completed`

These rules evaluate route + method patterns from tracked activity.

## Rule fields

- **Methods**: HTTP methods to match (`POST`, `PUT`, `PATCH`, `DELETE`, etc.)
- **Route Patterns**: wildcard route names/paths, e.g.:
  - `settings.users.*`
  - `invoices.*`
- **Level**: info/success/warning/error
- **Audience**:
  - Admins
  - All Users
  - Specific Role
  - Specific Users
- **Channels**:
  - In-app
  - Telegram
- **Throttle**: seconds to suppress duplicate sends

## Template placeholders

- `{actor_name}`
- `{actor_email}`
- `{method}`
- `{path}`
- `{route_name}`
- `{status}`
- `{ip}`
- `{timestamp}`

Example title:

```txt
{actor_name} triggered {method} on {route_name}
```

Example message:

```txt
Status {status} at {path} from {ip}.
```

## Recommendation

- Start with narrow `Route Patterns` to avoid alert floods.
- Keep Telegram enabled only for critical rules.
- Use warning/error levels for important operations.

