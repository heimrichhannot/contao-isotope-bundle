# Changelog
All notable changes to this project will be documented in this file.

## [0.11.0] - 2018-06-29

#### Added
* order conditions can now me dependent on certain product types or products

## [0.10.4] - 2018-06-28

#### Fixed
* date comparasion in 

## [0.10.3] - 2018-06-18

#### Fixed
* wrong framework call in ProductListPlus module

## [0.10.2] - 2018-06-13

#### Changed
* changed count handling on bookingDates; if no count is given the whole stock is blocked

## [0.10.0] - 2018-06-08

#### Added
* Backend Booking information for product

#### Changed 
* better usage of encore bundle for frontend

## [0.9.3] - 2018-06-05

#### Fixed
* error added items with no booking information to cart

## [0.9.2] -2018-06-04

#### Fixed
* empty pids for booking items
* booking selection not mandatory

## [0.9.1] - 2018-06-04

#### Fixed
* error on save (removed sync on Model save)
* return wrong product model type from model methods (added ProductModel as "standard" product isotope type)

## [0.9.0] - 2018-06-04

#### Added
* sync method for ProductModel and ProductDataModel
* sync method now called on save for ProductModel and ProductDataModel
* findAllProducts method for ProductDataManager
* moved 3 config fields to product data table

#### Fixed 
* findAll method for product model not working due isotope TypeAgent inheritance
* upgrade command not working due failing findAll method

You need to call the database updater!
You should call the upgrade command!

## [0.8.1] - 2018-06-01

#### Fixed
* error adding a item with no booking functionality to cart

## [0.8.0] - 2018-06-01

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