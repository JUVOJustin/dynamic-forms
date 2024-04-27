import Alpine from 'alpinejs'
window.Alpine = Alpine

Alpine.data('formDatePicker', function (form_id) {
    return {
        form_id: form_id,
        values: {},
        responseError: null, // Reactive property for storing any error message
        costs: {
            'total': 0,
        },
        isLoading: false,
        isPriceLoading: false,
        page: 1,
        error: "",
        success: "",
        showForm: true,
        init() {

            // Receive values from fields
            this.$el.addEventListener('setValue', (data) => {
                this.setValue(data.detail.name, data.detail.value);
            });

            this.$watch('values', () => {
                this.requestPrice();

                this.error = "";
            });

        },
        requestPrice() {
            this.isPriceLoading = true;
            $.ajax({
                url: dynamic_forms.ajax_url,
                type: 'post',
                data: {
                    action: 'calc_price',
                    form_id: this.form_id,
                    post_id: dynamic_forms.post_id,
                    nonce: dynamic_forms.nonce,
                    fields: this.values
                }
            }).done((response) => {
                if (response.success) {
                    this.costs = response.data;
                } else {
                    this.error = response.data;
                }
                this.isPriceLoading = false;
            }).fail((jqXHR, textStatus, errorThrown) => {
                this.error = errorThrown || 'An error occurred';
                this.isPriceLoading = false;
            });
        },
        submit() {
            this.isLoading = true;
            $.ajax({
                url: dynamic_forms.ajax_url,
                type: 'post',
                data: {
                    action: 'submit',
                    nonce: dynamic_forms.nonce,
                    post_id: dynamic_forms.post_id,
                    form_id: this.form_id,
                    fields: this.values
                }
            }).done((response) => {
                if (response.success) {
                    this.success = response.data;
                    this.showForm = false;
                } else {
                    this.error = response.data;
                }
                this.isLoading = false;
            }).fail((jqXHR, textStatus, errorThrown) => {
                this.error = errorThrown || 'An error occurred';
                this.isLoading = false;
            });
        },
        checkChanges() {
            const currentValues = this.getFormValues();
            this.valuesChanged = Object.keys(this.originalValues).some(key =>
                this.originalValues[key] !== currentValues[key]
            );
        },
        nextPage(currentPageId) {

            // Get element by id
            const page = this.$refs[currentPageId];

            // Get all inputs in the page
            const inputs = page.querySelectorAll('input, select, textarea');

            let page_valid = true;

            // Check validity of all inputs
            inputs.forEach(input => {
                const valid = input.reportValidity();
                if (!valid) page_valid = false;
            });

            if (page_valid) {
                this.page++;
            }
        },
        prevPage(currentPageId) {
            this.page--;
        },
        setValue(name, value) {
            this.values[name] = value;
        }
    };
});

// Add custom fields
import './input-number.js';
import './input-phone.js';
import './input-email.js';
import './input-text.js';
import './input-datepicker.js';

Alpine.start()
