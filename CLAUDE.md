# Project Guidelines - Sobczak Orders App

## Business Context

Sobczak Orders is a production order management system for stairs manufacturing company. Key concepts:

- **Agreement** (aka Order): Main business document, represents a single order.
- **AgreementLine** (aka OrderLine): Single product in an order (usually one per Agreement). Central entity with broad 
relationships. Has:
  - `confirmedDate`: delivery date proposed by customer and confirmed by factory
  - `factor`: parameter determining workload for a product
- **Production**: Production tasks for AgreementLine directed to production departments
- **Production departments** (identified by slug):
  - Klejenie (dpt01), CNC (dpt02), Szlifowanie (dpt03), Lakierowanie (dpt04), Pakowanie (dpt05), INTOREX (dpt06)
- **Task**: Other tasks for AgreementLine, optionally directed to a specific user
- **Factor**: Parameter determining workload for a product, used for workloads measurement and also serves as bonus 
and penalty basis for employees. 
- **Customer**: Order recipient
- **WorkConfiguration**: Work time and holiday configuration

## General purpose of the project
- Track orders
- Track factors and workloads
- Employees' bonuses and penalties tracker
- Schedule production tasks
- Analyze production capacity and its usage
- Estimate order delivery dates according to capacity usage

## Tech Stack

- **Backend**: Symfony 5.4 (PHP 8.1), Twig templates, `src/` (excluding `assets/`)
- **Frontend**: Vue 2.7 components communicating via axios HTTP, `assets/` directory
- **JS Bundler**: symfony/webpack-encore v4
- **Dev environment**: Docker Compose (`php-apache` and `mysql` containers)

## Commands (Makefile)

```bash
make up        # uruchom kontenery Docker (detached)
make down      # zatrzymaj kontenery
make dev       # up + npm run watch (codzienny start)
make watch     # tylko webpack watcher (npm run watch)
make check     # composer check w kontenerze (cs-fix + phpstan)
make test      # phpunit w kontenerze; make test F=tests/End2End/...
make cc        # cache:clear (dev + test)
make bash      # shell w kontenerze php-apache
make pull-db   # pobierz bazę z produkcji

make api-token E=<email> NAME="<nazwa>" [TTL=90]  # wydaj token API (TTL=0 -> bezterminowy)
make api-tokens [E=<email>]                       # lista wydanych tokenów
make api-token-revoke ID=<id>                     # unieważnij token
```

## Naming Conventions

- **PHP/Symfony**: camelCase for variables, methods, properties
- **JavaScript/Vue**: camelCase for variables, methods, properties, component names
- **API**: camelCase in requests and responses (JSON)
- **Database**: snake_case for table and column names (Doctrine converts automatically)

## Code Style

- **Never use the em dash (U+2014) anywhere in the project** - not in code, comments, templates,
  translations or Markdown. Use a plain hyphen `-` instead. Applies to PHP, JS/Vue, Twig, YAML and
  Markdown alike.
- **Minimise comments.** Write one only where it is genuinely needed: a non-obvious reason behind a
  decision, a workaround for someone else's bug, an environment trap. Do not restate in a comment
  what the method name already says, and never add a docblock just for the sake of having one.
- Type annotations PHPStan and the IDE rely on (`@return Foo[]`, `@extends ServiceEntityRepository<Foo>`,
  `@param array<string, string>`) are not comments in this sense - keep them.

## Git

- **Never commit or push without explicit consent** - not even when the task is finished and the
  tests pass. Leave the changes in the working tree and report what was done; commit only when
  asked to. Do not run `git add` pre-emptively either.

## Code Organisation

- Code is grouped in modules: `src/Module/[ModuleName]/`
- Each module has its own controllers, services, entities, repositories
- Module config in `module.yaml` (name, permissions), autowiring in `config.yaml`, routing in `routes.yaml`
- Legacy code outside `Module/` (in `src/Controller`, `src/Entity`, `src/Repository`) should eventually be moved into modules
- **Clear Symfony cache after any significant API change**

## Architecture Pattern: CQRS

Flow: **Request → Controller → Command/Query → Handler → Response**

- **Commands** do not return values - they execute actions and change state
- **Queries** return values - they fetch data without modifying state
- Use `App\System\CommandBus`, `App\System\EventBus`, `App\System\QueryBus`

### Typical implementation steps for a new feature

1. **Controller** - validate input, create command, handle errors
2. **Command** - readonly properties with Symfony Validator attributes
3. **Handler** - business logic delegated to helper methods
4. **End2End test** - test happy path with database verification

### Controller rules

- Controllers are thin - delegate logic to services via DI
- Validate input, dispatch commands/queries, format responses
- **Never use `addFlash()` in API controllers** - return messages as JSON
- Use `#[IsGranted('module.action')]` for authorisation

### Command example

```php
class CreateAgreementCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $customerId,
        #[Assert\NotBlank]
        public readonly string $orderNumber,
        #[Assert\NotBlank]
        #[Assert\Type('array')]
        #[Assert\Count(min: 1)]
        public readonly array $products,
        #[Assert\NotNull]
        public readonly int $userId,
        #[Assert\Type('array')]
        public readonly array $attachments = [],
    ) {}
}
```

### Handler example (with helper method delegation)

```php
public function __invoke(CreateAgreementCommand $command): void
{
    $this->em->beginTransaction();
    try {
        $customer  = $this->getCustomer($command->customerId);
        $agreement = $this->createAgreement($command, $customer);
        $lines     = $this->createAgreementLines($command, $agreement);
        $this->handleAttachments($command, $agreement);
        $this->em->flush();
        $this->assignTags($lines, $command->userId);
        $this->createFactors($agreement);
        $this->emitEvents($agreement);
        $this->em->commit();
    } catch (\Exception $e) {
        $this->em->rollback();
        throw $e;
    }
}
```

## Doctrine / Entities

- Use **PHP Attributes** (not annotations) for entity mapping
- Entities are clean - no business logic (only simple helper methods)
- Entities live in `src/Module/[ModuleName]/Entity/`
- **Each module with entities must be registered in `config/packages/doctrine.yaml` under `orm.mappings`** (despite `auto_mapping: true`, mappings are declared explicitly per module). After adding, run `bin/console cache:clear --env=test` before tests will pick it up.

### Entity inheritance

- `BaseTask` (`App\Module\Task\Entity\BaseTask`) - Doctrine Mapped Superclass (no own table)
- Contains shared fields: `dateStart`, `dateEnd`, `title`, `description`, `isStartDelayed`, `isCompleted`, `completedAt`, `createdAt`, `updatedAt`
- Fields in `BaseTask` are `protected` so subclasses can access them
- `Task` and `Production` both extend `BaseTask`

### Entity relationships

```
Agreement (1) ----< (N) AgreementLine (N) >---- (1) Product
    |                        |
    v                        v
Customer (1)           Production (N) [departmentSlug dpt01-dpt06]
```

### AgreementLine Read Model

- `App\Module\AgreementLine\Entity\AgreementLineRM` - aggregates data from related entities
- Updated via `App\Module\AgreementLine\Command\UpdateAgreementLineRM` after any change to AgreementLine or related entities
- Always keep the read model up to date when modifying data it contains

## Validation

- **Backend**: Symfony Validator in controllers (and on Command attributes - preferred pattern going forward)
- **Frontend**: VeeValidate for client-side form validation

## Authorisation

Custom permission system (not Symfony Security Voters):

```php
#[IsGranted('module.action')]
```

Examples: `orders.create`, `production.view`, `production.edit`, `work-configuration.capacity`

### Customer ownership filtering (ROLE_CUSTOMER)

Users can have assigned customers (`User::getCustomers()`). If a user has `ROLE_CUSTOMER`, endpoints returning AgreementLine data must filter results to only show lines belonging to their customers. This applies to **display data only** - aggregate values (e.g. capacity totals) must still be calculated company-wide.

Established pattern used in `ProductionRepository`, `AgreementLineRepository`, `DoctrineProductionFinishedRepository`, `ScheduleCapacityService`:

```php
if ($this->security->isGranted('ROLE_CUSTOMER')) {
    $customerIds = array_filter(
        $this->security->getUser()->getCustomers()
            ->map(fn ($c) => $c?->getId())
            ->toArray()
    );
    // filter data by $customerIds
}
```

`AgreementLineRMRepository` supports this via the `ownedBy` search key (accepts a `User` object).

## Data Visibility Rules

The application serves a single company with multiple internal users. There is no multi-tenancy. Visibility is controlled by two orthogonal mechanisms: Symfony roles and module-level grants.

### ROLE_CUSTOMER - customer-scoped visibility

Users with `ROLE_CUSTOMER` may only see data belonging to their assigned customers (relation `user_customer`). This affects:

- **Customer** listings
- **Agreement** listings
- **AgreementLine** listings (and any view derived from them)

`User::getCustomers()` returns the assigned customers. Filter by their IDs wherever these entities are queried. Aggregate/capacity values must still be calculated company-wide (not filtered).

### ROLE_PRODUCTION - production visibility gate

Users **without** `ROLE_PRODUCTION` must not see any production data:

- The `Production` entity listings must be hidden entirely
- All views, API endpoints, and UI sections related to production departments must be inaccessible

### Production department visibility (grants)

Users **with** `ROLE_PRODUCTION` are further restricted by module grants controlling which departments they can see:

| Grant | Department |
|---|---|
| `production.show.gluing` | dpt01 - Klejenie |
| `production.show.cnc` | dpt02 - CNC |
| `production.show.grinding` | dpt03 - Szlifowanie |
| `production.show.laquering` | dpt04 - Lakierowanie |
| `production.show.packing` | dpt05 - Pakowanie |
| `production.show.intorex` | dpt06 - INTOREX |

When returning production data, filter rows to only departments for which the user holds the corresponding grant. Check grants with `#[IsGranted('production.show.gluing')]` in controllers or `this.$user.can('production.show.gluing')` in Vue.

## Modules

### 1. Orders (Agreements)
- Statuses: DRAFT, WAITING, MANUFACTURING, COMPLETED, ARCHIVED
- One Agreement contains many AgreementLines

### 2. Production
- Production tasks for departments (dpt01–dpt06)
- Extends `BaseTask`
- Task taskStatuses: PENDING, IN_PROGRESS, COMPLETED
- Contains only production tasks - non-standard tasks belong to the Task module

### 3. Task (Custom tasks)
- Routes: `/tasks` (POST, PUT, DELETE)
- Extends `BaseTask`
- **TaskTypeEnum**: `task_custom`, `task_confirm_realization_date`
- **TaskStatusEnum**: AWAITS=10, PENDING=11, COMPLETED=12
- `dateStart`, `dateEnd` - nullable (optional)
- `owner` - nullable; if set, only owner can edit/delete
- `isDeleted` - soft delete flag; `TaskRepository.find()` filters deleted tasks automatically
- Included in `AgreementLineRM` as the `tasks` field (JSON)
- Date validation: `dateEnd >= dateStart` only when both dates are provided

### 4. Customers, Products
- Standard CRUD, linked to Agreements/AgreementLines

### 5. WorkConfiguration
- Daily capacity and schedule (holidays, days off)
- Used for production planning

### 6. Reports
- Production reports, calendar reports, order statistics, dashboards

#### Dashboard metrics (strategy pattern)

Each dashboard tile is a `MetricStrategyInterface` implementation resolved by `DashboardMetricProvider`.
Metrics returning one record per production extend `AbstractProductionRecordStrategy`, which owns the
whole loop (fetch lines from the read model, skip non-default departments and ghosts, map to
`ProductionReportRecordDTO`) and exposes template-method hooks:

| hook | meaning | default |
|---|---|---|
| `buildSearch()` | criteria for `AgreementLineRMRepository::search()` | abstract |
| `qualifies()` | does the production count in this range | abstract |
| `factorsOf()` | which factor of `ProductionRM` to report | abstract |
| `isOnTime()` | was it finished within its planned window | `true` |
| `emitsOutOfRange()` | also return non-qualifying productions, without a factor | `false` |
| `appliedTolerance()` | bonus granted only thanks to the tolerance window | `false` |
| `timelinessWorkingDays()` | deviation from the window in working days (+ late, - early) | `0` |

`compute()` takes `array $options = []` - the documented extension point for per-metric parameters
(currently only `toleranceDays`). PHP requires matching signatures, so every implementation declares it.

**Records "out of range"** are emitted only for lines that already have at least one qualifying
production (`if (!$lineRecords) continue;`), so the payload grows by at most five extra records per
line. They carry `inRange: false` and `factors: null`; the front skips them in every sum
(`mapDetails`, `aggregateByDepartment`), and uses them only to explain an empty cell in the popover.

#### The two completed-tasks reports

Both settle work by the **actual completion date** (`completedAt` inside the report range), not by the
planned window, and both use the same strategy base and the same cell components. Their `buildSearch()`
and `qualifies()` are identical - the difference is what earns the factor.

| | `departments_bonus` | `departments_bonus_on_time` |
|---|---|---|
| tile | "Ukończone zadania produkcyjne" | "Ukończone zadania produkcyjne (w terminie)" |
| endpoint | `/reports/production/production-tasks-completion-summary` | `/reports/production/production-tasks-on-time-summary` |
| grant | `PRIVILEGES.CAN_DASHBOARD_METRICS_VIEW` | `reports.dashboard:on-time-bonus` |
| factor source | `ProductionRM::getFactorBonus()` | `getFactorBonusCompletedTasks() ?? getFactorBonus()` |
| deadline | ignored, shown only for information | zeroes the factor when missed |
| adjustments | none | `FACTOR_ADJUSTMENT_BONUS_COMPLETED_TASKS` |

**On-time metric specifics:**
- Acceptance window is `[dateStart, dateEnd]` widened by a **tolerance in working days** on both sides
  (`WorkingDayCalculator::shift()`). The binding value is `DEFAULT_TOLERANCE_DAYS` in the strategy,
  mirrored by a constant in `OnTimeToleranceInput.vue`; the dashboard input only previews another
  value (`?tolerance=`) and is deliberately not persisted, so two people always settle the same way.
- The lower bound is intentional (anti-gaming), not an oversight - finishing early does not speed up
  the order, because the customer pays once every department is done.
- Bonus adjustments saved from its popover use the full cascade (`AGREEMENT_LINE` ->
  `FACTOR_ADJUSTMENT_RATIO` -> `FACTOR_ADJUSTMENT_BONUS` -> `FACTOR_ADJUSTMENT_BONUS_COMPLETED_TASKS`)
  and are visible **only in this report**, because they land in a dedicated `AgreementLineRM` field.
- A record outside the window has its factor zeroed on the front, so "grant the bonus despite the
  delay" cannot be built as a value adjustment - it needs a separate flag.

#### Factor cell components (`ProductionMetric/components/FactorCell/`)

`FactorCell.vue` renders the value and owns the popover state; its **content is a scoped slot**, so
each report composes only the sections it needs. Do not reintroduce boolean "mode" props here.

- `OnTimeCell.vue` - composition for the bonus report (timeliness + breakdown + adjustment form)
- `PlainFactorCell.vue` - composition for the plain report (dates + window adherence + breakdown)
- `FactorBreakdown.vue`, `TimelinessSummary.vue`, `WindowAdherence.vue`, `OutOfRangeSummary.vue`,
  `BonusAdjustmentForm.vue`, `ProductionDates.vue` - individual sections
- Two presentational props remain: `trigger` (`click`/`hover`) and `tone` (`timeliness`/`value`).
  Red means "the bonus was lost", so it must never appear in a report where the deadline pays nothing.

### 7. ActivityLog
- Central, append-only journal of business events with structured key/value fields
- Entities: `ActivityLog` (`activity_log`), `LogField` (`activity_log_field`)
- **ValueObjects**: `LogLevel` (PSR-3: DEBUG…EMERGENCY, default `INFO`), `LogPriority` (`normal`, `high`)
- Append-only - no setters for `type`, `content`, `user` after construction; `addLogField($name, $value)` is **idempotent by name** (first value wins); DB unique index `(activity_log_id, name)` enforces it
- Write side: `AddActivityLogCommand` + `AddActivityLogCommandHandler`; supports impersonation via `contextData['impersonateUserId']` (overrides author; key is stripped from persisted fields); dispatches `ActivityLogWasAddedEvent` after `$em->commit()`
- Read side: `GetPaginatedLogsQuery` (filter by `type`, list of `FieldFilter`, optional `filterBy` to narrow returned fields), `CountLogsByFieldQuery` (grouped count); both use ORM QueryBuilder + Doctrine `Paginator`; helper `Query/Helper/LogFinder` keeps JOIN logic shared
- REST: `POST /log/{type}` (grant `activity-log.create`), `GET /log[/{type}]` (grant `activity-log.read`), `GET /log/{type}/count-by/{groupBy}` (grant `activity-log.read`); GET endpoints accept filter payload via JSON body
- Monolog integration: dedicated `activity_log` channel routed to `App\Module\ActivityLog\Logger\ActivityLogMonologHandler` (in `config/packages/monolog.yaml`); producers can call `$logger->info($message, ['type' => 'agreement.created', ...$fields])` instead of building the command manually
- **Channel semantics - opt-in, not catch-all**: only logs sent on the `activity_log` channel reach the DB. The handler has a whitelist `channels: [activity_log]`, and `main`/`console` handlers have `!activity_log` so the same record is not also written to files/console. To log to the DB, inject the channel-scoped logger:
  ```php
  public function __construct(
      #[Autowire(service: 'monolog.logger.activity_log')]
      private LoggerInterface $activityLogger,
  ) {}
  ```
  A plain `$this->logger->info(...)` (default `app` channel) does **not** persist anything to `activity_log` - it goes to the regular file log as before.
- Author is an integer FK to `User` (`user_id`, nullable for system-triggered logs)
- **Response shape** is owned by `Query/Helper/LogResponseMapper` - every endpoint returning logs
  goes through it. Ordering is `createdAt DESC, id DESC`; the id tiebreaker is load-bearing,
  entries written in the same second would otherwise shuffle and paging could repeat or drop rows
- Any producer whose `content` key has no entry in `translations/activity_log.*.yml` leaks the raw
  key to the UI. Params are substituted as `%name%`, and a `null` param renders as the word "null"
  (`translateContent()` json-encodes non-scalars) - give optional params a default, or use two
  message keys

### 8. BonusSettlement
- Monthly settlement of production bonuses. `BonusPeriod` (one per year+month) with
  `BonusPeriodEntry` rows, one per employee+department. Spec: `spec/bonus-settlement.md`
- Input comes from the `departments_bonus_on_time` metric via `Service\DepartmentFactorsCalculator`,
  a **server-side twin of the front rule** in `DepartmentMetricMixin.aggregateByDepartment()`. A unit
  test reads the mixin file and fails if that rule changes - it is a guard, not a proof
- Department membership comes from grants via `GrantsResolver::getGrants($user)`, never
  `isGranted()` - the latter returns true for everything under `authorization.admin`, which would
  hand every administrator all six departments
- Values are **frozen on the row** (`userLabel`, `departmentLabel`, `factorsCalculated`), so a later
  rename or grant change does not rewrite a settled month
- Recalculation keeps adjustments, matching on user+department; a row whose user lost the department
  grant disappears together with its adjustment
- A `CLOSED` period is immutable. The guard sits in the handlers, not the controller, so the rule
  holds whatever calls them; reopening is the only way back in and is always journalled
- Staleness of an adjustment is `adjustedAgainst` (the input snapshotted when the adjustment was
  saved) compared with the current `factorsCalculated`, **not** a date comparison - a date rule lit
  up every adjusted row after any recalculation
- Grants: `bonus-settlement.view` (read + export), `bonus-settlement.manage` (everything that writes)
- Front: `assets/js-vue/src/modules/bonusSettlement/`. The directory is camelCase because `i18n.js`
  derives the translation namespace from the folder name; a hyphen would break `bonus_settlement.*`
- The adjustment cell is deliberately **not** the dashboard's `FactorCell` - that component describes
  a single production and hides its popover when `production` is null, which on the dashboard means
  an empty department cell
- Deployment needs `doctrine:migrations:migrate` **and** `app:module:register`, otherwise the grants
  never appear in the permission panel

## Testing

### General rules

- **Unit tests** - business logic and edge cases
- **End2End tests** - controllers and database operations
- Use custom helpers for fixtures (auth, login, grants)
- Wrap E2E tests in transactions, rolled back after each test
- Run tests inside Docker container

```bash
make test F=tests/End2End/Modules/WorkConfiguration/WorkCapacityControllerTest.php
```

```php
// Example: create user with roles/grants in E2E test
$user   = $this->createUser([], [], ['work-configuration.capacity']);
$client = $this->login($user);
```

### Folder structure

- `tests/Unit/` - unit tests (use for all new unit tests)
- `tests/End2End/` - integration/end-to-end tests (use for all new E2E tests)
- `tests/_toverify` - tests pending verification, do not run or reference
- `tests/Service/` - legacy unit tests (to be moved to `tests/Unit/Service/`)
- `tests/Reports/Production/Integration/` - legacy integration tests (to be moved to `tests/End2End/Modules/Reports/`)
- `tests/Utilities/` - test helpers (not tests, stays here)

> Always use `tests/Unit/` or `tests/End2End/` for new tests.

### End2End test structure

```php
protected function setUp(): void
{
    parent::setUp();
    $this->getManager()->beginTransaction();
}

protected function tearDown(): void
{
    $this->getManager()->rollback();
    parent::tearDown();
}

public function testShouldCreateAgreement(): void
{
    // Given
    $user     = $this->createUser();
    $client   = $this->login($user);
    $customer = $this->factory->make(Customer::class);
    $product  = $this->factory->make(Product::class);
    $this->getManager()->flush();
    $this->getManager()->clear();

    // When
    $client->request('POST', '/orders/save', [...]);

    // Then
    $this->assertEquals(201, $client->getResponse()->getStatusCode());
    $this->getManager()->clear();
    $order = $this->agreementRepository->findOneBy(['orderNumber' => '12345']);
    $this->assertNotNull($order);
}
```

## Frontend

### Structure

- Twig templates return simple custom HTML tags; Vue components handle rendering
- Global components registered in `assets/js-vue/src/components/root-components.js`
- Module-specific components: `assets/js-vue/src/modules/[ModuleName]/components/`
- Shared components: `assets/js-vue/src/components/base/`

### Page layout (SectionBlock)

Views are composed of **`SectionBlock`** panels (`assets/js-vue/src/components/base/SectionBlock.vue`) - a white rounded card with border and subtle shadow. **Do not use `CollapsibleCard` for view scaffolding** (it was removed from the dashboard for this reason). Prefer flat `SectionBlock` sections.

Standard structure: a title section followed by one or more content sections, each wrapped in its own `SectionBlock`:

```vue
<div>
    <SectionBlock class="d-flex align-items-center justify-content-between flex-wrap">
        <SectionBlockTitle :title="$t('module.title')" :breadcrumbs="breadcrumbs" />
        <!-- right side: filters / action buttons -->
    </SectionBlock>

    <SectionBlock class="section-gap">
        <!-- content -->
    </SectionBlock>
</div>
```

- Inner layout is the caller's responsibility - pass utility classes on the `SectionBlock` itself (e.g. `d-flex justify-content-between`); they merge onto the root.
- Use `class="section-gap"` (`margin-top: 2rem`, defined locally per view) to separate stacked sections.

**`SectionBlockTitle`** (`components/base/SectionBlockTitle.vue`) holds the view title and an optional breadcrumb beneath it, with shared muted breadcrumb styling. Breadcrumbs render **inside the title section**, not in the Twig topbar - when migrating a view, remove its `{% block breadcrumbs %}` from the Twig template. Pass breadcrumbs as a structured prop, last entry is the active (non-linked) crumb; an `icon` (FontAwesome name, must be registered in `app-vue.js`) replaces the label and keeps `label` as its aria-label:

```js
breadcrumbs() {
    return [
        { icon: 'home', href: '/', label: this.$t('module.breadcrumb.home') },
        { label: this.$t('module.breadcrumb.section') },
        { label: this.$t('module.breadcrumb.current') },
    ]
}
```

Reference implementations: `modules/dashboard/Dashboard.vue` (title only) and `modules/reports/DifferencesReport/index.vue` (title + breadcrumb).

### State & routing

- No Vue Router - navigation via Twig views (full page reload)
- Vuex used in limited scope (mainly global data)

### Translations (frontend)

- Supports Polish and English via vue-i18n
- Module locale files: `assets/js-vue/src/modules/[ModuleName]/locale/[lang].json`
- Global locale files: `assets/js-vue/src/locale/[lang].json`
- Usage: `this.$t('[moduleName].key')` or `this.$t('key')`

### API communication

Use repository pattern per module:

```javascript
// assets/js-vue/src/modules/[module]/repository/[name]Repository.js
import axios from "axios";

export function fetchProductionTasks(filters = {}) {
    return axios.get('/api/production/tasks', { params: filters });
}
export function createProductionTask(payload) {
    return axios.post('/api/production/tasks', payload);
}
export function updateProductionTask(id, payload) {
    return axios.put(`/api/production/tasks/${id}`, payload);
}
export function deleteProductionTask(id) {
    return axios.delete(`/api/production/tasks/${id}`);
}
```

### Modal pattern (with VeeValidate)

- `ValidationObserver` wraps the modal; validate with `this.$refs.form.validate()`
- `ValidationProvider` wraps each field with its validation rules
- Use `v-model` with `formProxy` pattern for two-way binding in sub-form components
- Modal slots: `#open-action`, `#modal-footer`, `#default`
- Report errors via `EventBus.$emit('message', { type: 'error', content: ... })`

### Sidebar pattern

```vue
<Sidebar title="..." sidebar-class="size-100 size-lg-75">
    <template #sidebar-action="{ open }">
        <button @click="open">Open</button>
    </template>
    <template #sidebar-content="{ close }">
        <MyForm @close="close" />
    </template>
</Sidebar>
```

- Check permissions with `this.$user.can('module.action')`

## Backend translations

```yaml
# translations/[module].pl.yml
production:
  list:
    title: "Lista zadań produkcyjnych"
  status:
    pending: "Oczekujące"
    in_progress: "W trakcie"
    completed: "Zakończone"
```

Translation key namespaces: `agreements.*`, `production.*`, `work_configuration.*`, `dashboard.*`
