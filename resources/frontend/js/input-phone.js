import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('phoneInput', function (field) {
        return {
            ...input(this, field),
            type: 'phone',
            placeholder: field.placeholder ?? "",
        };
    });
});
