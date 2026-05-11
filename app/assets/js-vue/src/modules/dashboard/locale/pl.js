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
    'capacityMetric': 'Obłożenie działów produkcji',
    'weeklyCapacityMetric': 'Obłożenie tygodniowe',
    'showForecast': 'Pokaż prognozę zadań oczekujących',
    'forecastLabel': 'Prognoza',
    'ghostOrderBanner': 'Zadania w prognozie - produkcja jeszcze nie została zlecona',

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
        },
        'weeklyCapacity': {
            'p1': 'Pokazuje tygodniowe zestawienie <strong>zdolności produkcyjnej</strong> firmy na tle <strong>obłożenia wynikającego z przyjętych zamówień</strong>. Dla każdego tygodnia pasek postępu ilustruje, jaka część dostępnej zdolności jest zajęta przez zamówienia z terminem dostawy przypadającym w danym tygodniu.',
            'p2': 'Zdolność tygodniowa jest wyliczana na podstawie skonfigurowanych norm dziennych, z pominięciem dni wolnych i świąt. Obłożenie to suma współczynników zamówień, których <strong>potwierdzony termin dostawy</strong> przypada w danym tygodniu — nie są to daty zadań produkcyjnych, lecz terminy uzgodnione z klientem.',
            'p3': 'Raport uwzględnia wyłącznie zamówienia, dla których uruchomiono produkcję. Zamówienia bez zlecenia produkcyjnego nie wpływają na wynik, chyba, że aktywny jest tryb prognozy.',
        },
    }
}