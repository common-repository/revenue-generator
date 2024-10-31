<?php
/**
 * Utility class containing common methods used in plugin.
 *
 * Initial code was taken from https://github.com/laterpay/laterpay-wordpress-plugin -> laterpay/application/Helper/String.php
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

/**
 * Class Utility
 */
class Utility {

	/**
	 * Get the first given number of words from a string.
	 *
	 * @param string $string     Actual string.
	 * @param int    $word_limit Limit of words.
	 *
	 * @return  string
	 */
	public static function limit_words( $string, $word_limit ) {
		$words = explode( ' ', $string );

		return implode( ' ', array_slice( $words, 0, $word_limit ) );
	}

	/**
	 * Determine the number of words to be shown for teaser/overlay.
	 *
	 * @param string $content Post content.
	 * @param int    $min     Minimum number of words.
	 * @param int    $max     Maximum number of words.
	 *
	 * @return int $number_of_words
	 */
	public static function determine_number_of_words( $content, $min = 50, $max = 100 ) {
		$content     = preg_replace( '/\s+/', ' ', $content );
		$total_words = count( explode( ' ', $content ) );

		// Static values passed for creating the number of words.
		$percent = max( min( 5, 100 ), 1 );

		$number_of_words = $total_words * ( $percent / 100 );
		$number_of_words = max( min( $number_of_words, $max ), $min );

		return $number_of_words;
	}

	/**
	 * Truncate text.
	 *
	 * Cuts a string to the length of $length and replaces the last characters
	 * with an ellipsis, if the text is longer than length.
	 *
	 * ### Options:
	 *
	 * - `ellipsis` Will be used as ending and appended to the trimmed string (`ending` is deprecated)
	 * - `exact` If false, $text will not be cut mid-word
	 * - `html` If true, HTML tags are handled correctly
	 *
	 * @param string  $text    String to truncate.
	 * @param integer $length  Length of returned string, including ellipsis.
	 * @param array   $options An array of html attributes and options.
	 *
	 * @return string Trimmed string.
	 *
	 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
	 */
	public static function truncate( $text, $length = 100, $options = [] ) {
		$default = [
			'ellipsis' => ' ...',
			'exact'    => true,
			'html'     => false,
			'words'    => false,
		];
		if ( isset( $options['ending'] ) ) {
			$default['ellipsis'] = $options['ending'];
		} elseif ( ! empty( $options['html'] ) ) {
			$default['ellipsis'] = "\xe2\x80\xa6";
		}
		$options = array_merge( $default, $options );

		$ellipsis = $options['ellipsis'];
		$html     = $options['html'];
		$exact    = $options['exact'];
		$words    = $options['words'];

		if ( ! function_exists( 'mb_strlen' ) ) {
			class_exists( 'Multibyte' );
		}

		if ( $html ) {
			$text = preg_replace( '/<! --(.*?)-->/i', '', $text );
			if ( $words ) {
				$length = mb_strlen( self::limit_words( preg_replace( '/<.*?>/', '', $text ), $length ) );
			}
			if ( mb_strlen( preg_replace( '/<.*?>/', '', $text ) ) <= $length ) {
				return $text;
			}
			$total_length = mb_strlen( wp_strip_all_tags( $ellipsis ) );
			$open_tags    = [];
			$truncate     = '';

			preg_match_all( '/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER );
			foreach ( $tags as $tag ) {
				if ( ! preg_match( '/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2] ) ) {
					if ( preg_match( '/<[\w]+[^>]*>/s', $tag[0] ) ) {
						array_unshift( $open_tags, $tag[2] );
					} elseif ( preg_match( '/<\/([\w]+)[^>]*>/s', $tag[0], $close_tag ) ) {
						$pos = array_search( $close_tag[1], $open_tags, true );
						if ( false !== $pos ) {
							array_splice( $open_tags, $pos, 1 );
						}
					}
				}
				$truncate .= $tag[1];

				$content_length = mb_strlen( preg_replace( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3] ) );
				if ( $content_length + $total_length > $length ) {
					$left            = $length - $total_length;
					$entities_length = 0;
					if ( preg_match_all( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE ) ) {
						foreach ( $entities[0] as $entity ) {
							if ( $entity[1] + 1 - $entities_length <= $left ) {
								$left --;
								$entities_length += mb_strlen( $entity[0] );
							} else {
								break;
							}
						}
					}

					$truncate .= mb_substr( $tag[3], 0, $left + $entities_length );
					break;
				} else {
					$truncate     .= $tag[3];
					$total_length += $content_length;
				}
				if ( $total_length >= $length ) {
					break;
				}
			}
		} else {
			if ( $words ) {
				$length = mb_strlen( self::limit_words( $text, $length ) );
			}
			if ( mb_strlen( $text ) <= $length ) {
				return $text;
			}
			$truncate = mb_substr( $text, 0, $length - mb_strlen( $ellipsis ) );
		}
		if ( ! $exact ) {
			$spacepos = mb_strrpos( $truncate, ' ' );
			if ( $html ) {
				$truncate_check = mb_substr( $truncate, 0, $spacepos );
				$last_open_tag  = mb_strrpos( $truncate_check, '<' );
				$last_close_tag = mb_strrpos( $truncate_check, '>' );
				if ( $last_open_tag > $last_close_tag ) {
					preg_match_all( '/<[\w]+[^>]*>/s', $truncate, $last_tag_matches );
					$last_tag = array_pop( $last_tag_matches[0] );
					$spacepos = mb_strrpos( $truncate, $last_tag ) + mb_strlen( $last_tag );
				}
				$bits = mb_substr( $truncate, $spacepos );
				preg_match_all( '/<\/([a-z]+)>/', $bits, $dropped_tags, PREG_SET_ORDER );
				if ( ! empty( $dropped_tags ) ) {
					if ( ! empty( $open_tags ) ) {
						foreach ( $dropped_tags as $closing_tag ) {
							if ( ! in_array( $closing_tag[1], $open_tags, true ) ) {
								array_unshift( $open_tags, $closing_tag[1] );
							}
						}
					} else {
						foreach ( $dropped_tags as $closing_tag ) {
							$open_tags[] = $closing_tag[1];
						}
					}
				}
			}
			$truncate = mb_substr( $truncate, 0, $spacepos );
		}
		$truncate .= $ellipsis;

		if ( $html ) {
			foreach ( $open_tags as $tag ) {
				$truncate .= '</' . $tag . '>';
			}
		}

		return $truncate;
	}

	/**
	 * Get post content in words excluding the markup in it.
	 *
	 * @param string $content Full post content.
	 *
	 * @return int
	 */
	public static function get_word_count( $content ) {
		$content     = trim( strip_tags( $content ) );
		$total_words = count( explode( ' ', $content ) );

		return $total_words;
	}
}
