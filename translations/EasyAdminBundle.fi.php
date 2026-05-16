<?php

return [
    'page_title' => [
        'dashboard' => 'Hallintapaneeli',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'Muokkaa %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'Luo uusi %entity_label_singular%',
        'exception' => 'Virhe|Virheet',
    ],

    'datagrid' => [
        'hidden_results' => 'Joitakin tuloksia ei voida näyttää, koska sinulla ei ole riittäviä käyttöoikeuksia',
        'no_results' => 'Tuloksia ei löytynyt.',
    ],

    'paginator' => [
        'first' => 'Ensimmäinen',
        'previous' => 'Edellinen',
        'next' => 'Seuraava',
        'last' => 'Viimeinen',
        'counter' => 'Tulokset <strong>%start%</strong> - <strong>%end%</strong>, yhteensä <strong>%results%</strong>',
        'results' => '{0} Ei tuloksia|{1} <strong>1</strong> tulos|]1,Inf] <strong>%count%</strong> tulosta',
    ],

    'label' => [
        'true' => 'Kyllä',
        'false' => 'Ei',
        'empty' => 'Tyhjä',
        'null' => 'Ei asetettu',
        'object' => 'PHP-objekti',
        'inaccessible' => 'Ei saatavilla',
        'inaccessible.explanation' => 'Arvo ei ole julkinen, tai sille ei ole asetettu get-metodia',
        'form.empty_value' => 'Ei mitään',
    ],

    'field' => [
        'code_editor.view_code' => 'Näytä koodi',
        'text_editor.view_content' => 'Näytä sisältö',
    ],

    'action' => [
        'entity_actions' => 'Toiminnot',
        'new' => 'Lisää uusi %entity_label_singular%',
        'search' => 'Etsi',
        'detail' => 'Näytä',
        'edit' => 'Muokkaa',
        'delete' => 'Poista',
        'cancel' => 'Peruuta',
        'index' => 'Takaisin listaan',
        'deselect' => 'Poista valinta',
        'add_new_item' => 'Luo uusi rivi',
        'remove_item' => 'Poista rivi',
        'choose_file' => 'Valitse tiedosto',
        'close' => 'Sulje',
        'download' => 'Lataa',
        'create' => 'Luo',
        'create_and_add_another' => 'Luo ja lisää toinen',
        'create_and_continue' => 'Luo ja jatka muokkaamista',
        'save' => 'Tallenna muutokset',
        'save_and_continue' => 'Tallenna ja jatka muokkaamista',
        'toggle_dropdown' => 'Vaihda pudotusvalikko',
    ],

    'batch_action_modal' => [
        'title' => 'Olet suorittamassa toimintoa "%action_name%" %num_items% kohteelle.',
        'content' => 'Tätä toimintoa ei voi peruuttaa.',
        'action' => 'Jatka',
    ],

    'delete_modal' => [
        'title' => 'Oletko varma että haluat poistaa tämän?',
        'content' => 'Toimintoa ei voi peruuttaa.',
    ],

    'action_confirmation_modal' => [
        'title' => 'Haluatko varmasti %action_name%?',
        'action' => 'Vahvista',
    ],

    'filter' => [
        'title' => 'Suodattimet',
        'button.clear' => 'Tyhjennä',
        'button.apply' => 'Käytä',
        'label.is_equal_to' => 'on yhtä suuri kuin',
        'label.is_not_equal_to' => 'ei ole yhtä suuri kuin',
        'label.is_greater_than' => 'on suurempi kuin',
        'label.is_greater_than_or_equal_to' => 'on suurempi tai yhtä suuri kuin',
        'label.is_less_than' => 'on pienempi kuin',
        'label.is_less_than_or_equal_to' => 'on pienempi tai yhtä suuri kuin',
        'label.is_between' => 'on välillä',
        'label.contains' => 'sisältää',
        'label.contains_all' => 'sisältää kaikki',
        'label.not_contains' => 'ei sisällä',
        'label.starts_with' => 'alkaa',
        'label.ends_with' => 'päättyy',
        'label.exactly' => 'täsmälleen',
        'label.not_exactly' => 'ei täsmälleen',
        'label.is_same' => 'on sama',
        'label.is_not_same' => 'ei ole sama',
        'label.is_after' => 'on jälkeen',
        'label.is_after_or_same' => 'on jälkeen tai sama',
        'label.is_before' => 'on ennen',
        'label.is_before_or_same' => 'on ennen tai sama',
    ],

    'form' => [
        'are_you_sure' => 'Et ole tallentanut muuttamiasi tietoja.',
        'tab.error_badge_title' => 'Yksi virheellinen syöte|%count% virheellistä syötettä',
        'slug.confirm_text' => 'Jos muutat slugia, voit rikkoa linkkejä muilla sivuilla.',
    ],

    'user' => [
        'logged_in_as' => 'Kirjautunut käyttäjänä',
        'unnamed' => 'Nimetön käyttäjä',
        'anonymous' => 'Tuntematon käyttäjä',
        'sign_out' => 'Ulos',
        'exit_impersonation' => 'Lopeta käyttäjänä esiintyminen',
    ],

    'settings' => [
        'appearance' => [
            'label' => 'Ulkoasu',
            'light' => 'Vaalea',
            'dark' => 'Tumma',
            'auto' => 'Automaattinen',
        ],
        'locale' => 'Kieli',
    ],

    'login_page' => [
        'username' => 'Username',
        'password' => 'Password',
        'sign_in' => 'Sign in',
        'forgot_password' => 'Unohditko salasanasi?',
        'remember_me' => 'Muista minut',
    ],

    'exception' => [
        'entity_not_found' => 'Tämä kohde ei ole enää saatavilla.',
        'entity_remove' => 'Tätä kohdetta ei voi poistaa, koska muut kohteet riippuvat siitä.',
        'forbidden_action' => 'Pyydettyä toimintoa ei voi suorittaa tälle kohteelle.',
        'insufficient_entity_permission' => 'Sinulla ei ole lupaa käyttää tätä kohdetta.',
        'general' => 'An error occurred while processing your request.',
        'general_403' => 'You don\'t have permission to perform this action.',
        'general_404' => 'The requested page could not be found.',
        'general_500' => 'An internal error occurred while processing your request.',
    ],

    'file_upload' => [
        'add_file' => 'Lisää tiedosto',
        'add_files' => 'Lisää tiedostoja',
        'clear_all' => 'Tyhjennä kaikki',
    ],

    'autocomplete' => [
        'no-results-found' => 'Ei tuloksia',
        'no-more-results' => 'Ei enempää tuloksia',
        'loading-more-results' => 'Ladataan lisää tuloksia…',
    ],
];
