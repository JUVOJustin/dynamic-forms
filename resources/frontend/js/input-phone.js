import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('phoneInput', function (name, label, required, placeholder = "") {
        return {
            ...input(this, name, label, required),
            type: 'phone',
            placeholder: placeholder,
        };
    });
});
