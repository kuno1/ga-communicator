# ga-communicator

Let your WordPress communicate with Google Analytics API.

## Getting Start

Install via [composer](https://getcomposer.org/).

### Install

```
composer require kunoichi/ga-communicator
```

### Include

From your theme or plugin, include autoloader.

```php
// This path will be changed depending on your use case.
require __DIR__ . '/vendor/autload.php';
```

### Run Boostrap

Calling bootstrap function, all hooks will be registered.

```php
\Kunoichi\GaCommunicator::get_instance();
```

Now your code is ready to run. 

### Register Service Account

To communicate Google Analytics, you need permission to do so.

Get service account key at Google API Console([document](https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php?hl=ja)).

A service account is something like a bot which has email and perform like a virtual Google account.

1. Create service account. You may be requested to create a new project, or else you can also use an exsting project. The bot belongs to the project.
2. Get **private key** in JSON format for the serivce account.
3. Enable **Analytics Reporting API** and **Google Analytics API** for the project.
4. Copy the email address of the service account and add it to a member of your Google Analytics account(properties, views and so on).
5. Go to your WordPress admin **Setting > Google Analytics Setting** and save the private key which you get at step 2.  
**NOTICE:** Save whole credentials including empty line with <kbd>⌘</kbd>+<kbd>A</kbd> and <kbd>⌘</kbd>+<kbd>C</kbd>. Otherwise, your key will be considered as invalid by Google's API.

## Examples

Here's example for this API.

### Get popular posts

`GaComunicator->popular_posts()` method will:

1. Request page views and path information from Analytics Reporting API.
2. Convert path information to post ID.
3. Query posts with IDs and returns `WP_Query` object.

With this method, you can get popular posts in WordPress way.

#### Arguments

`$query`

An array merged into `new WP_Query( $query )`. If you need just post type `faq`, `$query` is like below:

```
$query = [
	'post_type' => 'faq',
];
```

`$conditions`

An array of conditions to filter results. Default is defined inside [code](https://github.com/kuno1/ga-communicator/blob/master/src/Kunoichi/GaCommunicator.php#L150-L237) like below:

```php
$conditions = [
	'path_regexp' => $this->get_permalink_filter(), // Default is permalink structure.
	'number'      => 10, // Post count.
	'days_before' => 30,
	'offset_days' => 0,
	'start'       => '',
	'end'         => '',
];
```

To get posts well visited in recent 7 days(e.g. weekly ranking), specify `days_before`. `offset_days` works as an adjustment.

```php
// Get Sun-Mon last week ranking.
$contiond = [
	'days_before' => 7,
	'offset_days' => -1 * date_i18n( 'w' ), // Set origin to Sunday.
];
```

If you need a list of popular posts in specific range(e.g. Olympic year), specify `start` and `end` in YYYY-mm-dd.

```php
$conditions = [
	'start' => '2020-06-01', // As you know, this hasn't actually happen.
	'end'   => '2020-08-31',
];
```

#### Usage

Let's get popular posts `post_type=faq` in recent 7 days.

```php
$query = \Kunoichi\GaCommunicator::get_instance()->popular_posts( [
	'post_type' => 'faq',
], [
	'path_regexp' => '^/faq/[0-9]+/$', // Regular expression to filter path.
	'number'      => 10, // Number of posts.
	'days_before' => 7, // Recent 7 days.
] );
if ( $query ) {
	while ( $query->have_posts() ) {
		$query->the_post();
		// You can do things in WordPress way.
		get_template_part( 'template-parts/loop', get_post_type() );
	}
}
```

Cache result makes your site's performance nicer.

### Free Request

You can get any information in your Google Analytics account with this library.

You need understanding about Mertic and Dimensions.

`GaCommunicator->get_report` will provide 
