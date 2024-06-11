import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('emailInput', function (field) {
        return {
            ...input(this, field),
            type: 'email',
            placeholder: field.placeholder ?? "",
        };
    });
});
