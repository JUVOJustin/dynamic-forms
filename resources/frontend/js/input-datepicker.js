import {input} from './input';
import {AmpPlugin, DateTime, easepick, LockPlugin, RangePlugin} from "@easepick/bundle";

document.addEventListener('alpine:init', () => {
    Alpine.data('datepickerInput', function (name, labels, bookedDates = [], userConfig = {}, day_offset = 0) {
        return {
            ...input(this, name, "", true),
            init() {

                // Wait till next tick
                this.$nextTick(() => {
                    this.createCalendar();
                });

                // emit value change to parent !!Re add because init from input is overwritten immediately!!
                this.dispatchValue();
                this.$watch('value', () => {
                    this.dispatchValue();
                })

                // Parse bookeddates
                if (this.bookedDates.length > 0) {
                    this.setBookedDates(this.bookedDates);
                }

                // Adjust position on window resize
                window.addEventListener('resize', this.adjustPosition.bind(this));
            },
            type: 'datepicker',
            labels: labels,
            bookedDates: bookedDates,
            picker: null,
            day_offset: Number(day_offset),
            createCalendar() {
                const checkin = this.$refs.form.querySelector('#' + this.id + '-checkin');
                const checkout = this.$refs.form.querySelector('#' + this.id + '-checkout');

                let config = {
                    css: dynamic_forms.easepicker_css,
                    element: checkin,
                    plugins: [
                        AmpPlugin,
                        RangePlugin,
                        LockPlugin
                    ],
                    setup: (picker) => {
                        picker.on('render', (e) => {
                            this.adjustPosition();
                        });
                        picker.on('select', (e) => {

                            // If picker exists and has a start and end date, set checkin and checkout dates
                            if (e.detail.start && e.detail.end) {
                                this.value = {
                                    checkin: e.detail.start.format('YYYY-MM-DD'),
                                    checkout: e.detail.end.format('YYYY-MM-DD')
                                }
                            }
                        });
                    },
                    zIndex: 10,
                    format: "DD MMM YYYY",
                    grid: 2,
                    calendars: 2,
                    readonly: false,
                    inline: false,
                    documentClick: true,
                    RangePlugin: {
                        tooltipNumber: (num) => {
                            return num + this.day_offset;
                        },
                        locale: {
                            one: this.labels.one,
                            other: this.labels.other,
                        },
                        elementEnd: checkout,
                        repick: false,
                        strict: false
                    },
                    LockPlugin: {
                        minDate: new Date(),
                        inseparable: true,
                        selectForward: true,
                        filter: (date, picked) => {
                            if (picked.length === 1) {
                                const incl = date.isBefore(picked[0]) ? '[)' : '(]';
                                return !picked[0].isSame(date, 'day') && date.inArray(this.bookedDates, incl);
                            }
                            return date.inArray(this.bookedDates, '[)');
                        },
                    }
                };
                config = deepMergeConfigs(config, userConfig);
                this.picker = new easepick.create(config);
            },
            adjustPosition() {
                const wrapper = this.$el.querySelector('.easepick-wrapper');

                // Make sure container exists
                if (!wrapper) return;

                // Make sure shadowRoot exists
                if (!wrapper.shadowRoot) return;

                const container = wrapper.shadowRoot.firstChild;
                if (!container) return;

                const checkAndAdjust = () => {
                    const rect = container.getBoundingClientRect();
                    const thisElRect = this.$el.getBoundingClientRect();

                    if (rect.width === 0 && rect.height === 0) {
                        requestAnimationFrame(checkAndAdjust);
                        return;
                    }

                    const viewportWidth = window.innerWidth;
                    const centerThisEl = thisElRect.left + (thisElRect.width / 2);
                    let intendedLeft = centerThisEl - (rect.width / 2);

                    // Check if container is too wide for the viewport to maintain 30px margins on both sides
                    if (rect.width + 60 > viewportWidth) {
                        intendedLeft = 0; // Align to the complete left of the viewport
                    } else {
                        // Adjust for right overflow
                        if (intendedLeft + rect.width > viewportWidth - 30) {
                            intendedLeft = viewportWidth - rect.width - 30;
                        }

                        // Adjust for left overflow
                        if (intendedLeft < 30) {
                            intendedLeft = 30;
                        }
                    }

                    // Calculate the offset of the parent element
                    const parentOffset = container.offsetParent.getBoundingClientRect().left;

                    // Adjust the left position of the container relative to the offset parent
                    container.style.left = `${intendedLeft - parentOffset}px`;
                    container.style.position = 'absolute';
                };

                checkAndAdjust();
            },
            setBookedDates(dates) {
                this.bookedDates = dates.map(d => {
                    if (d instanceof Array) {
                        const start = new DateTime(d[0], 'YYYY-MM-DD');
                        const end = new DateTime(d[1], 'YYYY-MM-DD');

                        return [start, end];
                    }

                    return new DateTime(d, 'YYYY-MM-DD');
                })
            },
        };
    });
});


function deepMergeConfigs(target, source) {
    for (let key in source) {
        if (source[key] instanceof Object && key in target) {
            Object.assign(source[key], deepMergeConfigs(target[key], source[key]));
        }
    }
    Object.assign(target, source);
    return target;
}