# Changelog
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [1.10.0] - 2025-03-05
### IMPORTANT
- Support for PHP 7.0 has been discontinued. If you are running PHP 7.0, you MUST upgrade PHP before installing this addon. Failure to do that will disable addon functionality.

### Changed
- The minimum WPForms version supported is 1.9.1.
- Updated Authorize.Net PHP SDK to v2.0.4.

## [1.9.0] - 2024-08-21
### Added
- Notice in the Form Builder when Authorize.Net is not connected or payments processing is not configured.
- Notice in the WPForms > Settings > Payments admin page when a selected currency is not supported by Authorize.Net.

### Changed
- Updated logo to reflect the company's rebranding.
- Improved rendering of Payment fields according to W3C requirements.
- Updated Authorize.Net PHP SDK to v2.0.3.
- The minimum WPForms version supported is 1.9.0.

### Fixed
- Better compatibility with default Block themes.
- There was an error on multi-payment form submission.
- There was an API error in the case of a long subscription title.

## [1.8.0] - 2023-09-27
### IMPORTANT
- Support for PHP 5.6 has been discontinued. If you are running PHP 5.6, you MUST upgrade PHP before installing WPForms Authorize.Net 1.8.0. Failure to do that will disable WPForms Authorize.Net functionality.
- Support for WordPress 5.4 and below has been discontinued. If you are running any of those outdated versions, you MUST upgrade WordPress before installing WPForms Authorize.Net 1.8.0. Failure to do that will disable WPForms Authorize.Net functionality.

### Changed
- Minimum WPForms version supported is 1.8.4.

## [1.7.0] - 2023-08-08
### Changed
- Minimum WPForms version supported is 1.8.3.

### Fixed
- Card type for payment method was missing on the single Payment page.
- Payment field was not displayed in the Elementor Builder.

## [1.6.1] - 2023-06-09
### Fixed
- There were situations when PHP notices were generated on the Single Payment page.

## [1.6.0] - 2023-06-08
### Added
- Compatibility with WPForms 1.8.2.

### Changed
- Minimum WPForms version supported is 1.8.2.

### Fixed
- Payment error was displayed too close to the Description field.
- JavaScript error occurred when the user was asked to enter verification information for a payment form locked with the Form Locker addon.

## [1.5.1] - 2023-03-23
### Fixed
- There was a styling conflict with PayPal Commerce field preview in the Form Builder.
- Subfield validation error messages were overlapping each other in certain themes.

## [1.5.0] - 2023-03-21
### Added
- Compatibility with the upcoming WPForms v1.8.1 release.

### Fixed
- In some cases validation errors were not removed after correcting the values and submitting the form again.
- Local validation error messages overlapped Authorize.Net API error messages.
- Authorize.Net validation error codes are now displayed only in the console.
- On multi-page forms it was possible to continue to the next page even if the field validation failed.
- Expiration and Security Code subfields were too narrow in the Form Builder preview.

## [1.4.0] - 2022-10-05
### Changed
- Show settings in the Form Builder only if they are enabled.
- On form preview, display an alert message with an error when payment configurations are missing.
- Minimum WPForms version supported is 1.7.5.5.

### Fixed
- The form couldn't be submitted if several configured payment gateways were executed according to Conditional Logic.
- A.Net completed payment notifications were sent for non-completed payments.

## [1.3.0] - 2022-06-28
### Changed
- Minimum WPForms version supported is 1.7.5.
- Reorganized locations of 3rd party libraries.

### Fixed
- Compatibility with WordPress Multisite installations.

## [1.2.0] - 2022-03-16
### Added
- Compatibility with WPForms 1.6.8 and the updated Form Builder.
- Compatibility with WPForms 1.7.3 and Form Revisions.

### Changed
- Updated Authorize.Net PHP SDK to v2.0.2 for PHP 7.4 and PHP 8 support.
- Minimum WPForms version supported is 1.6.7.1.

## [1.1.0] - 2021-03-31
### Added
- Transaction-specific errors logging to make payment issues identification easier.
- Account credentials validation on WPForms Payments settings page.
- Optional address field mapping for Authorize.Net accounts requiring customer billing address.
- Email Notifications option to limit to completed payments only.

### Changed
- Replaced jQuery.ready function with recommended way since jQuery 3.0.

## [1.0.2] - 2020-08-06
### Fixed
- Card field can be mistakenly processed as hidden under some conditional logic configurations.

## [1.0.1] - 2020-08-05
### Fixed
- Conditionally hidden Authorize.net field should not be processed on form submission.

## [1.0.0] - 2020-05-28
### Added
- Initial release.
