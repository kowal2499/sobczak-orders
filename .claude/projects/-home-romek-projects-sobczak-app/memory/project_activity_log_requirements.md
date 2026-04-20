---
name: ActivityLog module requirements
description: Preliminary requirements for the future universal ActivityLog module, derived from TaskStatusLog/StatusLog use case
type: project
---

Moduł ActivityLog ma zastąpić wąsko sprofilowane logi (`StatusLog`, `TaskStatusLog`) jednym uniwersalnym mechanizmem śledzenia zdarzeń i zmian w systemie.

**Why:** Obecnie każda encja ma własny mechanizm logowania (StatusLog → Production, TaskStatusLog → Task). Powoduje to duplikację i coupling. ActivityLog ma być modułem obserwatora bez bezpośrednich FK do innych encji.

**How to apply:** Gdy użytkownik podejmie temat implementacji ActivityLog, wyjść od tych wymagań jako punktu startowego.

---

## Identyfikacja encji (bez FK)

- `entityType` — string, np. `"task"`, `"production"`, `"agreement_line"`
- `entityId` — int
- Brak FK — moduł toleruje sieroty (wpisy dla usuniętych encji)

## Typy zdarzeń (`eventType`)

- `field_changed` — zmiana pola (status, data, tytuł)
- `event` — zdarzenie domenowe (np. `task.created`, `agreement.archived`)
- `action` — akcja użytkownika (np. `comment.added`)

## Payload

- `previousValue` — nullable, JSON lub scalar
- `currentValue` — nullable
- `metadata` — JSON, dowolny kontekst (np. nazwa pola, powód)

## Aktor i czas

- `userId` — int nullable (brak FK, zmiany systemowe = null)
- `source` — `"user"` / `"system"` / `"import"`
- `createdAt` — timestamp automatyczny

## Integracja — przez EventBus (jednostronny coupling)

- Handlery domenowe emitują zdarzenia domenowe (nie wiedzą o ActivityLog)
- ActivityLog nasłuchuje na zdarzenia przez własne listenery
- Decyzja "co logować" należy wyłącznie do modułu ActivityLog

## Proponowana tabela `activity_log`

| kolumna | typ |
|---|---|
| id | int PK |
| entity_type | varchar(64) |
| entity_id | int |
| event_type | varchar(64) |
| previous_value | text nullable |
| current_value | text nullable |
| metadata | json nullable |
| user_id | int nullable (brak FK) |
| source | varchar(32) |
| created_at | datetime |

Indeksy: `(entity_type, entity_id)`, `created_at`.

## Migracja istniejących danych

- `StatusLog` → `entity_type="production"`, `event_type="status_changed"`
- `TaskStatusLog` → `entity_type="task"`, `event_type="status_changed"`

## Poza zakresem MVP

- Brak API do odczytu (na start wystarczy widok w bazie)
- Brak indeksowania pełnotekstowego
- Brak retencji / TTL
- Nie jest event sourcing — log audytowy, nie źródło prawdy
