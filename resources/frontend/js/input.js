export function input(input, name, label, required) {
    return {
        init() {

            this.dispatchValue();

            // emit value change to parent
            input.$watch('value', (value) => {
                this.dispatchValue();
            })
        },
        name: name,
        label: label,
        idWrapper: input.$id(name + '-wrapper'),
        id: input.$id(name),
        value: "",
        required: required,
        dispatchValue() {
            this.$dispatch('setValue', {
                name: this.name,
                value: this.value
            })
        }
    };
}