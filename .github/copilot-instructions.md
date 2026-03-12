# Wskazówki przy pracy z projektem

## Kontekst biznesowy

Aplikacja Sobczak Orders to system do zarządzania zamówieniami produkcyjnymi. Kluczowe pojęcia:

- **Agreement** (inna nazwa: Order) (Zamówienie): Główny dokument biznesowy
- **AgreementLine** (inna nazwa: OrderLine) (Pozycja zamówienia): Pojedynczy produkt w zamówieniu, 
zwykle tylko jedna dla danego Agreement. Kluczowa pozycja w całym systemie o szerokim zakresie powiązań. AgreementLine posiada:
  - **confirmedDate**: datę realizacji potwierdzoną klientowi
  - **factor** (współczynnik): parametr określający pracochłonność danego produktu
- **Production** (Produkcja): Zadania produkcyjne dla pozycji umowy kierowane do działu produkcyjnego
- **Działy produkcyjne** (departments): Główne działy odpowiadające za produkcję, identyfikowane przez slug
  - Klejenie (dpt01)
  - CNC (dpt02)
  - Szlifowanie (dpt03)
  - Lakierowanie (dpt04)
  - Pakowanie (dpt05)
- **Customer** (Klient): Klient, odbiorca zamówienia 
- **WorkConfiguration**: Konfiguracja czasu pracy i dni wolnych

## Kontekst techniczny

Aplikacja jest napisana w Symfony 5.4 (PHP 8.1). Widoki frontendu zwracane są przez szablony twig. 
W dalszej kolejności generowanie widoku przejmują komponenty Vue 2.7 które komunikują się za api poprzez żądania http (axios).
JS bundler to symfony/webpack-encore w wersji 4.
Kod frontendu jest w app/assets
Kod backendu jest w app (z wyłączeniem assets)

### Konwencje nazewnictwa
- **Backend (PHP/Symfony)**: camelCase dla zmiennych, metod i właściwości
- **Frontend (JavaScript/Vue)**: camelCase dla zmiennych, metod, właściwości i nazw komponentów
- **API**: camelCase w requestach i response (JSON)
- **Baza danych**: snake_case dla nazw tabel i kolumn (Doctrine konwertuje automatycznie)

### Walidacja danych
- **Backend**: Walidacja danych odbywa się w kontrolerach przy użyciu Symfony Validator
- **Frontend**: W formularzach preferowane jest użycie **VeeValidate** do walidacji po stronie klienta

### Doctrine
- Używamy **PHP Attributes** (nie annotations) do mapowania encji
- Encje są czyste, bez logiki biznesowej (poza prostymi metodami pomocniczymi)

## Środowisko deweloperskie
docker compose, kontenery php-apache i mysql

### Testy - ogólne zasady
- Testy jednostkowe (Unit) do testowania logki biznesowej i warunków brzegowych
- Testy integracyjne (End2End) głównie do testowania kontrolerów i operacji bazodanowych
- Używamy własnych helperów do tworzenia fixtures (np autoryzacja, logowanie, granty)
- Testy End2End wrapujemy w transakcje, które są rollbackowane po teście
- Testy uruchamiamy w kontenerze Dockera. Projekt zawiera testy jednostkowe (Unit) oraz testy integracyjne (End2End).

```php
// przykład utworzenia użytkownika z rolami, grantami
// w teście end2end
$user = $this->createUser([], [], ['work-configuration.capacity']);
$client = $this->login($user);
```

```bash
# uruchomienie testu jednostkowego
cd /home/romek/projects/sobczak-app && docker compose exec php-apache php vendor/bin/phpunit tests/End2End/Modules/WorkConfiguration/WorkCapacityControllerTest.php
```

### Struktura folderów testowych

**Docelowa struktura (wymagana dla nowych testów):**
- `tests/Unit/` - testy jednostkowe
- `tests/End2End/` - testy integracyjne/end-to-end

**Aktualne wyjątki (legacy, do refaktoryzacji):**
- `tests/Service/` - stare testy jednostkowe (docelowo należy przenieść do `tests/Unit/Service/`)
- `tests/Reports/Production/Integration/` - stare testy integracyjne (docelowo należy przenieść do `tests/End2End/Modules/Reports/`)
- `tests/Utilities/` - helpery i narzędzia testowe (nie są testami, pozostają w tej lokalizacji)

> ⚠️ **Uwaga:** Przy tworzeniu nowych testów ZAWSZE używaj struktury docelowej (`tests/Unit/` lub `tests/End2End/`). Wyjątki istnieją tylko ze względów historycznych.

## Organizacja kodu api
- kod grupujemy w modułach (src/Modules/[ModuleName])
- każdy moduł ma swoje kontrolery, serwisy, encje, repository
- konfiguracja modułu w module.yaml, zawiera m.in. nazwę, uprawnienia
- konfiguracja autowiringu w config.yaml w katalogu modułu
- konfiguracja routing w routes.yaml w katalogu modułu
- historyczny kod jest także poza folderem Module, w standardzie Symfony (src/Controller, src/Entity, src/Repository), docelowo powinien zostać przeniesiony do konkretnych modułów

## Moduły systemu

### 1. Orders (Agreements, Zamówienia)
- Tworzenie i zarządzanie zamówieniami
- Status: DRAFT, WAITING, MANUFACTURING, COMPLETED, ARCHIVED
- Zawiera wiele AgreementLine

### 2. Production (Produkcja)
- Zadania produkcyjne (tasks)
- Status tasks: PENDING, IN_PROGRESS, COMPLETED
- Logi statusów (StatusLog)
- Harmonogram produkcji

### 3. Customers (Klienci)
- Zarządzanie klientami
- Powiązani z zamówieniami

### 4. Products (Produkty)
- Katalog produktów
- Powiązane z AgreementLine

### 5. WorkConfiguration (Konfiguracja pracy)
- Capacity (Wydajność dzienna)
- Schedule (Harmonogram: dni wolne, święta)
- Używane do planowania produkcji

### 6. Reports (Raporty)
- Raporty produkcyjne
- Raporty kalendarzowe
- Statystyki zamówień
- Dashboardy

## Kontrolery
- dbamy o to, by kontrolery były cienkie, delegują logikę do serwisów za pośrednictwem dependency injection 
- walidują dane wejściowe i uruchamiają serwisy i delegują eventy, komendy lub queries (preferujemy CQRS)
- nie zawierają bezpośrednio logiki biznesowej, ale mogą zawierać logikę specyficzną dla API (np. formatowanie odpowiedzi)- **NIGDY nie używamy `addFlash()` w kontrolerach API** - komunikaty są zwracane w odpowiedzi JSON i obsługiwane przez frontend (Vue)

### Typowy flow API: Request → Controller → Command → Handler → Response

Standardowy proces implementacji nowej funkcjonalności w module:

1. **Kontroler** - walidacja danych, utworzenie komendy, obsługa błędów
2. **Komenda** - readonly properties z atrybutami walidacji Symfony Validator
3. **Handler** - logika biznesowa delegowana do dedykowanych metod pomocniczych
4. **Test End2End** - testowanie happy path kontrolera z weryfikacją w bazie danych

**Przykład kompletnej implementacji:**

#### 1. Komenda (Command)
```php
<?php

namespace App\Module\Agreement\Command;

use Symfony\Component\Validator\Constraints as Assert;

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
    ) {
    }
}
```

#### 2. Handler z delegacją do metod pomocniczych
```php
<?php

namespace App\Module\Agreement\CommandHandler;

use App\Entity\Agreement;
use App\Module\Agreement\Command\CreateAgreementCommand;
use Doctrine\ORM\EntityManagerInterface;

class CreateAgreementCommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private CustomerRepository $customerRepository,
        // ... inne zależności
    ) {
    }

    public function __invoke(CreateAgreementCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $customer = $this->getCustomer($command->customerId);
            $agreement = $this->createAgreement($command, $customer);
            $linesToTag = $this->createAgreementLines($command, $agreement);
            $this->handleAttachments($command, $agreement);

            $this->em->flush();

            $this->assignTags($linesToTag, $command->userId);
            $this->createFactors($agreement);
            $this->emitEvents($agreement);

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function getCustomer(int $customerId): Customer
    {
        // logika pobrania customera
    }

    private function createAgreement(CreateAgreementCommand $command, Customer $customer): Agreement
    {
        // logika utworzenia agreement
    }

    private function createAgreementLines(CreateAgreementCommand $command, Agreement $agreement): array
    {
        // logika utworzenia linii
    }

    // ... inne metody pomocnicze
}
```

#### 3. Kontroler z walidacją ręczną
```php
<?php

namespace App\Module\Agreement\Controller;

use App\Module\Agreement\Command\CreateAgreementCommand;
use App\System\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/orders')]
class AgreementController extends AbstractController
{
    public function __construct(
        private CommandBus $commandBus,
        private Security $security,
    ) {
    }

    #[Route('/save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = $request->request->all();

        // Parsowanie products jeśli przyszły jako JSON string
        if (false === is_array($data['products'] ?? null)) {
            $data['products'] = json_decode($data['products'], true);
        }

        // Walidacja danych wejściowych
        $customerId = (int) ($data['customerId'] ?? 0);
        $orderNumber = (string) ($data['orderNumber'] ?? '');
        $products = (array) ($data['products'] ?? []);

        if ($customerId <= 0) {
            return $this->json(['error' => 'Invalid customer ID'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($orderNumber)) {
            return $this->json(['error' => 'Order number is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($products)) {
            return $this->json(['error' => 'At least one product is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new CreateAgreementCommand(
                customerId: $customerId,
                orderNumber: $orderNumber,
                products: $products,
                userId: $this->security->getUser()->getId(),
            );

            $this->commandBus->dispatch($command);

            return new JsonResponse(['success' => true], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'An unexpected error occurred'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
```

#### 4. Test End2End - testowanie happy path
```php
<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\System\Test\ApiTestCase;

class AgreementControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        // inicjalizacja repozytoriów, factory
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateAgreement(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);
        $customer = $this->factory->make(Customer::class);
        $product = $this->factory->make(Product::class);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/orders/save', [
            'customerId' => $customer->getId(),
            'orderNumber' => '12345',
            'products' => [
                [
                    'productId' => $product->getId(),
                    'description' => 'Test product',
                    'requiredDate' => '2024-12-31',
                    'factor' => 1.5,
                ]
            ],
        ]);

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        
        // Verify in database
        $this->getManager()->clear();
        $order = $this->agreementRepository->findOneBy(['orderNumber' => '12345']);
        $this->assertNotNull($order);
        $this->assertEquals($customer->getId(), $order->getCustomer()->getId());
        
        // Verify read models, factors, tags, etc.
        // ...
    }
}
```

### Przykład pełnego flow API (Request → Controller → Service → Response)

**1. Request (JSON)**
```json
POST /api/production/tasks
{
  "agreementLineId": 123,
  "departmentSlug": "dpt02",
  "plannedDate": "2026-03-15"
}
```

**2. Controller**
```php
<?php

namespace App\Module\Production\Controller;

use App\Module\Production\Command\CreateProductionTask;
use App\System\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/production')]
class ProductionController extends AbstractController
{
    public function __construct(
        private CommandBus $commandBus
    ) {}

    #[Route('/tasks', methods: ['POST'])]
    #[IsGranted('production.create')]
    public function createTask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Walidacja danych wejściowych w kontrolerze
        if (!isset($data['agreementLineId']) || !is_numeric($data['agreementLineId'])) {
            return $this->json(['errors' => ['agreementLineId is required and must be numeric']], 400);
        }
        
        if (!isset($data['departmentSlug']) || !in_array($data['departmentSlug'], ['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'])) {
            return $this->json(['errors' => ['departmentSlug is required and must be valid']], 400);
        }
        
        if (!isset($data['plannedDate'])) {
            return $this->json(['errors' => ['plannedDate is required']], 400);
        }
        
        // Utworzenie komendy i delegacja do Command Handler
        $command = new CreateProductionTask(
            agreementLineId: (int) $data['agreementLineId'],
            departmentSlug: $data['departmentSlug'],
            plannedDate: $data['plannedDate']
        );
        
        $this->commandBus->dispatch($command);
        
        return $this->json([
            'success' => true,
            'message' => 'Production task created successfully'
        ], 201);
    }
}
```

> **Uwaga:** Powyższy przykład pokazuje aktualnie stosowany wzorzec ręcznej walidacji w kontrolerze. 
> Pożądanym (ale jeszcze niezaimplementowanym) wzorcem jest użycie Symfony Validator z atrybutami walidacji na komendach:
> 
> ```php
> // Pożądany wzorzec (do stosowania w przyszłości)
> public function __construct(
>     private CommandBus $commandBus,
>     private ValidatorInterface $validator
> ) {}
> 
> // ...
> $errors = $this->validator->validate($command);
> if (count($errors) > 0) {
>     return $this->json(['errors' => (string) $errors], 400);
> }
> ```

**3. Command**
```php
<?php

namespace App\Module\Production\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProductionTask
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly ?int $agreementLineId,
        
        #[Assert\NotBlank]
        #[Assert\Choice(['dpt01', 'dpt02', 'dpt03', 'dpt04', 'dpt05'])]
        public readonly ?string $departmentSlug,
        
        #[Assert\NotBlank]
        #[Assert\Date]
        public readonly ?string $plannedDate
    ) {}
}
```

**4. Command Handler (Service)**
```php
<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\CreateProductionTask;
use App\Module\Production\Entity\Production;
use App\Module\Production\Repository\ProductionRepository;
use App\Module\AgreementLine\Repository\AgreementLineRepository;
use Doctrine\ORM\EntityManagerInterface;

class CreateProductionTaskHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private AgreementLineRepository $agreementLineRepository,
        private ProductionRepository $productionRepository
    ) {}

    public function __invoke(CreateProductionTask $command): void
    {
        $agreementLine = $this->agreementLineRepository->find($command->agreementLineId);
        
        if (!$agreementLine) {
            throw new \InvalidArgumentException('AgreementLine not found');
        }
        
        $production = new Production();
        $production->setAgreementLine($agreementLine);
        $production->setDepartmentSlug($command->departmentSlug);
        $production->setPlannedDate(new \DateTimeImmutable($command->plannedDate));
        $production->setStatus('PENDING');
        
        $this->em->persist($production);
        $this->em->flush();
    }
}
```

> **Uwaga:** Command Handler nie zwraca wartości. Jeśli potrzebujemy danych po wykonaniu komendy, 
> kontroler powinien wykonać Query lub pobrać dane bezpośrednio z repozytorium.

**5. Response (JSON)**
```json
{
  "success": true,
  "message": "Production task created successfully"
}
```

## Logika biznesowa
- umieszczamy w serwisach (src/Modules/[ModuleName]/Service) 
- preferowane stosowanie wzorca CQRS poprzez App\System\CommandBus, App\System\EventBus, App\System\QueryBus
- **Komendy nie zwracają wartości** - służą tylko do wykonywania akcji i zmiany stanu systemu
- **Query zwracają wartości** - służą do pobierania danych bez modyfikacji stanu

## Encje i Doctrine

Encje znajdują się w `src/Modules/[ModuleName]/Entity` i używają PHP Attributes do mapowania.

### Przykład encji z atrybutami Doctrine

```php
<?php

namespace App\Module\AgreementLine\Entity;

use App\Module\Agreement\Entity\Agreement;
use App\Module\Product\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgreementLineRepository::class)]
#[ORM\Table(name: 'agreement_lines')]
class AgreementLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedDate = null;

    #[ORM\Column(type: 'float')]
    private float $factor = 1.0;

    #[ORM\Column(type: 'integer')]
    private int $quantity = 0;

    #[ORM\ManyToOne(targetEntity: Agreement::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private Agreement $agreement;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    // gettery i settery...
}
```

### Relacje encji

```
Agreement (1) ----< (N) AgreementLine (N) >---- (1) Product
    |                        |
    |                        |
    |                        v
    v                   Production (N)
Customer (1)
```

**Kardynalności:**
- `Agreement 1 --- N AgreementLine` - jedno zamówienie zawiera wiele pozycji (zwykle jedną)
- `AgreementLine N --- 1 Product` - wiele pozycji może odnosić się do tego samego produktu
- `AgreementLine 1 --- N Production` - jedna pozycja może mieć wiele zadań produkcyjnych (per dział)
- `Agreement N --- 1 Customer` - wiele zamówień dla jednego klienta
- `Production` zawiera pole `departmentSlug` (string) wskazujące na rodzaj działu produkcyjnego (dpt01-dpt05)

### AgreementLine Read Model
- Dla encji AgreementLine istnieje read-model zbierający dane także z powiązanych encji 
(np. Product, Customer, Production, Agreement) 
- znajduje się w `App\Module\AgreementLine\Entity\AgreementLineRM`
- Komenda aktualizująca ten read-model (`App\Module\AgreementLine\Command\UpdateAgreementLineRM`) jest uruchamiana po każdej zmianie w encji AgreementLine lub powiązanych encjach
- Należy dbać o to, by read-model był aktualizowany przy każdej zmianie danych, które zawiera

## Autoryzacja

System używa własnego systemu uprawnień (nie Symfony Security Voters):
```php
#[IsGranted('module.action')]
```

Przykłady uprawnień:
- `orders.create`
- `production.view`
- `production.edit`
- `work-configuration.capacity`
- `work-configuration.schedule`

## Tłumaczenia

Klucze tłumaczeń:
- `agreements.*` - dla modułu umów
- `production.*` - dla produkcji
- `work_configuration.*` - dla konfiguracji pracy
- `dashboard.*` - dla dashboardu

Przykład:
```yaml
# translations/production.pl.yml
production:
  list:
    title: "Lista zadań produkcyjnych"
  status:
    pending: "Oczekujące"
    in_progress: "W trakcie"
    completed: "Zakończone"
```

## Frontend 

Widoki na froncie zwracane są w pierwszej kolejności przez api Symfony (twig). Szablony twig są proste i
sprowadzają się głównie do zwrócenia customowego tagu html. Za rendering i obsługę na froncie odpowiadają komponenty Vue.
Lista globalnych komponentów możliwych do osadzania w szablonach twig znajduje się w `assets/js-vue/src/components/root-componenets.js`

Komponenty Vue powiązane z konkretną logiką biznesową, grupowane są per moduł i znajdują w katalogu `assets/js-vue/src/modules/[ModuleName]/components/`.
Komponenty wspólne trafiają do katalogu `assets/js-vue/src/components/base`

### Routing i zarządzanie stanem
- **Routing**: Brak Vue Router - nawigacja odbywa się przez widoki Twig (pełne przeładowanie strony)
- **Stan aplikacji**: Używamy Vuex, ale w ograniczonym zakresie (głównie dla danych globalnych)

### Frontend: tłumaczenia
Na froncie aplikacja wspiera języki angielski i polski. Do tłumaczeń używane jest vue-i18n. Pliki z kluczami tłumaczeń ładowane
są dynamiczne i umieszczane są w:
- katalogach modułów: `assets/js-vue/src/modules/[ModuleName]/locale/[lang].json`
- globalnych plikach: `assets/js-vue/src/locale/[lang].json`
Użycie w kodzie odpowiednio `this.$t('[moduleName].key')` lub `this.$t(`key`)`

### komunikacja z API

#### Repository pattern
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

#### Przykład 1: Komponent z Modal i VeeValidate

Modal jest często używany do wyświetlania formularzy i zbierania danych od użytkownika. Poniżej przykład komponentu z modal-action, ValidationObserver i subkomponentem formularza.

```vue
<template>
    <ValidationObserver ref="form" #default="{ invalid }">
        <modal-action
            :title="$t('agreement_line_list.startProductionForm.modalTitle')"
            :configuration="{ hideFooter: false, size: 'xl' }"
            :value="value"
            v-on="$listeners"
        >
            <template #open-action="{ open }">
                <slot name="open-action" :open="() => beforeOpen(open)">
                    <a class="dropdown-item p-0"
                       href="#"
                       @click.prevent="beforeOpen(open)"
                    >
                        <i class="fa fa-play" aria-hidden="true"/>
                        {{ $t('startProduction') }}
                    </a>
                </slot>
            </template>

            <template #modal-footer="{ close }">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-secondary" @click="close">{{ $t('cancel') }}</button>
                    <button class="btn btn-success ml-2" @click="startProduction(close)">
                        <i class="fa fa-play mr-2" aria-hidden="true" /> 
                        {{ $t('agreement_line_list.startProductionForm.startProduction') }}
                    </button>
                </div>
            </template>

            <template #default="{ close }">
                <div class="row">
                    <div class="col-lg-8">
                        <start-production-form
                            v-model="form"
                            :confirmedDate="confirmedDate"
                        />
                    </div>

                    <div class="col-lg-4">
                        <collapsible-card :title="$t('orders.orderProcessing')">
                            <agreement-line-widget :agreement-line="agreementLine" disable-edit />
                        </collapsible-card>

                        <collapsible-card :title="$t('product')" v-if="agreementLine?.Product">
                            <product-widget :product="agreementLine.Product" disable-edit />
                        </collapsible-card>
                    </div>
                </div>
            </template>
        </modal-action>
    </ValidationObserver>
</template>

<script>
import ModalAction from "@/components/base/ModalAction.vue";
import StartProductionForm from "./StartProductionForm.vue";
import ApiNewOrder from "@/api/neworder";
import { parseYMD } from "@/services/datesService";
import CollapsibleCard from "@/components/base/CollapsibleCard.vue";
import AgreementLineWidget from "@/components/orders/single/AgreementLineWidget.vue";
import ProductWidget from "@/components/orders/single/ProductWidget.vue";

export default {
    name: "StartProductionAction",

    props: {
        agreementLine: {
            type: Object,
            required: true,
        },
        value: {
            type: Boolean,
            default: false
        }
    },

    components: {
        ProductWidget, 
        CollapsibleCard, 
        AgreementLineWidget,
        ModalAction,
        StartProductionForm,
    },

    computed: {
        agreementLineId() {
            return this.agreementLine.id;
        },
        confirmedDate() {
            const date = this.agreementLine.confirmedDate
                .split('T')[0]
                .split(' ')[0];
            return parseYMD(date)
        },
        payload() {
            return (this.form || []).map(row => ({
                department: row.slug,
                dateStart: row.dateStart,
                dateEnd: row.dateEnd,
            }))
        }
    },

    methods: {
        beforeOpen(callback) {
            this.form = this.getInitialForm();
            callback && callback();
        },

        async startProduction(closeCallback)
        {
            // Walidacja przez VeeValidate
            const isValid = await this.$refs.form.validate();

            if (!isValid) {
                return
            }

            return ApiNewOrder.startProduction(this.agreementLineId, { schedule: this.payload })
                .then(() => {
                    EventBus.$emit('message', {
                        type: 'success',
                        content: this.$t('addedToSchedule')
                    });
                    EventBus.$emit('statusUpdated');
                    this.$emit('lineChanged');
                    if (closeCallback) {
                        closeCallback()
                    }
                })
                .catch((error) => {
                    EventBus.$emit('message', {
                        type: 'error',
                        content: error.response?.data?.message || this.$t('common.errorOccurred')
                    });
                })
        },

        getInitialForm() {
            return [
                { slug: 'dpt01', dateStart: null, dateEnd: null },
                { slug: 'dpt02', dateStart: null, dateEnd: null },
                { slug: 'dpt03', dateStart: null, dateEnd: null },
                { slug: 'dpt04', dateStart: null, dateEnd: null },
                { slug: 'dpt05', dateStart: null, dateEnd: null },
            ];
        }
    },

    data: () => ({
        form: []
    })
}
</script>
```

**Subkomponent formularza z walidacją (StartProductionForm.vue):**

```vue
<template>
    <div>
        <b-row v-for="row in formProxy" :key="row.slug">
            <b-col :cols="12" :lg="4" class="d-flex justify-content-end align-items-center">
                {{ getDepartmentName(row.slug) }}
            </b-col>

            <b-col :cols="6" :lg="4">
                <ValidationProvider
                    :name="`${row.slug}.dateStart`"
                    #default="{ errors }"
                    :rules="{
                        required: true,
                        dateFrom: { target: row.dateEnd },
                        dateEarlierThan: { deadline: confirmedDate }
                    }"
                >
                    <b-form-group
                        :label="$t('agreement_line_list.startProductionForm.startDate')"
                        :invalid-feedback="errors.join(' ')"
                    >
                        <date-picker
                            v-model="row.dateStart"
                            :is-range="false"
                            :date-only="true"
                            style="width: 100%"
                            :class="errors.length > 0 && 'is-invalid'"
                        />
                    </b-form-group>
                </ValidationProvider>
            </b-col>

            <b-col :cols="6" :lg="4">
                <ValidationProvider
                    :name="`${row.slug}.dateEnd`"
                    #default="{ errors }"
                    :rules="{
                        required: true,
                        dateTo: { target: row.dateStart },
                        dateEarlierThan: { deadline: confirmedDate }
                    }"
                >
                    <b-form-group
                        :label="$t('agreement_line_list.startProductionForm.endDate')"
                        :invalid-feedback="errors.join(' ')"
                    >
                        <date-picker
                            v-model="row.dateEnd"
                            :is-range="false"
                            :date-only="true"
                            style="width: 100%"
                            :class="errors.length > 0 && 'is-invalid'"
                        />
                    </b-form-group>
                </ValidationProvider>
            </b-col>
        </b-row>
    </div>
</template>

<script>
import { getDepartmentName } from "@/helpers";
import datePicker from "@/components/base/DatePicker.vue";

export default {
    name: "StartProductionForm",

    props: {
        value: {
            type: Array,
            default: () => []
        },
        confirmedDate: {
            type: Date,
            required: true
        }
    },

    components: {
        datePicker
    },

    watch: {
        value: {
            immediate: true,
            deep: true,
            handler() {
                const valueStr = JSON.stringify(this.value)
                if (valueStr === JSON.stringify(this.formProxy)) {
                    return
                }
                this.formProxy = JSON.parse(valueStr)
            }
        },

        formProxy: {
            deep: true,
            handler() {
                this.$emit('input', JSON.parse(JSON.stringify(this.formProxy)))
            }
        }
    },

    methods: {
        getDepartmentName,
    },

    data: () => ({
        formProxy: []
    })
}
</script>
```

**Kluczowe elementy:**
- `ValidationObserver` opakowuje cały modal i umożliwia walidację przez `this.$refs.form.validate()`
- `ValidationProvider` opakowuje każde pole formularza z własnymi regułami walidacji
- `v-model` z dwukierunkowym bindowaniem danych (przez `formProxy`)
- Modal z customowymi slotami: `#open-action`, `#modal-footer`, `#default`
- Obsługa błędów przez EventBus

#### Przykład 2: Komponent z Sidebar

Sidebar jest używany do wyświetlania dodatkowych informacji lub formularzy z boku ekranu. Poniżej przykład użycia Sidebar.

```vue
<template>
    <collapsible-card 
        :title="$t('agreement_line_list.factorsForm.sidebarTitle')" 
        :locked="locked" 
        v-if="canManageFactors"
    >
        <Sidebar
            :title="$t('agreement_line_list.factorsForm.sidebarTitle')"
            sidebar-class="size-100 size-lg-75"
        >
            <template #sidebar-action="{ open }">
                <button class="btn btn-outline-primary btn-sm" @click="open">
                    {{ $t('agreement_line_list.factorsForm.manageFactorsButton') }}
                </button>
            </template>
            
            <template #sidebar-content="{ close }">
                <FactorsView
                    :agreement-line="orderData"
                    :agreement-line-id="orderData.id"
                    @close="close"
                />
            </template>
        </Sidebar>
    </collapsible-card>
</template>

<script>
import Sidebar from '@/components/base/Sidebar.vue'
import FactorsView from '@/modules/agreementLineList/view/FactorsView'

export default {
    name: "OrderFactorsCard",
    
    components: {
        Sidebar, 
        FactorsView,
    },
    
    props: {
        orderData: {
            type: Object,
            required: true
        },
        locked: {
            type: Boolean,
            default: false
        }
    },
    
    computed: {
        canManageFactors() {
            return this.$user.can('production.factor_adjustment');
        }
    }
}
</script>
```

**Kluczowe elementy:**
- `Sidebar` z dwoma slotami: `#sidebar-action` (trigger button) i `#sidebar-content` (zawartość sidebara)
- Scoped slots z funkcjami `open` i `close` przekazywanymi z komponentu Sidebar
- Event `@close` emitowany z subkomponentu do zamknięcia sidebara
- Sprawdzanie uprawnień przez `this.$user.can()`

