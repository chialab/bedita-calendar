export function defineCalendarFilters(DNA) {
    const { window, extend, customElements } = DNA;

    const CalendarFilters = class CalendarFilters extends extend(window.HTMLFormElement) {
        static get properties() {
            return {
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
            };
        }

        connectedCallback() {
            super.connectedCallback();
            this.addEventListener('change', this.onChange, true);
        }

        disconnectedCallback() {
            this.removeEventListener('change', this.onChange);
            super.disconnectedCallback();
        }

        onChange = (event) => {
            const target = event.target;
            if (!target) {
                return;
            }
            const name = target.getAttribute('name');
            switch (name) {
                case this.dayParam:
                case this.monthParam:
                case this.yeatParam:
                    break;
                default:
                    return;
            }

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
        };
    }

    customElements.define('calendar-filters', CalendarFilters, {
        extends: 'form',
    });

    return CalendarFilters;
}
