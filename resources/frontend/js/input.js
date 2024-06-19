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
        dispatchValue() {

            let forms = [];

            // If mirroring forms are set up add them to the recipients
            if (this.mirroring_forms) {
                this.mirroring_forms.forEach((form) => {
                    forms.push(form);
                })
            }

            this.$dispatch('setValue', {
                forms: forms,
                name: this.name,
                value: this.values[this.name]
            })
        }
    };
}