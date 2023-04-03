export function defineCalendarFilters(DNA) {
    const { window, dispatchEvent, extend, customElements } = DNA;

    const CalendarFilters = class CalendarFilters extends extend(window.HTMLFormElement) {
        static get properties() {
            return {
                rangeParam: {
                    type: String,
                    attribute: 'range-param',
                    defaultValue: 'range',
                },
                categoriesParam: {
                    type: String,
                    attribute: 'categories-param',
                    defaultValue: 'categories',
                },
                tagsParam: {
                    type: String,
                    attribute: 'tags-param',
                    defaultValue: 'tags',
                },
                dayParam: {
                    type: String,
                    attribute: 'day-param',
                    defaultValue: 'day',
                },
                monthParam: {
                    type: String,
                    attribute: 'month-param',
                    defaultValue: 'month',
                },
                yearParam: {
                    type: String,
                    attribute: 'year-param',
                    defaultValue: 'year',
                },
                categories: {
                    type: Array,
                    state: true,
                    attribute: 'categories',
                    defaultValue: [],
                    fromAttribute(val) {
                        if (!val) {
                            if (this.categories.length === 0) {
                                return this.categories;
                            }
                            return [];
                        }

                        const list = val.split(',').map((str) => str.trim()).sort();
                        if (this.categories.join(',') === list.join(',')) {
                            return this.categories;
                        }
                        return list;
                    },
                    toAttribute(val) {
                        if (val.length === 0) {
                            return null;
                        }
                        return val.map((str) => str.trim()).sort().join(',');
                    },
                },
                tags: {
                    type: Array,
                    state: true,
                    attribute: 'tags',
                    defaultValue: [],
                    fromAttribute(val) {
                        if (!val) {
                            if (this.tags.length === 0) {
                                return this.tags;
                            }
                            return [];
                        }

                        const list = val.split(',').map((str) => str.trim()).sort();
                        if (this.tags.join(',') === list.join(',')) {
                            return this.tags;
                        }
                        return list;
                    },
                    toAttribute(val) {
                        if (val.length === 0) {
                            return null;
                        }
                        return val.map((str) => str.trim()).sort().join(',');
                    },
                },
            };
        }

        connectedCallback() {
            super.connectedCallback();
            this.addEventListener('change', this.onChange, true);
            this.addEventListener('click', this.onClick, true);
            this.updateState();
        }

        disconnectedCallback() {
            this.removeEventListener('change', this.onChange);
            this.removeEventListener('click', this.onClick);
            super.disconnectedCallback();
        }

        updateState() {
            const data = new FormData(this);
            this.categories = data.getAll(`${this.categoriesParam}[]`);
            this.tags = data.getAll(`${this.tagsParam}[]`);
        }

        updateRange() {
            const rangeInput = this.querySelector(`[name="${this.rangeParam}[]"]`);
            if (!rangeInput) {
                return;
            }

            rangeInput.value = [
                this.querySelector(`[name="${this.yearParam}"]`)?.value,
                this.querySelector(`[name="${this.monthParam}"]`)?.value || 1,
                this.querySelector(`[name="${this.dayParam}"]`)?.value || 1,
            ].join('-');
            rangeInput.removeAttribute('form');

            const radioInputs = this.querySelectorAll(`input[type="radio"][name="${this.rangeParam}"]`);
            for (let i = 0; i < radioInputs.length; i++) {
                radioInputs[i].setAttribute('form', '');
            }
        }

        excludeRange() {
            const rangeInput = this.querySelector(`[name="${this.rangeParam}[]"]`);
            if (!rangeInput) {
                return;
            }
            rangeInput.setAttribute('form', '');

            const radioInputs = this.querySelectorAll(`input[type="radio"][name="${this.rangeParam}"]`);
            for (let i = 0; i < radioInputs.length; i++) {
                radioInputs[i].removeAttribute('form');
            }
        }

        onClick = (event) => {
            const target = event.target;
            if (!target) {
                return;
            }

            if (target.matches('input[type="radio"]:checked + label')) {
                const input = target.previousElementSibling;
                input.checked = false;
                event.preventDefault();
                input.dispatchEvent(new Event('change'));
            }
        };

        onChange = (event) => {
            const target = event.target;
            if (!target) {
                return;
            }

            const name = target.getAttribute('name');
            const type = target.getAttribute('type');
            switch (name) {
                case this.monthParam:
                case this.yearParam:
                    this.updateDateFilter();
                    this.updateRange();
                    break;
                case this.dayParam:
                    this.updateRange();
                    break;
                case this.rangeParam:
                    this.excludeRange();
                    this.requestSubmit();
                    break;
                case this.categoriesParam:
                case this.tagsParam:
                case `${this.categoriesParam}[]`:
                case `${this.tagsParam}[]`:
                    this.updateState();
                    this.requestSubmit();
                    break;
                default: {
                    if (type === 'radio' || type === 'checkbox') {
                        this.requestSubmit();
                    }
                }
            }
        };

        updateDateFilter() {
            const days = this.querySelector(`[name="${this.dayParam}"]`);
            if (!days) {
                return;
            }

            const data = new FormData(this);
            const month = data.get(this.monthParam);
            const year = data.get(this.yearParam);
            const date = new Date(year, month, 0);

            days.innerHTML = '';
            let num = date.getDate();
            while (num--) {
                const option = this.ownerDocument.createElement('option');
                option.value = num + 1;
                option.textContent = num + 1;
                option.selected = num === 0;
                days.insertBefore(option, days.firstChild);
            }
        }

        requestSubmit() {
            if (window.HTMLFormElement.prototype.requestSubmit) {
                super.requestSubmit();
                return;
            }

            if (!dispatchEvent(this, 'submit')) {
                return;
            }

            this.submit();
        }
    };

    customElements.define('calendar-filters', CalendarFilters, {
        extends: 'form',
    });

    return CalendarFilters;
}
