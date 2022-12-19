# Google Analytics Communicator

Tags: google-analytics, api  
Contributors: tarosky, Takahashi_Fumiki  
Tested up to: 6.1  
Requires at least: 5.9  
Requires PHP: 7.0  
Stable Tag: nightly  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Let your WordPress communicate with Google Analytics API.

## Description

This plugin has custom functions to connect with Google Analytics.

**NOTICE:** [Google will stop Universal Analytics(UA) at July 1st, 2023](https://support.google.com/analytics/answer/11583528). Since then, you will be able to create only Google Analytics 4(GA4) accounts. API for UA is [Core Reporting API](https://developers.google.com/analytics/devguides/reporting/core/v4?hl=ja), and one for GA4 is [Google Analytics Data API](https://developers.google.com/analytics/devguides/reporting/data/v1). This API change is a breaking change. Please see [our wiki](https://github.com/kuno1/ga-communicator/wiki/MIgrate-to-Google-Analytics-Data-API-for-GA4) to check what you should do for the migration.

### Default Feature

- Setting screen. You can check the connection and set up GA tags.
- Popular posts widget.

### Register Service Account and Setup

To communicate with Google Analytics, WordPress needs permission to do so.

Service Account works as permission.

Get the service account key at Google API Console([document](https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php?hl=ja)).

A service account is like a bot with email and performs like a virtual Google account.

1. Create a service account. You may be requested to create a new project, or else you can also use an existing project. The bot belongs to the project.
2. Get **private key** in JSON format for the service account.
3. Enable **Analytics Reporting API** and **Google Analytics API** for the project.
4. Copy the service account's email address and add it to a member of your Google Analytics account, properties, or profiles. It depends on your Google Analytics permission policy.
5. Go to your WordPress admin **Setting > Google Analytics Setting** and save the private key that you get in step 2.  

**NOTICE:** Save whole credentials including empty line with <kbd>⌘</kbd>+<kbd>A</kbd> and <kbd>⌘</kbd>+<kbd>C</kbd>. Otherwise, your key will be considered invalid by Google's API.

If the service account is valid, you can see your Google Analytics Properties in your WordPress Admin screen.

### Custom Use

If the setup is ready, you can communicate with Google Analytics through the function `ga_communicator_get_report( $config )`. It's a utility function to access [batchGet API](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet).

Please visit our [Wiki](https://github.com/kuno1/ga-communicator/wiki) to find many code examples.

## Installation

### From Plugin Repository

Click install and activate it.

### Via Composer

You can install this plugin as a composer library. See our [Wiki](https://github.com/kuno1/ga-communicator/wiki/Install-via-Composer).

## FAQ

### Where can I get support?

Please create a new ticket on the support forum.

### How can I contribute?

Create a new [issue](https://github.com/kuno1/ga-communicator/issues) or send [pull requests](https://github.com/kuno1/ga-communicator/pulls).

## Changelog

### 3.0.0

* Add [Google Analytics Data API](https://developers.google.com/analytics/devguides/reporting/data/v1) support. [Core Reporting API](https://developers.google.com/analytics/devguides/reporting/core/v4?hl=ja) will be deprecated in 2023.
* Drop support for PHP 5.6

### 2.0.0

* Works as a single WordPress plugin.

### 1.0.0

* First release as composer library.
