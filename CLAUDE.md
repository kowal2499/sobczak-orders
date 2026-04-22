# Project Guidelines — Sobczak Orders App

## Business Context

Sobczak Orders is a production order management system. Key concepts:

- **Agreement** (aka Order): Main business document
- **AgreementLine** (aka OrderLine): Single product in an order (usually one per Agreement). Central entity with broad relationships. Has:
  - `confirmedDate`: delivery date confirmed to the customer
  - `factor`: parameter determining workload for a product
- **Production**: Production tasks for AgreementLine directed to production departments
- **Production departments** (identified by slug):
  - Klejenie (dpt01), CNC (dpt02), Szlifowanie (dpt03), Lakierowanie (dpt04), Pakowanie (dpt05)
- **Customer**: Order recipient
- **WorkConfiguration**: Work time and holiday configuration

## Tech Stack

- **Backend**: Symfony 5.4 (PHP 8.1), Twig templates, `src/` (excluding `assets/`)
- **Frontend**: Vue 2.7 components communicating via axios HTTP, `assets/` directory
- **JS Bundler**: symfony/webpack-encore v4
- **Dev environment**: Docker Compose (`php-apache` and `mysql` containers)

## Naming Conventions

- **PHP/Symfony**: camelCase for variables, methods, properties
- **JavaScript/Vue**: camelCase for variables, methods, properties, component names
- **API**: camelCase in requests and responses (JSON)
- **Database**: snake_case for table and column names (Doctrine converts automatically)

## Code Organisation

- Code is grouped in modules: `src/Module/[ModuleName]/`
- Each module has its own controllers, services, entities, repositories
- Module config in `module.yaml` (name, permissions), autowiring in `config.yaml`, routing in `routes.yaml`
- Legacy code outside `Module/` (in `src/Controller`, `src/Entity`, `src/Repository`) should eventually be moved into modules
- **Clear Symfony cache after any significant API change**

## Architecture Pattern: CQRS

Flow: **Request → Controller → Command/Query → Handler → Response**

- **Commands** do not return values — they execute actions and change state
- **Queries** return values — they fetch data without modifying state
- Use `App\System\CommandBus`, `App\System\EventBus`, `App\System\QueryBus`

### Typical implementation steps for a new feature

1. **Controller** — validate input, create command, handle errors
2. **Command** — readonly properties with Symfony Validator attributes
3. **Handler** — business logic delegated to helper methods
4. **End2End test** — test happy path with database verification

### Controller rules

- Controllers are thin — delegate logic to services via DI
- Validate input, dispatch commands/queries, format responses
- **Never use `addFlash()` in API controllers** — return messages as JSON
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
- Entities are clean — no business logic (only simple helper methods)
- Entities live in `src/Module/[ModuleName]/Entity/`

### Entity inheritance

- `BaseTask` (`App\Module\Task\Entity\BaseTask`) — Doctrine Mapped Superclass (no own table)
- Contains shared fields: `dateStart`, `dateEnd`, `title`, `description`, `isStartDelayed`, `isCompleted`, `completedAt`, `createdAt`, `updatedAt`
- Fields in `BaseTask` are `protected` so subclasses can access them
- `Task` and `Production` both extend `BaseTask`

### Entity relationships

```
Agreement (1) ----< (N) AgreementLine (N) >---- (1) Product
    |                        |
    v                        v
Customer (1)           Production (N) [departmentSlug dpt01-dpt05]
```

### AgreementLine Read Model

- `App\Module\AgreementLine\Entity\AgreementLineRM` — aggregates data from related entities
- Updated via `App\Module\AgreementLine\Command\UpdateAgreementLineRM` after any change to AgreementLine or related entities
- Always keep the read model up to date when modifying data it contains

## Validation

- **Backend**: Symfony Validator in controllers (and on Command attributes — preferred pattern going forward)
- **Frontend**: VeeValidate for client-side form validation

## Authorisation

Custom permission system (not Symfony Security Voters):

```php
#[IsGranted('module.action')]
```

Examples: `orders.create`, `production.view`, `production.edit`, `work-configuration.capacity`

### Customer ownership filtering (ROLE_CUSTOMER)

Users can have assigned customers (`User::getCustomers()`). If a user has `ROLE_CUSTOMER`, endpoints returning AgreementLine data must filter results to only show lines belonging to their customers. This applies to **display data only** — aggregate values (e.g. capacity totals) must still be calculated company-wide.

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

## Modules

### 1. Orders (Agreements)
- Statuses: DRAFT, WAITING, MANUFACTURING, COMPLETED, ARCHIVED
- One Agreement contains many AgreementLines

### 2. Production
- Production tasks for departments (dpt01–dpt05)
- Extends `BaseTask`
- Task taskStatuses: PENDING, IN_PROGRESS, COMPLETED
- Contains only production tasks — non-standard tasks belong to the Task module

### 3. Task (Custom tasks)
- Routes: `/tasks` (POST, PUT, DELETE)
- Extends `BaseTask`
- **TaskTypeEnum**: `task_custom`, `task_confirm_realization_date`
- **TaskStatusEnum**: AWAITS=10, PENDING=11, COMPLETED=12
- `dateStart`, `dateEnd` — nullable (optional)
- `owner` — nullable; if set, only owner can edit/delete
- `isDeleted` — soft delete flag; `TaskRepository.find()` filters deleted tasks automatically
- Included in `AgreementLineRM` as the `tasks` field (JSON)
- Date validation: `dateEnd >= dateStart` only when both dates are provided

### 4. Customers, Products
- Standard CRUD, linked to Agreements/AgreementLines

### 5. WorkConfiguration
- Daily capacity and schedule (holidays, days off)
- Used for production planning

### 6. Reports
- Production reports, calendar reports, order statistics, dashboards

## Testing

### General rules

- **Unit tests** — business logic and edge cases
- **End2End tests** — controllers and database operations
- Use custom helpers for fixtures (auth, login, grants)
- Wrap E2E tests in transactions, rolled back after each test
- Run tests inside Docker container

```bash
# Run an End2End test
cd /home/romek/projects/sobczak-app && docker compose exec php-apache php vendor/bin/phpunit tests/End2End/Modules/WorkConfiguration/WorkCapacityControllerTest.php
```

```php
// Example: create user with roles/grants in E2E test
$user   = $this->createUser([], [], ['work-configuration.capacity']);
$client = $this->login($user);
```

### Folder structure

- `tests/Unit/` — unit tests (use for all new unit tests)
- `tests/End2End/` — integration/end-to-end tests (use for all new E2E tests)
- `tests/_toverify` — tests pending verification, do not run or reference
- `tests/Service/` — legacy unit tests (to be moved to `tests/Unit/Service/`)
- `tests/Reports/Production/Integration/` — legacy integration tests (to be moved to `tests/End2End/Modules/Reports/`)
- `tests/Utilities/` — test helpers (not tests, stays here)

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

### State & routing

- No Vue Router — navigation via Twig views (full page reload)
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
