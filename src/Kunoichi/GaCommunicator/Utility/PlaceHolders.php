<?php

namespace Kunoichi\GaCommunicator\Utility;


use Kunoichi\GaCommunicator;
use Kunoichi\GaCommunicator\Pattern\Singleton;

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
				'type'        => 'int',
				'description' => __( 'Single post ID. If page is not singular, always 0.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? get_queried_object_id() : 0;
				},
			],
			[
				'name'        => 'post_type',
				'type'        => 'string',
				'description' => __( 'Post type in singular page.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? get_queried_object()->post_type : 0;
				},
			],
			[
				'name'        => 'post_date',
				'type'        => 'string',
				'description' => __( 'Single post date. If page is not singular, always empty string. You can also specify the format like <code>%post_date:Y-m-d%</code>.', 'ga-communicator' ),
				'callback'    => function ( $format = 'Y-m-d H:i:s' ) {
					return is_singular() ? mysql2date( $format, get_queried_object()->post_date ) : '';
				},
			],
			[
				'name'        => 'post_title_count',
				'type'        => 'int',
				'description' => __( 'Character count of single post title. If page is not singular, always 0.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? mb_strlen( get_queried_object()->post_title ) : 0;
				},
			],
			[
				'name'        => 'post_content_count',
				'type'        => 'int',
				'description' => __( 'Character count of single post content. If page is not singular, always 0.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? mb_strlen( str_replace( [ "\n", "\r" ], '', get_the_content() ) ) : 0;
				},
			],
			[
				'name'        => 'blog_id',
				'type'        => 'int',
				'description' => __( 'Blog ID.', 'ga-communicator' ),
				'callback'    => function () {
					return get_current_blog_id();
				},
			],
			[
				'name'        => 'author_id',
				'type'        => 'int',
				'description' => __( 'If current page is a singular post, return author ID. Else, always 0.', 'ga-communicator' ),
				'callback'    => function () {
					return is_singular() ? (int) get_queried_object()->post_author : 0;
				},
			],
			[
				'name'        => 'post_terms',
				'type'        => 'string',
				'description' => __( 'On single page, returns CSV value for all assigned terms in any taxonomy like <code>_11_,20_,_30_</code>. Else, always empty string.', 'ga-communicator' ),
				'callback'    => function () {
					if ( is_singular() ) {
						$taxonomies = get_post_taxonomies( get_queried_object() );
						$term_ids   = [];
						foreach ( $taxonomies as $taxonomy ) {
							$terms = get_the_terms( get_queried_object(), $taxonomy );
							if ( $terms && ! is_wp_error( $terms ) ) {
								foreach ( $terms as $term ) {
									$term_ids[] = sprintf( '_%d_', $term->term_id );
								}
							}
						}
						return implode( ',', $term_ids );
					} else {
						return '';
					}
				},
			],
			[
				'name'        => 'term_id',
				'type'        => 'int',
				'description' => __( 'On taxonomy archive page, returns term ID. Else, always 0', 'ga-communicator' ),
				'callback'    => function () {
					if ( is_category() || is_tag() || is_tax() ) {
						return get_queried_object_id();
					} else {
						return 0;
					}
				},
			],
			[
				'name'        => 'taxonomy',
				'type'        => 'string',
				'description' => __( 'On taxonomy archive page, returns taxonomy name. Else, always empty.', 'ga-communicator' ),
				'callback'    => function () {
					if ( is_category() || is_tag() || is_tax() ) {
						return get_queried_object()->taxonomy;
					} else {
						return '';
					}
				},
			],
		] );
		$this->place_holders = array_filter( $placeholders, function ( $placeholder ) {
			if ( empty( $placeholder['name'] ) || empty( $placeholder['description'] ) ) {
				return false;
			}
			if ( ! isset( $placeholder['callback'] ) || ! is_callable( $placeholder['callback'] ) ) {
				return false;
			}
			return true;
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
		foreach ( $this->place_holders as $place_holder ) {
			$tag = str_replace( "%{$place_holder['name']}%", $place_holder['callback'](), $tag );
			// Detect placeholder with parameter.
			if ( preg_match_all( "/%{$place_holder['name']}:(.*)%/", $tag, $matches ) ) {
				foreach ( $matches[0] as $matched_key ) {
					list( $placeholder_key, $parameter ) = explode( ':', str_replace( '%', '', $matched_key ), 2 );
					$tag                                 = str_replace( $matched_key, $place_holder['callback']( $parameter ), $tag );
				}
			}
		}
		return $tag;
	}

	/**
	 * Sandbox content.
	 */
	public function sandbox() {
		return json_encode( GaCommunicator::get_instance()->ga4_default_json(), JSON_PRETTY_PRINT );
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
  var gtagConfig = {};
  %2$s

  gtag( 'config', '%1$s', gtagConfig );
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
