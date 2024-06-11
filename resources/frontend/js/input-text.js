import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('textInput', function (field) {
        return {
            ...input(this, field),
            type: 'text',
            placeholder: field.placeholder ?? "",
        };
    });
});
