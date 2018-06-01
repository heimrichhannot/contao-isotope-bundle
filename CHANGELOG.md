# Changelog
All notable changes to this project will be documented in this file.

## [0.8.0] -2018-05-31

#### Added
* validation for product booking an add to cart and checkout

#### Changed
* ProductModel now extends Isotope\Model\Product\Standard
* removed heimrichhannot/contao-request dependency
* refactoring

#### Fixed
* booking plan reservation not working

## [0.7.0] -2018-05-31

#### Added
* upgrade command for product data

## [0.6.0] -2018-05-30

#### Changes 
* product data now save in own database with own model mirrowed into product model
* refactoring

#### Fixed
* Module Stock reports
* Update stock on order delete

## [0.5.0] - 2018-05-29
* added ability to reserve a product for booking dates from backend 

## [0.4.0] - 2018-05-23
* fixed price calculation for bookingTime items 

#### Fixed
* updated slick dependency

## [0.1.3] - 2018-05-03

#### Fixed
* updated slick dependency

## [0.1.3] - 2018-05-03

#### Fixed
* updated slick dependency

## [0.1.2] - 2018-05-02

#### Fixed
* wrong path to bundle js in package.json