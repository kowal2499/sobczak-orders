export default {
    'title': 'Pulpit',
    'month_placeholder': 'Wybierz miesiąc',
    'year_placeholder': 'Wybierz rok',
    'orders_pending': 'Zamówienia w realizacji',
    'orders_finished': 'Zamówienia zrealizowane',
    'factors_pending': 'Suma współczynników dla zamówień w realizacji',
    'factors_completed': 'Suma współczynników dla zamówień zrealizowanych',
    'workingDays': 'Ilość dni roboczych',
    'factorLimit': 'Limit współczynników w miesiącu',
    'totalFactors': 'Suma współczynników dla wszystkich zamówień',
    'firstFreeDay': 'Planowany dzień zrealizowania wszystkich zamówień',
    'tasksCompleted': 'Ukończone zadania produkcyjne',
    'tasksCompletedOnTime': 'Ukończone zadania produkcyjne (w terminie)',
    'timeliness': {
        'planned': 'Planowany zakres',
        'actual': 'Faktyczna realizacja',
        'status': 'Terminowość',
        'onTime': 'W terminie',
        'delayedOne': 'Opóźnione o {days} dzień roboczy',
        'delayedMany': 'Opóźnione o {days} dni robocze',
        'earlyOne': 'Przyspieszone o {days} dzień roboczy',
        'earlyMany': 'Przyspieszone o {days} dni robocze',
        'noWindow': 'Brak zaplanowanego okna',
    },
    // poglądowa informacja o dotrzymaniu okna produkcji w raportach, gdzie termin nie daje premii
    'plannedWindow': {
        'label': 'Zaplanowane okno',
        'met': 'dotrzymane',
        'missed': 'niedotrzymane',
        'note': 'Informacja poglądowa - nie wpływa na naliczony współczynnik.',
    },
    // wspólne dla wszystkich raportów pokazujących rozbicie współczynnika w komórce
    'factorBreakdown': {
        'title': 'Składowe współczynnika',
        'finalValue': 'Wartość finalna',
    },
    'onTimeCell': {
        'noBonusNote': 'współczynnik premii 0',
        'withinToleranceNote': 'Premia uznana dzięki widełkom - ukończenie wypadło poza zaplanowanym oknem, ale w granicach tolerancji.',
        'addAdjustment': 'Dodaj korektę',
        'adjustmentType': {
            'bonus': 'Bonus',
            'penalty': 'Kara',
        },
        'adjustmentComment': 'Komentarz do korekty...',
        'addAdjustmentButton': 'Dodaj korektę',
        'adjustmentSaved': 'Korekta premii została zapisana',
        'adjustmentError': 'Nie udało się zapisać korekty premii',
        'validation': {
            'valueRequired': 'Podaj wartość korekty różną od zera',
            'bonusMustBePositive': 'Bonus musi być liczbą dodatnią',
            'penaltyMustBeNegative': 'Kara musi być liczbą ujemną',
            'commentRequired': 'Komentarz do korekty jest wymagany',
        },
        'outOfRange': {
            'title': 'Poza zakresem dat',
            'reportRange': 'Zakres raportu',
            'productionWindow': 'Okno produkcji',
            'completedAt': 'Ukończono',
            'notCompleted': 'nieukończone',
            'noteCompletedOutside': 'Zadanie ukończono poza zakresem raportu - pozycja jest liczona w miesiącu faktycznego ukończenia.',
            'noteNotCompleted': 'Zadanie nie zostało jeszcze ukończone - pozycja nie jest liczona w żadnym okresie.',
        },
    },
    'tolerance': {
        'label': 'Widełki:',
        'unit': 'dni rob.',
        'hint': 'Tolerancja terminu w dniach roboczych. Tylko jako podgląd - nie zapisuje się.',
    },
    'capacityMetric': 'Obłożenie działów produkcji',
    'weeklyCapacityMetric': 'Obłożenie tygodniowe',
    'showForecast': 'Pokaż prognozę zadań oczekujących',
    'forecastLabel': 'Prognoza',
    'ghostOrderBanner': 'Zadania w prognozie - produkcja jeszcze nie została zlecona',

    'layout': {
        'edit': 'Edytuj układ',
        'done': 'Zakończ edycję',
        'reset': 'Przywróć domyślny układ',
        'hide': 'Ukryj widget',
        'show': 'Pokaż widget',
    },

    'productionMetric': {
        'baseFactor': 'Współczynnik bazowy',
        'bonus': 'Bonus',
        'penalty': 'Kara',
        'percentageModifier': 'Modyfikator procentowy',
        'unsupportedValue': 'Nieobsługiwana wartość',
    },

    'descriptions': {
        'capacity': {
            'p1': '<strong>Raport planistyczny.</strong> Operuje wyłącznie na danych zadań produkcyjnych — status samego zamówienia ani jego planowana data realizacji nie mają wpływu na wyniki.',
            'p2': 'Pokazuje zaplanowane obłożenie każdego działu produkcji w wybranym okresie. Każde zadanie jest przypisywane do miesiąca według <strong>zaplanowanej daty zakończenia pracy w danym dziale</strong> — ustalanej w momencie zlecenia do produkcji.',
            'p3': 'Raport obejmuje zadania z planowaną datą zakończenia w wybranym zakresie, które zostały już zlecone do produkcji — zarówno w trakcie realizacji, jak i zakończone. Nie uwzględnia bonusów ani kar.',
            'p4': 'Uwaga: zadania, których praca w danym dziale trwa dłużej niż jeden miesiąc, są widoczne wyłącznie w miesiącu planowanego zakończenia, a nie w miesiącu startu.',
            'p5': 'Raport nie pokazuje zamówień, których produkcja jeszcze nie została zlecona, chyba, że aktywny jest tryb prognozy.',
        },
        'tasksCompleted': {
            'p1': 'Raport operuje wyłącznie na danych zadań produkcyjnych — status samego zamówienia ani jego planowana data realizacji nie mają wpływu na wyniki.',
            'p2': '<strong>Raport realizacyjny.</strong> Pokazuje sumę współczynników produkcji <strong>faktycznie ukończonych</strong> w wybranym okresie, z podziałem na działy. Każde zadanie jest przypisywane do miesiąca według <strong>rzeczywistej daty zakończenia</strong> — momentu, w którym oznaczono je jako ukończone.',
            'p3': 'Oznacza to, że zadania opóźnione (zaplanowane na wcześniejszy miesiąc, ale ukończone później) pojawiają się w miesiącu faktycznego zakończenia. Tak samo zadania przyspieszone — jeśli coś zaplanowano na kwiecień, a ukończono w marcu, trafi do raportu marcowego. Raport uwzględnia bonusy i kary przypisane do zadań. Nie zawiera zadań oczekujących ani w trakcie realizacji.',
            'p4': 'Najlepiej sprawdza się do <strong>miesięcznego rozliczenia</strong> rzeczywiście wykonanej pracy.',
            'p5': 'W dymku komórki widać, czy zadanie zmieściło się w zaplanowanym oknie produkcji danego działu. To <strong>informacja poglądowa</strong> - w tym raporcie termin nie wpływa na naliczony współczynnik.',
        },
        'tasksCompletedOnTime': {
            'p1': '<strong>Raport premiowy (terminowość).</strong> Jak „Ukończone zadania produkcyjne", ale premia (współczynnik z bonusami i karami) jest naliczana <strong>wyłącznie za ukończenie działu w zaplanowanym oknie czasowym</strong> — data ukończenia mieści się między planowanym startem a końcem pracy w danym dziale.',
            'p2': 'Zadania ukończone poza oknem (za wcześnie, po terminie lub bez ustawionych dat) są widoczne, ale <strong>wyszarzone i liczone jako 0 punktów</strong>. Szczegóły bonusów i kar pozostają dostępne w dymku. Każdy dział rozliczany jest osobno.',
            'p3': 'Okno akceptacji jest rozszerzone o <strong>widełki liczone w dniach roboczych</strong> (weekendy i święta ich nie konsumują) - drobne obsuwy nadal dają premię. Pole „Widełki" na kafelku pozwala podejrzeć wynik dla innej wartości, ale nie zmienia ustawienia obowiązującego dla rozliczeń.',
        },
        'weeklyCapacity': {
            'p1': 'Pokazuje tygodniowe zestawienie <strong>zdolności produkcyjnej</strong> firmy na tle <strong>obłożenia wynikającego z przyjętych zamówień</strong>. Dla każdego tygodnia pasek postępu ilustruje, jaka część dostępnej zdolności jest zajęta przez zamówienia z terminem dostawy przypadającym w danym tygodniu.',
            'p2': 'Zdolność tygodniowa jest wyliczana na podstawie skonfigurowanych norm dziennych, z pominięciem dni wolnych i świąt. Obłożenie to suma współczynników zamówień, których <strong>potwierdzony termin dostawy</strong> przypada w danym tygodniu — nie są to daty zadań produkcyjnych, lecz terminy uzgodnione z klientem.',
            'p3': 'Raport uwzględnia wyłącznie zamówienia, dla których uruchomiono produkcję. Zamówienia bez zlecenia produkcyjnego nie wpływają na wynik, chyba, że aktywny jest tryb prognozy.',
        },
    }
}