import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('numberInput', function (field) {
        return {
            ...input(this, field),
            type: 'number',
            value: field.value,
            min: field.min ?? 0,
            max: field.max,
            step: field.step ?? 1
        };
    });
});
