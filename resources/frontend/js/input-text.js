import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('textInput', function (name, label, required, placeholder = "") {
        return {
            ...input(this, name, label, required),
            type: 'text',
            placeholder: placeholder,
        };
    });
});
