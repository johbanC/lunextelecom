# Especificación de desarrollo — Sistema de Tickets Lunex Telecom

Documento de handoff para iniciar el desarrollo en Laravel. Compila todo lo levantado del sistema actual (capturas reales del CRM viejo) y las decisiones tomadas con Johban Clavijo. Léelo completo antes de tocar código; es la fuente de verdad del catálogo y del modelo de datos.

---

## 1. Contexto

Lunex Telecom tiene un sistema interno de tickets (Admin Accounting Portal, `ats.lunextelecom.local`) construido hace más de una década. Funciona, pero: las notificaciones al equipo se hacen manualmente por correo (el agente copia el ticket y lo pega en un email), no hay niveles de acceso, no hay indicador visual de prioridad/tiempo (semáforo), y el catálogo de Categoría → Issue → Campos es fijo (definido en el código, no editable desde la plataforma).

El nuevo sistema debe ser moderno, intuitivo y elegante, con: envío automático de notificaciones (correo + en plataforma), enrutamiento a grupos (ej. "si envío a Accounting, le llega a todo el grupo y cualquiera de ellos lo puede atender") o a una persona específica, niveles de acceso, semáforo de tiempo, timeline del ticket, campos configurables como obligatorios, catálogo de categorías/issues administrable sin tocar código, y centro de ayuda.

## 2. Tipos de ticket

Dos flujos de creación independientes, con encabezados fijos distintos, pero que comparten el mismo motor de formulario dinámico, historial, notificaciones y semáforo:

### 2.1 Retailer Ticket
Encabezado fijo (solo lectura, traído del retailer buscado): Retailer (código de cuenta), Street, Suite, City, State, Zip Code, Owner, Phone, Email.
Selects fijos: Sku, Category*, Issue*, Status*, Related To*, Assignee, Priority*.

### 2.2 Customer Ticket
Encabezado fijo: Phone, SKU, Full Name, City, State (distinto al de Retailer).
Selects fijos: Category*, Issue*, Status*, Related To*, Assignee, Priority*, + campo **Tx Id** de texto libre siempre visible antes de los campos dinámicos.
Trae sección **"Extra Customers" / "Add extra customer"** (permite vincular más de un cliente al mismo ticket) — no existe en Retailer.
Casi todos los issues agregan un campo de texto libre **Retailer** al inicio del bloque dinámico (para anotar con qué retailer se relaciona la llamada del cliente).

Patrón recurrente: sub-bloque "Follow up" (checkboxes: CSR Please call customer when received a resolution / 1st-2nd-3rd try - Called customer no answer / Customer was informed / Other + campo Notes) que aparece en muchos issues de Accounting/Conect2/TopUp. Se recomienda modelarlo como componente reusable en la UI (no duplicar field_definitions issue por issue), aunque en el sistema viejo está duplicado (con bugs de copy-paste, ej. "Notes" repetido dos veces en algunos issues).

## 3. Regla de modelo de datos (crítica)

El set de campos dinámicos depende **siempre del Issue**, nunca de la Categoría. La Categoría solo filtra qué Issues aparecen en el dropdown. El catálogo completo debe ser administrable (crear/editar categorías, issues y sus campos) sin despliegue técnico — requerimiento explícito: *"En Issue poder establecer nuevos Issues"*.

## 4. Catálogo completo — Retailer Ticket

### Categoría "R Account inquiry" (Related To por defecto: Accounting)
Campos base comunes a casi todos: Caller Name, Caller #, Method of Verification (Pick 2: User ID / Caller Name / Phone Number / Address / Last 4 digit of Bank Account), Notes.
- **Account Reactivation/Status**: Entity Name (además de base).
- **ACH for Extra Credit**: Current Credit, ACH & Refill Amount.
- **Add New Product**: Product Request (SKU).
- **Check Sales Report/Send Invoice**: Product Request (Name & SKU), From Date, To Date.
- **Conect2-Pinless Void**: Customer #, Transaction Date, Transaction Amount, Void Reason.
- **Credit Balance Update**: Current Credit Balance.
- **Others**: Issue Description.
- **Password Reset**: Product Request (SKU), Entity Type (Pick 1: Special Distributor / Super Retailer / Authorized API / Full Integration / Others), Sent Password To (checkbox: Phone Number / Email Address).
- **TopUp Noc Inquiry**: Key Account (Y/N), Transaction Date, Transaction ID, Country, Carrier, TopUp Phone, US Phone, Amount, Reason.
- **Update Info**: Update Request, NEW info.

### Categoría "R Retailer POS"
- **Login Issues**: Caller Name, Caller #, User ID, Method of Verification (Pick 2), Issues (radio: Did not receive Verification Code / Verification Code was input but still can't login / Correct Password but still can't login / Oth), Screen-shot provided (Y/N), Notes.

### Categoría "R Retailer Services"
- **Order Material**: Caller Name, Caller #, Method of Verification (Pick 2), USER ID, Store Name, Address, Ship To ID, Material Request (checkbox: OMNY Cards / OMNY Marketing Materials / COAM Cards / COAM Marketing Materials / Others), Quantity, Card Order placed (Y/N), Card Order Date, Notes.
- **Others**: Caller Name, Caller #, Method of Verification (Pick 2), Issue Description, Notes.
- **POS Issues**: Caller Name, Caller #, User ID, Method of Verification (Pick 2), Product, OS/Browser Version, Issue Date, Label (checkbox: 1ClicMax / PICA / Mega Minutos / Mas Minutos / Youtelo / SSO-MAXI), Screen-shot provided (Y/N), Issue Description (checkbox: Retailer gets error message / Unable to display page / Account Restricted / Promotion not displayed / Others), Test Result, Notes.
- **Promotion**: Caller Name, Caller #, Method of Verification (Pick 2), Product/Promo, Comment/Input, Notes.
- **Sale on Behalf**: Caller Name, Caller #, Method of Verification (Pick 2), Customer #, Amount, Reason, Notes.
- **Training Inquiry**: Caller Name, Caller #, Method of Verification (Pick 2), USER ID, Store Name, Address, Date Account was Opened, Notes.

> **Nota**: el catálogo de "Retailer POS" aparece incompleto en el Excel de Maritza — confirmar con ella si hay más issues antes de cerrar este catálogo.

## 5. Catálogo completo — Customer Ticket

Casi todos los issues agregan **Retailer** (texto libre) como primer campo del bloque dinámico.

### Categoría "Customer POS"
- **Bonus discrepancy**: solo Retailer.
- **Calling instruction Assistance**: Customer Name, Caller #, Destination #.
- **Can not Call/Connect**: Customer Name, Caller #, Destination # (mismos campos que Calling instruction Assistance).
- **Drop Call**: solo Retailer.
- **eGift did not go through**: Receive Phone Number, Sender Phone Number, Transaction ID, Transaction Status, Transaction Date/Time, Operator, Info provided to the caller.
- **Fraud case-Balance removed**: solo Retailer.
- **International Recharge has not gone through**: Caller Name, Caller #, Transaction ID, Transaction Status, Transaction Date/Time, Destination Country, Operator, Topup Phone, Info provided to the caller.
- **Need recharge confirmation**: Caller Name, Caller #, Destination Country, Operator, Topup Phone.
- **No confirmation received**: igual a eGift did not go through.
- **Other**: Other Issue.
- **Processed twice**: Caller Name, Caller #, Transaction ID, Destination Country, Operator, Topup Phone, Info provided to the caller.
- **Quality Issue**: solo Retailer.
- **Recharge assistance**: Caller Name, Caller #, Operator, PIN/RTR?, Topup phone, Info provided to the caller.
- **Recipient did not receive egift**: igual a eGift did not go through.

### Categoría "GA COAM Account Inquiry"
- **Sign Up Inquiry**: Owner's Name, Owner's Cell Phone Number, Store Phone Number, Best time to call back, Primary E-mail Address, Store Name/DBA, Store Address, Company/Business Name, Location Licence Holder (LLH#), Master Licence Holder (MLH) Name, How did you hear about us?, Additional Notes. (No trae Caller Name/Caller#/Method of Verification base — es un formulario de onboarding aparte).
- **Follow up/call back**: sin campos extra.
- **New Ownership**: sin campos extra.
- **OTHER**: sin campos extra.

### Categoría "ACCOUNTING INQUIRY"
- **Add Minutes**: Caller Name, Caller #, Method of Verification (Pick 2), Balance, Number of Refill/Cycle, Info provided to the caller, Follow up (bloque completo), Notes.
- **CC-Status**: Caller Name, Caller #, Credit Card Status (radio: Decline/No Match/Zip Match/Address Match), # of tries, 1st/2nd/3rd try status, Info provided to the caller, Follow up, Notes.
- **HOLD Balance**: Caller #, Caller Name, Retailer Login, Method of Verification (dropdown simple), Hold Amount, Reason, Notes.
- **Refund-Fraud call**: Caller Name, Caller #, Retailer Login, Method of Verification, Allowed Destination Country, Allowed Destination #, Fraud Destination Country, Fraud Destination #, Issue Date, Refund Amount, Info provided to the caller, Follow up, Notes.
- **Refund-CC**: Caller Name, Caller #, Retailer Login, Method of Verification, Transaction Date, Transaction ID, Voided Amount, CC last 4 digits, Credit Card Status (radio incl. Other), Notes.
- **Fraud Case-Balance removed**: Customer Name, Caller #, Retailer Login, Method of Verification, Suspicious Activity (checkbox: Unusual Call Country / Unusual Credit Balance consumed / Unusual Call Frequency / Different Credit Cards provided / Credit card status not Exact Match), Notes.
- **Back-Charge**: Customer Name, Caller #, Retailer Login, Transaction Date, Transaction ID, Amount, Credit Card Last 4 digits, Info provided to the caller, Follow up, Notes.

### Categoría "CONECT2 INQUIRY"
- **DID/Access Number**: Customer Name, Caller #, Issue Date/Time, Option, Notes, Issue Description (dropdown simple, ej. "Silence"), Follow up, Notes, Caller Phone Carrier/Operator, DID/TFN, Test Results, Info provided to the caller.
- **Destination**: Customer Name, Caller #, DID/Access #, Destination #, Issue Date/Time, Issue Description (dropdown, ej. "Static, Noise, Distortion"), Test Results DID + Destination #, Info provided to the caller, Follow up, Notes.
- **Speed Dial Number / 1Clic # Add/Edit**: Customer Name, Caller #, Contact Added, Country Added, Notes.
- **DID/Dialing Instructions**: Customer Name, Caller #, DID #, Speed Dial/1Clic #, Notes.
- **Possible Hang Call**: Caller Name, Caller #, DID/TFN, Estimated Date/Time, IVR Message, Notes.
- **Refund-Test Call**: Account #, Amount, Reason, Notes.
- **Bonus discrepancy** (variante propia de Conect2, distinta a la de Customer POS): Caller Name, Caller #, SMS promotion, Date, Amount, Info provided to the caller, Follow up, Notes.
- **Unlimited Plan**: Caller Name, Caller #, Product (SKU), Issue, Info provided to the caller, Follow up, Notes.
- **Account Expired**: Customer Name, Caller #, DID #, Expiration Date, Expire Amount, Method of Payment, Expiration SMS Notification (Y/N + When?), Info provided to the caller, Follow up, Notes.
- **SECURITY CODE INQUIRY**: Customer Name, Caller #, DID #, Message after dialing DID, Info provided to the caller, Follow up, Notes.

### Categoría "TOP UP INQUIRY"
- **International TopUp**: Caller Name, Caller #, Retailer Login, Transaction ID, Transaction Status, Transaction Date/Time, Destination Country, Operator, Topup Phone, Issue Description (dropdown, ej. "Beneficiary didn't received credit"), Info provided to the caller, Follow up, Notes.
- **Domestic TopUp**: mismo patrón que International TopUp pero sin Destination Country (doméstico).
- **GA COAM**: Caller Name, Caller #, Retailer Login, Transaction ID, Transaction Status, Transaction Date/Time, Issue Description, Info provided to the caller, Notes.
- **PINLESS/BOSS & SIN PIN**: Caller Name, Caller #, Retailer Login, Transaction ID, Transaction Status, Transaction Date/Time, Product (SKU), TopUp Phone, Issue Description (dropdown, ej. "Incorrect Product"), Info provided to the caller, Follow up, Notes.
- **RELOADABLE CARD**: mismos campos que PINLESS/BOSS & SIN PIN.
- **E-Gift**: catálogo de campos pendiente de confirmar (no se vio captura detallada; usar patrón similar a los otros issues de TopUp — Transaction ID/Status/Date, Issue Description — hasta confirmar).

### Categoría "COMPLAINT"
Campos mínimos en los tres issues: Caller Name, Caller #, Retailer Login, Notes.
- **Others**
- **Promotion**
- **Rate**

### Categoría "GENERAL INQUIRY"
- **Balance Inquiry**: pendiente de confirmar detalle exacto de campos (visto en el dropdown, sin captura de formulario completo).
- **Follow Up/Call back**: Caller Name, Caller #, Retailer Login, Reason, Result, Notes.
- **New customer**: Caller Name, Caller # (formulario mínimo).
- **Others**: Caller Name, Retailer Login, Notes.
- **Promotion**: igual a Others.
- **Rate**: igual a Others.
- **Connect2 Sale**: Caller Name, Caller #, Retailer Login, Product (SKU), Transaction ID, Transaction Status, Transaction Date/Time, Credit Card Estatus, Credit Card Last 4 digits, Notes.
- **ITU Sale**: Caller Name, Caller #, Method of Verification (Pick 2: ITU Sale Verification Docs / Previous ITU's Sale / Other), Transaction ID, Transaction Status, Transaction Date/Time, Country/Operator, Credit Card Estatus, Credit Card Last 4 digits, Notes.
- **Subscribe/Unsub. SMS**: Caller Name, Caller #, Would like to Subscribe #, Would like to Unsubscribe #, Label/Product, SMS Issue (checkbox: Received Incorrect information / Didn't receive Confirmation text / Promotional Information / Other), Notes.
- **Transfer Call**: Caller Name, Caller #, Transfer To, Notes.
- **Update Account Info**: Caller Name, Caller #, Method of Verification (Pick 2), Notes.
- **CALL BACK / FOLLOW UP**: Caller Name, Caller Contact Phone #, Extension, Best Time to Call Back, Company Name, Reference to, Notes.

## 6. Modelo de datos propuesto

| Entidad | Descripción / campos clave |
|---|---|
| `ticket_type` | Retailer \| Customer — define qué catálogo de categorías aplica |
| `category` | Nombre, ticket_type_id, activo/inactivo |
| `issue` | Nombre, category_id, activo/inactivo, creado_por (para permitir issues nuevos desde Admin) |
| `field_definition` | issue_id, nombre del campo, tipo (texto, selección, pick-N, checkbox, radio, fecha, adjunto), obligatorio (booleano), texto de ayuda (tooltip), orden |
| `field_option` | field_definition_id, valor de la opción (para campos tipo selección/checkbox/radio/pick-N) |
| `ticket` | ticket_type, category_id, issue_id, retailer/cliente (datos capturados manualmente), status, priority, related_to_group_id, assignee_user_id, creado_por, creado_en, actualizado_en, sla_due_at |
| `ticket_field_value` | ticket_id, field_definition_id, valor capturado |
| `ticket_event` / timeline | ticket_id, tipo de evento, autor, fecha/hora, detalle |
| `ticket_comment` | ticket_id, autor, texto, fecha, visibilidad (interno/externo) |
| `attachment` | ticket_id, archivo, subido_por, fecha |
| `group` | Nombre (ej. Accounting, CSR Spanish), **applies_to** (Retailer / Customer / Ambos — controla en qué formulario de ticket aparece este grupo como opción de "Related To") |
| `group_member` | group_id, user_id, rol dentro del grupo |
| `user` | Nombre, email, rol (asesor/líder/director/admin), grupos a los que pertenece |
| `notification_rule` | Evento disparador, category_id (opcional), grupo destinatario, canal (correo/plataforma), plantilla |

El catálogo completo de Categoría → Issue → Campos (secciones 4 y 5 de este documento) debe cargarse como datos semilla (seeders de Laravel), no hardcodeado en el código — así el Admin puede editarlo después desde el propio sistema.

### Requerimiento de grupos multi-tipo
Los grupos deben poder configurarse para indicar en qué tipo(s) de ticket aparecen como opción de "Related To": solo Retailer, solo Customer, o ambos. Hoy en el sistema viejo esto es fijo (catálogos de grupos separados por tipo de ticket); el nuevo sistema debe permitir esa multiplexión desde el panel de administración de grupos (campo `applies_to` en la tabla `group`).

## 7. Roles y niveles de acceso

| Rol | Alcance |
|---|---|
| Asesor (Agente) | Crea tickets, ve y gestiona los asignados a él o a sus grupos, comenta y adjunta archivos, cambia estado dentro de su alcance. |
| Líder de equipo | Todo lo del Asesor + ve todos los tickets de su(s) grupo(s), reasigna entre miembros, ve reportes de su equipo, gestiona miembros del grupo. |
| Director / Administración | Ve todos los tickets y grupos, reportes globales, filtra y exporta todo, gestiona reglas de notificación y SLA por categoría. |
| Admin (sistema) | Todo lo anterior + administra el catálogo completo (categorías, issues, campos, obligatoriedad), crea/edita grupos (incl. `applies_to`), gestiona usuarios y roles, configura plantillas de correo y centro de ayuda. |

Implementar con `spatie/laravel-permission`, y verificar permisos tanto en la UI como en el backend (policies/gates), no solo ocultando botones.

## 8. Mejoras generales → cómo se resuelven

| # | Mejora solicitada | Solución en el MVP |
|---|---|---|
| 1 | Notificaciones por estatus y por área/departamento | Motor de notificaciones (correo + campana en plataforma) disparado por eventos: creación, cambio de estado, reasignación, vencimiento próximo de SLA. Reglas configurables por Categoría/Grupo. |
| 2 | Niveles de acceso: asesor, líder, director, admin | Ver sección 7. |
| 3 | Qué información es obligatoria | Cada campo del catálogo se marca obligatorio/opcional desde un panel de administración, con validación en tiempo real. |
| 4 | Línea de tiempo del ticket | Vista timeline vertical con cada evento (creación, cambios de campo, cambios de estado, comentarios, correos enviados) con autor y fecha/hora. |
| 5 | Semáforo de tiempo: 1 día verde, 2 días amarillo, 3 días rojo | Indicador visual (chip de color) en lista y detalle, calculado sobre tiempo transcurrido desde creación o último cambio de estado, configurable por categoría. |
| 6 | Asignar a departamento y a persona encargada dentro del departamento | Related To = grupo, Assignee = persona (mismo patrón de hoy), pero con grupos administrables (crear/editar, agregar/quitar miembros) y opción de dejar el ticket "disponible para el grupo" sin asignar a una persona. |
| 7 | Burbujas de información / instrucciones | Tooltips junto a cada campo del formulario, con texto configurable por campo desde administración. |
| 8 | Filtrar y descargar información de tickets | Vista de lista con filtros combinables (estado, categoría, issue, grupo, asignado, rango de fechas, semáforo, retailer) + exportación a Excel/CSV. |
| 9 | Centro de ayuda | Sección de autoservicio con guías por categoría, FAQ y glosario de Issues. |

## 9. Stack técnico

- **Backend**: Laravel + Eloquent (se adapta bien al modelo EAV-like de la sección 6).
- **Roles**: `spatie/laravel-permission`.
- **Notificaciones**: Notifications + Queues nativos de Laravel.
- **Base de datos**: agnóstico (MySQL o Postgres/Supabase) — pendiente decisión del cliente.
- **Frontend**: Livewire (recomendado). Alternativa evaluada: Inertia + Vue/React (más flexible a futuro, ej. app móvil, pero mayor curva de aprendizaje para un equipo de 2).

## 10. Estimado y roadmap sugerido (equipo de 2 desarrolladores full-time)

Estimado total: **3 a 4.5 meses** para el MVP completo. Puede comprimirse a **2-2.5 meses** lanzando primero Retailer + Customer con notificaciones básicas, dejando centro de ayuda y reportes avanzados para una segunda entrega.

Desglose orientativo:
1. Modelo de datos + autenticación/roles — ~2 semanas.
2. Motor de formulario dinámico (la pieza más compleja: renderiza campos según Issue, builder de catálogo en Admin) — ~3-4 semanas.
3. Listado de tickets + timeline + semáforo — ~2 semanas.
4. Notificaciones por correo y por grupo — ~1.5-2 semanas.
5. Filtros/exportación + centro de ayuda — ~1.5 semanas.
6. Carga y pruebas del catálogo completo real (secciones 4 y 5 de este documento como seeders) — ~1 semana.
7. QA, ajustes con feedback de Lunex, buffer — ~2-3 semanas.

## 11. Decisiones pendientes antes de cerrar el 100% del alcance

1. **Motor de base de datos**: MySQL vs. Supabase/Postgres — pendiente de confirmación del cliente (costo recurrente adicional en su país).
2. **Catálogo de "Retailer POS"**: aparece incompleto en el Excel de Maritza — confirmar si hay más issues además de "Login Issues".
3. Issues sin detalle completo de campos capturado: "E-Gift" (Top Up Inquiry) y "Balance Inquiry" (General Inquiry) — usar el patrón de issues similares como base y confirmar con el equipo de Lunex antes de cerrar esos dos formularios específicos.

## 12. Archivos de referencia

- `docs/Lunex_Ticket_System_Discovery_MVP.docx` — versión narrativa/ejecutiva de este mismo levantamiento, entregada al cliente.
- Excel original: "TICKET SYSTEM UPDATE INQ. Rev 10232025.xlsx" (enviado por Maritza) — hojas "CSR Current system", "CSR Suggested Ticket System", "RETAILER TICKET SYS", "Related TO".
