import TomSelect from 'tom-select';
import { RecursivePartial } from 'tom-select/dist/esm/types/core.js';
import { TomSettings } from 'tom-select/dist/esm/types/settings.js';

//ea.autocomplete.connect
export type EAAutocompleteConnectEvent = CustomEvent<{
    tomSelect: TomSelect;
    config: RecursivePartial<TomSettings>;
    prefix: 'autocomplete';
}>;
//ea.autocomplete.pre-connect
export type EAAutocompletePreConnectEvent = CustomEvent<{
    config: RecursivePartial<TomSettings>;
    prefix: 'autocomplete';
}>;
//ea.collection.item-added
export type EACollectionItemAddedEvent = CustomEvent<{
    newElement: HTMLDivElement;
    collection: HTMLDivElement;
}>;
//ea.collection.item-removed
export type EACollectionItemRemovedEvent = CustomEvent<{
    deletedElement: HTMLDivElement;
    collection: HTMLDivElement;
}>;
//ea.form.submit
export type EAFormSubmitEvent = CustomEvent<{
    page: 'new' | 'edit';
    form: HTMLFormElement;
}>;
//ea.form.error
export type EAFormErrorEvent = CustomEvent<{
    page: 'new' | 'edit';
    form: HTMLFormElement;
}>;

declare global {
    interface HTMLElementEventMap {
        'ea.autocomplete.connect': EAAutocompleteConnectEvent;
        'ea.autocomplete.pre-connect': EAAutocompletePreConnectEvent;
    }
    interface DocumentEventMap {
        'ea.autocomplete.connect': EAAutocompleteConnectEvent;
        'ea.autocomplete.pre-connect': EAAutocompletePreConnectEvent;
        'ea.collection.item-added': EACollectionItemAddedEvent;
        'ea.collection.item-removed': EACollectionItemRemovedEvent;
        'ea.form.submit': EAFormSubmitEvent;
        'ea.form.error': EAFormErrorEvent;
    }
}
