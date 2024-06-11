export function input(input, field = {}) {
    return {
        init() {

            this.idWrapper = input.$id(this.name + '-wrapper');
            this.id = input.$id(this.name);

            this.dispatchValue();

            // emit value change to parent
            input.$watch('value', (value) => {
                this.dispatchValue();
            })

            if (typeof this.setup === "function") {
                this.setup();
            }

        },
        ...field, // Stores the data directly passed from php
        id: "",
        idWrapper: "",
        value: "",
        dispatchValue() {
            this.$dispatch('setValue', {
                form: this.form_id,
                name: this.name,
                value: this.value
            })
        }
    };
}