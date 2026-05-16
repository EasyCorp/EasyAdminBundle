<?php

return [
    'page_title' => [
        'dashboard' => 'Nadzorna ploča',
        'detail' => '%entity_label_singular% <small>(#%entity_short_id%)</small>',
        'edit' => 'Uredi %entity_label_singular%',
        'index' => '%entity_label_plural%',
        'new' => 'Izradi %entity_label_singular%',
        'exception' => 'Greška|Greške',
    ],

    'datagrid' => [
        'hidden_results' => 'Neki rezultati se ne mogu prikazati jer nemate dovoljno ovlasti',
        'no_results' => 'Nema rezultata pretrage.',
    ],

    'paginator' => [
        'first' => 'Prvi',
        'previous' => 'Prethodan',
        'next' => 'Sljedeći',
        'last' => 'Posljednji',
        'counter' => '<strong>%start%</strong> - <strong>%end%</strong> od <strong>%results%</strong>',
        'results' => '{0} Nema rezultata|{1} <strong>1</strong> rezultat|]1,Inf] <strong>%count%</strong> rezultata',
    ],

    'label' => [
        'true' => 'Da',
        'false' => 'Ne',
        'empty' => 'Prazno',
        'null' => 'Null',
        'object' => 'PHP Object',
        'inaccessible' => 'Nepristupačan',
        'inaccessible.explanation' => 'Getter metoda ne postoji za ovo polje ili vrijednost svojstva nije javna',
        'form.empty_value' => 'Prazno',
    ],

    'field' => [
        'code_editor.view_code' => 'Prikaži kod',
        'text_editor.view_content' => 'Prikaži sadržaj',
    ],

    'action' => [
        'entity_actions' => 'Akcije',
        'new' => 'Dodaj %entity_label_singular%',
        'search' => 'Pretraži',
        'detail' => 'Prikaži',
        'edit' => 'Uredi',
        'delete' => 'Izbriši',
        'cancel' => 'Poništi',
        'index' => 'Natrag na popis',
        'deselect' => 'Poništi odabir',
        'add_new_item' => 'Dodajte novu stavku',
        'remove_item' => 'Uklonite stavku',
        'choose_file' => 'Odaberi datoteku',
        'close' => 'Zatvori',
        'download' => 'Preuzmi',
        'create' => 'Izradi',
        'create_and_add_another' => 'Izradi i dodaj još jedan',
        'create_and_continue' => 'Izradi i nastavi uređivanje',
        'save' => 'Spremi promjene',
        'save_and_continue' => 'Spremi i nastavi uređivanje',
        'toggle_dropdown' => 'Prebaci padajući izbornik',
    ],

    'batch_action_modal' => [
        'title' => 'Primijenit ćete akciju "%action_name%" na %num_items% stavki.',
        'content' => 'Ova se operacija ne može poništiti.',
        'action' => 'Nastavi',
    ],

    'delete_modal' => [
        'title' => 'Jeste li sigurni da želite izbrisati ovu stavku?',
        'content' => 'Izbrisana stavka se ne može povratiti',
    ],

    'action_confirmation_modal' => [
        'title' => 'Jeste li sigurni da želite %action_name%?',
        'action' => 'Potvrdi',
    ],

    'filter' => [
        'title' => 'Filtri',
        'button.clear' => 'Očisti',
        'button.apply' => 'Primijeni',
        'label.is_equal_to' => 'je jednako',
        'label.is_not_equal_to' => 'nije jednako',
        'label.is_greater_than' => 'je veće od',
        'label.is_greater_than_or_equal_to' => 'je veće ili jednako',
        'label.is_less_than' => 'je manje od',
        'label.is_less_than_or_equal_to' => 'je manje ili jednako',
        'label.is_between' => 'je između',
        'label.contains' => 'sadrži',
        'label.contains_all' => 'sadrži sve',
        'label.not_contains' => 'ne sadrži',
        'label.starts_with' => 'počinje s',
        'label.ends_with' => 'završava s',
        'label.exactly' => 'točno',
        'label.not_exactly' => 'nije točno',
        'label.is_same' => 'je isto',
        'label.is_not_same' => 'nije isto',
        'label.is_after' => 'je nakon',
        'label.is_after_or_same' => 'je nakon ili isto',
        'label.is_before' => 'je prije',
        'label.is_before_or_same' => 'je prije ili isto',
    ],

    'form' => [
        'are_you_sure' => 'Niste spremili izmjene na ovom obrascu.',
        'tab.error_badge_title' => 'Jedan neispravan unos|%count% neispravnih unosa',
        'slug.confirm_text' => 'Ako promijenite slug, možete pokvariti poveznice na drugim stranicama.',
    ],

    'user' => [
        'logged_in_as' => 'Prijavljen kao',
        'unnamed' => 'Neimenovani korisnik',
        'anonymous' => 'Anonimni korisnik',
        'sign_out' => 'Odjava',
        'exit_impersonation' => 'Izađi iz impersonacije',
    ],

    'settings' => [
        'appearance' => [
            'label' => 'Izgled',
            'light' => 'Svijetli',
            'dark' => 'Tamni',
            'auto' => 'Automatski',
        ],
        'locale' => 'Jezik',
    ],

    'login_page' => [
        'username' => 'Korisničko ime',
        'password' => 'Lozinka',
        'sign_in' => 'Prijavi se',
        'forgot_password' => 'Zaboravili ste Vašu lozinku?',
        'remember_me' => 'Zapamti me',
    ],

    'exception' => [
        'entity_not_found' => 'Ta stavka više nije dostupna.',
        'entity_remove' => 'Ta stavka ne može se izbrisati jer ovise o njoj ostale stavke.',
        'forbidden_action' => 'Zatražena radnja ne može se izvršiti na ovoj stavci.',
        'insufficient_entity_permission' => 'Nemate dopuštenje za pristup ovoj stavci.',
        'general' => 'An error occurred while processing your request.',
        'general_403' => 'You don\'t have permission to perform this action.',
        'general_404' => 'The requested page could not be found.',
        'general_500' => 'An internal error occurred while processing your request.',
    ],

    'file_upload' => [
        'add_file' => 'Dodaj datoteku',
        'add_files' => 'Dodaj datoteke',
        'clear_all' => 'Očisti sve',
    ],

    'autocomplete' => [
        'no-results-found' => 'Nema rezultata',
        'no-more-results' => 'Nema više rezultata',
        'loading-more-results' => 'Učitavanje rezultata…',
    ],
];
