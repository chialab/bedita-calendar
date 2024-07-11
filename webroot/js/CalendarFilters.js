export function defineCalendarFilters(DNA) {
    const BaseElement = DNA.HTML ?
        // DNA 4
        DNA.HTML.Form :
        // DNA 3
        DNA.extend(HTMLFormElement);
    const CalendarFilters = class CalendarFilters extends BaseElement {
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
            }
        };

        updateDateFilter() {
            const days = this.querySelector(`[name="${this.dayParam}"]`);
            const months = this.querySelector(`[name="${this.monthParam}"]`);
            const years = this.querySelector(`[name="${this.yearParam}"]`);
            if (!days || !months || !years) {
                return;
            }

            const day = parseInt(days.value);
            const month = parseInt(months.value);
            const year = parseInt(years.value);
            if (isNaN(month) || isNaN(year)) {
                return;
            }

            const date = new Date(year, month, 0);
            const maxDays = date.getDate();

            days.innerHTML = '';
            let num = maxDays;
            while (num--) {
                const dayValue = num + 1;
                const option = this.ownerDocument.createElement('option');
                option.value = dayValue;
                option.textContent = dayValue;
                if (day <= maxDays) {
                    option.selected = dayValue === day;
                } else {
                    option.selected = dayValue === 1;
                }
                days.insertBefore(option, days.firstChild);
            }
        }

        requestSubmit() {
            if (HTMLFormElement.prototype.requestSubmit) {
                super.requestSubmit();
                return;
            }

            if (!DNA.dispatchEvent(this, 'submit')) {
                return;
            }

            this.submit();
        }
    };

    if (DNA.define) {
        // DNA 4
        DNA.define('calendar-filters', CalendarFilters, {
            extends: 'form',
        });
    } else if (DNA.customElements) {
        // DNA 3
        customElements.define('calendar-filters', CalendarFilters, {
            extends: 'form',
        });
    }

    return CalendarFilters;
}
