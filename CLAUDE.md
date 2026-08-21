# Lunex Telecom — Modernización del Sistema de Tickets

Este archivo se carga automáticamente por Claude Code al abrir este proyecto. Da el contexto mínimo para empezar a desarrollar; el detalle completo (catálogo de categorías/issues/campos, modelo de datos, roles, roadmap) está en `docs/SPEC_DESARROLLO.md` y en `docs/Lunex_Ticket_System_Discovery_MVP.docx`. Lee ambos antes de escribir código.

## Qué estamos construyendo

Un reemplazo moderno del CRM interno de tickets de Lunex (Admin Accounting Portal, en `ats.lunextelecom.local`), que hoy es funcional pero está desactualizado: sin notificaciones automáticas nativas, sin niveles de acceso, sin semáforo de tiempo, con catálogo de categorías/issues fijo (no editable desde el sistema).

Hay dos tipos de ticket con flujos de creación independientes pero que comparten el mismo motor:
- **Retailer** (tickets de comerciantes/retailers)
- **Customer** (tickets de clientes regulares / CSR)

## Stack decidido

- **Backend**: Laravel (el equipo ya trabaja en PHP/Laragon).
- **Roles y permisos**: `spatie/laravel-permission`.
- **Notificaciones**: sistema de Notifications + Queues nativo de Laravel (correo + notificación en plataforma, disparadas por evento y enrutadas a usuario o grupo).
- **Frontend**: Livewire (recomendado sobre Inertia+Vue/React por velocidad de desarrollo con equipo de 2 personas; todo en PHP, sin stack de JS aparte).
- **Base de datos**: MySQL o Postgres (Supabase) — Laravel es agnóstico a esto. **Decisión pendiente de confirmar con el cliente** (ver "Decisiones pendientes" abajo); no bloquea empezar el desarrollo del modelo de datos.

## Regla de oro del sistema (ya validada con capturas reales del sistema viejo)

El set de campos dinámicos de un ticket depende **siempre del Issue seleccionado, nunca de la Categoría**. La Categoría solo filtra qué Issues aparecen en el dropdown de Issue. Cada Issue tiene su propio set de campos, no reutilizable entre issues. Por eso el modelo de datos se construye a nivel de `issue` (no de `category`), con un builder de campos administrable sin tocar código (requerimiento explícito del cliente: "poder establecer nuevos Issues").

## Decisiones pendientes (bloquean detalles, no el arranque del desarrollo)

1. Motor de base de datos: MySQL vs. Supabase/Postgres — pendiente de confirmación del cliente por el costo recurrente en su país.
2. Catálogo completo de "Retailer POS" — aparece incompleto en el Excel de Maritza; confirmar con ella antes de cerrar el catálogo final de ese tipo de ticket (ya tenemos el flujo de "Login Issues" documentado, pero puede haber más issues).

## Documentos de referencia en este repo

- `docs/SPEC_DESARROLLO.md` — catálogo completo de categorías/issues/campos de ambos tipos de ticket, modelo de entidades, roles, mejoras generales mapeadas a soluciones, y roadmap.
- `docs/Lunex_Ticket_System_Discovery_MVP.docx` — documento de levantamiento entregado al cliente (misma información en formato narrativo/ejecutivo).
