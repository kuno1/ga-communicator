<?php

namespace Kunoichi\GaCommunicator\Utility;


use Hametuha\SingletonPattern\Singleton;

/**
 * Class PlaceHolders
 *
 * @package ga-communicator
 */
class PlaceHolders extends Singleton {

	/**
	 * @var array[] List of placeholders.
	 */
	protected $place_holders = [];

	protected function init() {
		$placeholders        = apply_filters( 'ga_communicator_placeholders', [
			[
				'name'        => 'post_id',
				'description' => __( 'Single post ID. If page is not singular, always 0.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? get_queried_object_id() : 0;
				},
			],
			[
				'name'        => 'blog_id',
				'description' => __( 'Blog ID.', 'ga-communicator' ),
				'callback'    => function () {
					return get_current_blog_id();
				},
			],
			[
				'name'        => 'author_id',
				'description' => __( 'If current page is a singular post, return author ID. Else, always 0.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? get_queried_object()->post_author : 0;
				},
			],
			[
				'name'        => 'term_id',
				'description' => __( 'On taxonomy archive page, returns term ID. Else, always 0', 'ga-communicator' ),
				'callback'    => function () {
					if ( is_category() || is_tag() || is_tax() ) {
						return get_queried_object_id();
					} else {
						return 0;
					}
				},
			],
		] );
		$this->place_holders = array_filter( $placeholders, function ( $placeholder ) {
			return ! empty( $placeholder );
		} );
	}

	/**
	 * Return placeholders.
	 *
	 * @return array[]
	 */
	public function get() {
		return $this->place_holders;
	}

	/**
	 * Return replaced strings.
	 *
	 * @param string $tag
	 *
	 * @return string
	 */
	public function replace( $tag ) {

	}

	/**
	 * Get script tag to render.
	 *
	 * @param string $key        Tag type. gtag, universal,
	 * @param string $id         Measurement ID. e.g. UA-100000-1
	 * @param string $additional Additional scripts to insert.
	 *
	 * @return string
	 */
	public function tag( $key, $id, $additional = '' ) {
		switch ( $key ) {
			case 'gtag':
				$tag = <<<'HTML'
<!-- Global Site Tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=%1$s"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  %2$s

  gtag( 'config', '%1$s' );
</script>
HTML;
				return sprintf( $tag, $id, $additional );
				break;
			case 'universal':
				$tag = <<<'HTML'
<!-- Google Analytics -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', '%1$s', 'auto');

%2$s

ga('send', 'pageview');
</script>
<!-- End Google Analytics -->
HTML;
				return sprintf( $tag, $id, $additional );
				break;
			case 'manual':
				return $additional;
			default:
				return '';
		}
	}
}
