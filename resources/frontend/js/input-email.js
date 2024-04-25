import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('emailInput', function (name, label, required, placeholder = "") {
        return {
            ...input(this, name, label, required),
            type: 'email',
            placeholder: placeholder,
        };
    });
});
