import {input} from './input';

document.addEventListener('alpine:init', () => {
    Alpine.data('numberInput', function (name, label, required, min = null, max = null, step = 1) {
        return {
            ...input(this, name, label, required),
            type: 'number',
            value: min,
            min: min,
            max: max,
            step: step
        };
    });
});
