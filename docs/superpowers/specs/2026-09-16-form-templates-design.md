# Plantillas de Formulario (Forms) — Diseño

Fecha: 2026-09-16
Estado: aprobado, pendiente de plan de implementación.

## 1. Contexto y objetivo

El módulo **Formularios** (`Agreement`) ya permite generar un link público donde un
cliente llena datos y firma con canvas (usado hoy para "GA COAM Equipment
Inquiry"). El problema: está **hardcodeado a un solo tipo de formulario** — los
campos, validaciones y catálogo de items están fijos en PHP
(`App\Models\Agreement::itemCatalog()`, `PublicAgreementController::store()`).

El objetivo es permitir crear **nuevas plantillas de formulario** desde
Administración (sin tocar código), cubriendo dos casos reales observados:

- **Aprobación ligada a algo en curso** (ej. cliente pide cambiar el número
  que recibe códigos OTP): hoy el agente pide por correo que el cliente
  responda con el dato sensible en texto plano. En su lugar, el agente genera
  un link donde el cliente ve el cambio propuesto y solo debe aprobar/firmar.
- **Captura de datos nueva** (ej. "Potential Retailer Sign Up"): hoy alguien
  arma a mano un correo con formato de tabla y lo reenvía al equipo. En su
  lugar, un formulario público auto-atendido que, al enviarse, notifica al
  equipo dentro de la plataforma.

**Fuera de alcance / restricciones confirmadas:**

- El módulo Tickets está detenido pendiente de aprobación del cliente — este
  diseño **no depende de Tickets/Issues** de ninguna forma.
- El formulario **"GA COAM Equipment" (`Agreement`, `type=coam_equipment`)
  no se toca**: modelo, tabla, controladores, vistas y su notificación por
  correo quedan exactamente como están. El motor nuevo es una funcionalidad
  adicional y paralela, no una migración de lo existente.
- Los formularios generados con el motor nuevo **nunca envían correo** — solo
  notificación dentro de la plataforma (campanita + fila en el listado de
  envíos).

## 2. Modelo de datos (nuevo, no modifica tablas existentes)

### `form_templates`
| Campo | Tipo | Notas |
|---|---|---|
| `name` | string | |
| `slug` | string, nullable, único | solo se usa si `mode = standalone` |
| `instructions` | text, nullable | texto que ve el destinatario arriba del formulario |
| `requires_signature` | boolean | si exige firma (canvas) o basta un botón "Aprobar" |
| `mode` | enum: `on_demand` \| `standalone` | ver sección 3 |
| `notify_group_id` | FK `groups`, nullable | a quién se notifica al completarse |
| `is_active` | boolean | |
| `created_by` | FK `users` | |

### `form_fields`
| Campo | Tipo | Notas |
|---|---|---|
| `form_template_id` | FK | |
| `label` | string | |
| `key` | string | slug del label, único por plantilla |
| `field_type` | enum | `text`, `textarea`, `select`, `checkbox`, `radio`, `pick_n`, `date`, `file` (mismo catálogo de tipos que `FieldDefinition` de Tickets, pero tabla propia — sin acoplarse a Tickets) |
| `is_required` | boolean | |
| `editable_by_recipient` | boolean, default true | `false` = el agente ya lo llenó y el cliente solo lo ve (caso "número actual") |
| `help_text` | string, nullable | tooltip |
| `pick_count` | int, nullable | solo para `pick_n` |
| `sort_order` | int | |

### `form_field_options`
`form_field_id`, `value`, `sort_order` — igual a `field_option` de Tickets.

> Nota: no se incluye el tipo `item_table` (catálogo con precios) en esta
> primera entrega — es exclusivo de COAM Equipment, que queda intacto. El
> catálogo de `field_type` es extensible si una plantilla futura lo necesita.

### `form_submissions`
| Campo | Tipo | Notas |
|---|---|---|
| `uuid` | string, único | identifica el link público (`on_demand`) |
| `form_template_id` | FK | |
| `status` | enum: `pending`, `submitted`, `expired` | |
| `expires_at` | datetime, nullable | solo aplica a `on_demand` |
| `signature_path` | string, nullable | solo si `requires_signature` |
| `signed_ip` | string, nullable | |
| `submitted_at` | datetime, nullable | |
| `reference_note` | string, nullable | anotación libre del agente al gestionar (equivalente genérico de `linked_ticket_number` de `Agreement`) |
| `created_by` | FK `users`, nullable | agente que generó el link (`on_demand`); null en `standalone` |
| `managed_by` / `managed_at` | FK `users` / datetime, nullable | igual patrón que `Agreement` |

### `form_submission_values`
`form_submission_id`, `form_field_id`, `value` — valores capturados, un registro por campo.

## 3. Los dos modos

- **`on_demand`**: el agente elige la plantilla desde Admin, llena los campos
  con `editable_by_recipient = false` (lo que ya sabe — ej. "número actual"),
  define vigencia del link, y el sistema genera un `form_submission` con
  `status = pending` y su `uuid`. El cliente abre el link, ve esos campos de
  solo lectura más los editables, y aprueba/firma. Al enviar, `status` pasa a
  `submitted`.
- **`standalone`**: la plantilla tiene una URL fija y pública (`/f/{slug}`),
  siempre abierta, sin expiración. No hay paso previo de "generar link" — al
  hacer submit se crea el `form_submission` directamente con
  `status = submitted`. Todos los campos son editables por definición (no
  tiene sentido `editable_by_recipient = false` aquí, se valida en el builder
  de plantillas).

En ambos casos, al llegar a `status = submitted` se dispara la notificación
(sección 5).

## 4. Administración

**`Admin\FormTemplateManager`** (Livewire, mismo patrón que
`Admin\CatalogBrowser` pero un solo nivel — no hay categorías): lista de
plantillas → expandir → agregar/editar/reordenar campos y sus opciones.
Permiso `form_templates.manage`.

**`Admin\FormController`** (Controller normal, mismo patrón que
`AgreementController` — no Livewire, para mantener consistencia con lo ya
construido en este módulo):
- `forms.index` — listado de envíos, filtrable por plantilla/estado. Cada
  fila muestra: plantilla, valor del campo identificador (el primer campo
  requerido de la plantilla), fecha de envío, estado.
- `forms.create` / `forms.store` — solo `on_demand`: formulario dinámico que
  renderiza los `form_fields` con `editable_by_recipient = false` para que el
  agente los llene, más selección de vigencia.
- `forms.show` — detalle, link para compartir, valores si ya se completó.
- `forms.manage` — marca como gestionado + `reference_note`.

Permisos nuevos: `form_templates.manage`, `forms.view`, `forms.create`,
`forms.manage`. Asignación por defecto igual al patrón ya documentado en
`AgreementPolicy`: Admin (todo), Agente (todo lo de Formularios) — editable
después desde `RoleManager`.

## 5. Rutas públicas y notificación

- **on_demand**: `GET/POST /formularios/{uuid}` (+ `/gracias`) — mismo patrón
  que `PublicAgreementController`, pero renderiza campos según `form_fields`
  de la plantilla en vez de código hardcodeado por tipo.
- **standalone**: `GET/POST /p/{slug}` — siempre abierto, sin expiración.
  (No se usa el prefijo `/f/` porque ya lo ocupan los links de COAM Equipment
  — `PublicAgreementController` — y ambos son solo comodines de un segmento,
  por lo que colisionarían.)

**`FormNotifier::notifySubmitted(FormSubmission $submission)`** (nuevo
servicio, paralelo a `AgreementNotifier`, sin modificarlo): notifica a los
miembros de `form_template.notify_group_id` mediante
`FormSubmittedNotification`, **con `via()` limitado a `['database']`** — sin
canal `mail`, a diferencia de `AgreementSignedNotification`. Aparece en la
campanita (`NotificationBell` ya lee `$user->notifications()`, sin cambios
ahí) y queda reflejado en el listado `forms.index`.

## 6. Fuera de alcance de esta entrega

- Migrar COAM Equipment al motor nuevo.
- Tipo de campo `item_table` / catálogo con precios.
- Cualquier integración con Tickets/Issues (el módulo está detenido).
- Reenvío automático de `on_demand` por evento (ej. disparado al cambiar un
  estado en otro sistema) — por ahora el agente lo genera manualmente desde
  Admin.

## 7. Testing

- Feature tests: creación de plantilla + campos vía `FormTemplateManager`.
- Feature tests: flujo `on_demand` completo (agente genera link → cliente
  firma → notificación en `database` → nunca en `mail`).
- Feature tests: flujo `standalone` completo (submit crea `form_submission`
  sin paso previo).
- Policy tests: permisos `form_templates.manage`, `forms.*` por rol.
- Test explícito de que `Agreement`/COAM Equipment sigue funcionando sin
  cambios (regresión).
