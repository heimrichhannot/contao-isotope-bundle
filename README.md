# Contao Isotope Bundle

[![](https://img.shields.io/packagist/v/heimrichhannot/contao-isotope-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-isotope-bundle)
[![](https://img.shields.io/packagist/dt/heimrichhannot/contao-isotope-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-isotope-bundle)
[![Build Status](https://travis-ci.org/heimrichhannot/contao-isotope-bundle.svg?branch=master)](https://travis-ci.org/heimrichhannot/contao-isotope-bundle)
[![Coverage Status](https://coveralls.io/repos/github/heimrichhannot/contao-isotope-bundle/badge.svg?branch=master)](https://coveralls.io/github/heimrichhannot/contao-isotope-bundle?branch=master)

This bundle offers additional functionality for the isotope ecommerce system.

This bundle is a port of [Contao Isotope Module](https://github.com/heimrichhannot/contao-isotope_plus).

## Features

> List not complete

* Stock managment
* Sets
* Booking functionality (book a ressource (product) for specific time)

## Install 

```php
composer require heimrichhannot/contao-isotope-bundle
```

You need to call the database update tool after install.

We recommend use [Contao Encore](https://github.com/heimrichhannot/contao-encore-bundle) for assets managment.

## Usage

### Upgrade from older version or module

If you upgrade from [Isotope_Plus](https://github.com/heimrichhannot/contao-isotope_plus) or the bundle prior to version 0.7, you should use the upgrade command to setup the product data table.

```php
# From project folder:
php vendor/bin/contao-console huh:isotope:upgrade
```

This command fills the product data table based on the data stored in product table.

## Developers

### Commands

For more informations use the help function (`[command] --help`).

Command | Description | Attributes | Options
--------|-------------|------------|--------
huh:isotope:upgrade | Mirrors product data to the product data table | - | overwriteExistingEntries


