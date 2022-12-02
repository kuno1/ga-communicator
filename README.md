# Google Analytics Communicator

Tags: gutenberg, block editor, iframe  
Contributors: tarosky, Takahashi_Fumiki  
Tested up to: 5.8  
Requires at least: 5.4  
Requires PHP: 5.6  
Stable Tag: nightly  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Let your WordPress communicate with Google Analytics API.

## Description

This plugin has custom function to 

### Default Feature

- Setting screen. You can check the connetction and set up GA tags.
- Popular posts widget.

### Register Service Account and Setup

To communicate Google Analytics, WordPress needs permission to do so.

Service Account is the permission.

Get service account key at Google API Console([document](https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php?hl=ja)).

A service account is something like a bot which has email and perform like a virtual Google account.

1. Create service account. You may be requested to create a new project, or else you can also use an exsting project. The bot belongs to the project.
2. Get **private key** in JSON format for the serivce account.
3. Enable **Analytics Reporting API** and **Google Analytics API** for the project.
4. Copy the email address of the service account and add it to a member of your Google Analytics' account, properties, or profiles. It depends on your Google Analytics permission policy.
5. Go to your WordPress admin **Setting > Google Analytics Setting** and save the private key which you get at step 2.  

**NOTICE:** Save whole credentials including empty line with <kbd>⌘</kbd>+<kbd>A</kbd> and <kbd>⌘</kbd>+<kbd>C</kbd>. Otherwise, your key will be considered as invalid by Google's API.

If the service account is valid, you can see your Google Analytics Properties in your WordPress Admin screen.

### Custom Use

If setup is ready, you can communicate with Google Analytics through the function `ga_communicator_get_report( $config )`. It's an utility function to access [batchGet API](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet).

Please visit our [Wiki](https://github.com/kuno1/ga-communicator/wiki) to find many code examples.

## Installation

### From Plugin Repository

Click install and activate it.

### Via Composer

You can install this plugin as a composer library. See our [Wiki](https://github.com/kuno1/ga-communicator/wiki/Install-via-Composer).

## FAQ

### Where can I get supported?

Please create new ticket on support forum.

### How can I contribute?

Create a new [issue](https://github.com/kuno1/ga-communicator/issues) or send [pull requests](https://github.com/kuno1/ga-communicator/pulls).

## Changelog



### 2.0.0

* Works as a single WordPress plugin.

### 1.0.0

* First release as composer library.
