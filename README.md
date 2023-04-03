# Chialab/Calendar

**Calendar** is a [BEdita 4](https://www.bedita.com/) plugin designed to render and filter calendar views.

## Usage

You can install the plugin using [Composer](https://getcomposer.org).

The recommended way to install Composer packages is:

```sh
$ composer require chialab/calendar
```

Then, you have to load it as plugin in your Cake application:

**src/Application.php**
```php
$this->addPlugin('Chialab/Calendar');
```

Please read the [Wiki](https://github.com/chialab/bedita-calendar/wiki) to correctly setup the application.


## Testing

[![GitHub Actions tests](https://github.com/chialab/bedita-calendar/actions/workflows/test.yml/badge.svg?event=push&branch=main)](https://github.com/chialab/bedita-calendar/actions/workflows/test.yml?query=event%3Apush+branch%3Amain)
[![codecov](https://codecov.io/gh/chialab/bedita-calendar/branch/main/graph/badge.svg)](https://codecov.io/gh/chialab/bedita-calendar)

Since some FrontendKit queries uses specific MySQL syntax, you must provide a DSN url for a test database before running tests:

```sh
$ export db_dsn='mysql://root:****@localhost/bedita4_calendar'
```

Then, you can launch tests using the `test` composer command:

```sh
$ composer run test
```

---

## License

**Chialab/Calendar** is released under the [MIT](https://gitlab.com/chialab/bedita-calendar/-/blob/main/LICENSE) license.

