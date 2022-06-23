export function defineCalendarFilters(DNA) {
    const { window, extend, customElements } = DNA;

    const CalendarFilters = class CalendarFilters extends extend(window.HTMLFormElement) {
        static get properties() {
            return {
                dateParam: {
                    type: String,
                    attribute: 'date-param',
                    defaultValue: 'date',
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
                case this.dateParam:
                case this.dayParam:
                case this.monthParam:
                case this.yearParam:
                case this.categoriesParam:
                case this.tagsParam:
                case `${this.categoriesParam}[]`:
                case `${this.tagsParam}[]`:
                    this.updateState();
                    return this.requestSubmit();
            }
        };
    };

    customElements.define('calendar-filters', CalendarFilters, {
        extends: 'form',
    });

    return CalendarFilters;
}
