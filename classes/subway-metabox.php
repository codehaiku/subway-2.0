<?php
/**
 * This file is part of the Subway WordPress Plugin Package.
 * This file contains the class which handles the metabox of the plugin.
 *
 * (c) Joseph Gabito <joseph@useissuestabinstead.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP Version 5.4
 *
 * @category Subway\Metabox
 * @package  Subway
 * @author   Joseph G. <emailnotdisplayed@domain.tld>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version  GIT:github.com/codehaiku/subway
 * @link     github.com/codehaiku/subway The Plugin Repository
 */

namespace Subway;

if (! defined('ABSPATH') ) {
    return;
}

/**
 * Subway Metabox methods.
 *
 * @category Subway\Metabox
 * @package  Subway
 * @author   Joseph G. <emailnotdisplayed@domain.tld>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     github.com/codehaiku/subway The Plugin Repository
 * @since    2.0.9
 */
final class Metabox
{

	/**
	 * Subway visibility meta value,
	 *
	 * @since  2.0.9
	 * @const string VISIBILITY_METAKEY
	 */
	const VISIBILITY_METAKEY = 'subway_visibility_meta_key';

	/**
	 * Registers and update metabox with its intended method below.
	 *
	 * @since  2.0.9
	 * @return void
	 */
	public function __construct()
	{
		add_action(
			'add_meta_boxes',
			array(
				$this,
				'addMetabox'
			)
		);
		add_action(
			'save_post',
			array(
				$this,
				'saveMetaboxValues'
			)
		);
	}

	/**
	 * Initialize metabox
	 *
	 * @since  2.0.9
	 * @access public
	 * @return void
	 */
	public static function initMetabox()
	{
		new Metabox();
	}

	/**
	 * Initialize metabox
	 *
	 * @since  2.0.9
	 * @access public
	 * @return void
	 */
	public function addMetabox()
	{
		$post_types = $this->getPostTypes();

		foreach ( $post_types as $post_type => $value ) {
			add_meta_box(
				'subway_visibility_metabox',
				esc_html__( 'Subway: Visibility Option', 'subway' ),
				array( $this, 'visibilityMetabox' ),
				$post_type,
				'side',
				'low'
			);
		}
	}

	/**
     * This method displays the Subway Visibility checkbox.
     *
     * @param object $post Contains data from the current post.
     *
     * @since  2.0.9
     * @access public
     * @return void
     */
    public function visibilityMetabox( $post )
    {
		$howto           = __( 'Tick the given checkbox to make this post/page private. Otherwise, uncheck to make this post/page public', 'subway' );
		$private_label   = '<strong>' . __( 'private', 'subway' ) . '</strong>';
		$setting_label   = __( 'Check to make this post ', 'subway' ) . $private_label;
		$is_post_public  = self::isPostPublic( $post->ID );
		$is_post_private = self::isPostPrivate( $post->ID );

        // Make sure the form request comes from WordPress
        wp_nonce_field(
            basename(__FILE__),
            'subway_post_visibility_nonce'
        );

		if ( empty( $is_post_private ) ) {
			if ( $is_post_public ) {
				$is_post_private = false;
			}
		}

        ?>
        <label class="screen-reader-text" for="subway-visibility"><?php echo esc_html( $setting_label ); ?></label>

		<label class="subway-visibility-settings-checkbox-label" for="subway-visibility-settings-checkbox">
			<input type="checkbox" class="subway-visibility-settings-checkbox" id="subway-visibility-settings-checkbox" name="subway-visibility-settings-checkbox" value="1" <?php echo checked( 1, esc_attr( $is_post_private ), false ); ?>>
			<?php echo wp_kses( $setting_label, array( 'strong' => array() ) ); ?>
		</label>

        <p class="howto"><?php echo esc_html( $howto ); ?></p>

    <?php
    }

	/**
     * This method verify if nonce is valid then updates a post_meta.
     *
     * @param integer $post_id Contains ID of the current post.
     *
     * @since  2.0.9
     * @access private
     * @return boolean false Returns false if nonce is not valid.
     */
	private function saveVisibilityMetabox( $post_id = '' )
	{
		$public_posts     = Options::getPublicPostsIdentifiers();

		$visibility_field = 'subway-visibility-settings-checkbox';
		$visibility_nonce = filter_input(
			INPUT_POST,
			'subway_post_visibility_nonce',
			FILTER_SANITIZE_STRING
		);

		$post_visibility = filter_input(
			INPUT_POST,
			$visibility_field,
			FILTER_SANITIZE_NUMBER_INT
		);

		$is_valid_visibility_nonce = self::isNonceValid(
			$visibility_nonce
		);

		// verify taxonomies meta box nonce
		if ( false === $is_valid_visibility_nonce ) {
			return;
		}

		if ( empty( $post_visibility ) ) {
			$post_visibility = 0;
		}
		if ( ! empty( $post_visibility ) ) {
			$public_posts[] = $post_id;
		} else {
			unset( $public_posts[ array_search( $post_id, $public_posts ) ] );
		}

		if ( ! empty( $post_id ) ) {
			$public_posts = implode( ", ", $public_posts );
			update_option( 'subway_public_post', $public_posts );
			update_post_meta(
				$post_id,
				self::VISIBILITY_METAKEY,
				$post_visibility
			);
		}
	}

	/**
     * This method runs the methods that handles the update for a post_meta.
     *
     * @param integer $post_id Contains ID of the current post.
     *
     * @since  2.0.9
     * @access public
     * @return boolean false Returns false if nonce is not valid.
     */
    public function saveMetaboxValues( $post_id )
    {
		$this->saveVisibilityMetabox( $post_id );
        return;
	}

	/**
	 * Initialize metabox
	 *
	 * @param array $args    The arguments for the get_post_types().
	 * @param string $output Your desired output for the data.
	 *
	 * @since  2.0.9
	 * @access public
	 * @return void
	 */
	private static function getPostTypes( $args = '', $output = '' )
	{
		if ( empty( $args ) ) {
			$args = array(
				'public'   => true,
			);
			$output = 'names';
		}

		$post_types = get_post_types( $args, $output );

		return $post_types;
	}

	/**
     * This method verify if nonce is valid.
     *
     * @param mixed $nonce the name of a metabox nonce.
     *
     * @since  2.0.9
     * @access public
     * @return boolean true Returns true if nonce is valid.
     */
    private function isNonceValid( $nonce )
    {
        if ( !isset( $nonce ) || !wp_verify_nonce( $nonce, basename( __FILE__ ) ) ) {
            return;
        }

        return true;
    }

	/**
     * Checks if a post is set to private.
     *
     * @param integer $post_id Contains ID of the current post.
     *
     * @since  2.0.9
     * @access public
     * @return boolean true Returns true if post is private. Otherwise false.
     */
    public static function isPostPrivate( $post_id )
    {
		$meta_value = '';

		if ( ! empty( $post_id ) ) {
			$meta_value = get_post_meta( $post_id, self::VISIBILITY_METAKEY, true);
			if ( true == $meta_value ) {
				return true;
			}
		}

		return false;
    }

	/**
     * Checks if a post is set to private.
     *
     * @param integer $post_id Contains ID of the current post.
     *
     * @since  2.0.9
     * @access public
     * @return boolean true Returns true if post is public. Otherwise false.
     */
    public static function isPostPublic( $post_id )
    {
		$public_post = Options::getPublicPostsIdentifiers();

		if ( ! empty( $post_id ) ) {
			if ( ! in_array( $post_id, $public_post, true) ) {
				return true;
			}
		}

		return false;
    }

}
