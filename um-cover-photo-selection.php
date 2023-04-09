<?php
/**
 * Plugin Name:     Ultimate Member - Cover Photo Selection
 * Description:     Extension to Ultimate Member for User Cover Photo Selection from Site Predefined Photos.
 * Version:         2.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Cover_Photo_Selection {

    public $output  = '';
    public $url     = '';
    public $subdir  = '';
    public $title   = '';
    public $form_id = '';
    public $message = '';
    public $data_ratio   = '';
    public $cover_photos = 'cover-photos';
    public $meta_key     = 'my_cover_photo';
    public $photo_extensions = array();
    public $selection_list   = array();

    function __construct( ) {

        add_shortcode( 'cover_photo_selection' ,            array( $this, 'cover_photo_selection_shortcode' ));

        add_action( 'init',                                 array( $this, 'um_init_my_predefined_cover_photo' ), 10 );
        add_filter( 'um_get_default_cover_uri_filter',      array( $this, 'um_get_default_cover_uri_filter_selection' ), 10, 1 );
        add_filter( 'um_user_cover_photo_uri__filter',      array( $this, 'um_get_default_cover_uri_filter_selection' ), 10, 1 );
        add_filter( 'um_myprofile_edit_menu_items',         array( $this, 'um_myprofile_edit_menu_items_photo_selection' ), 10, 1 );
        add_filter( 'um_profile_edit_menu_items',           array( $this, 'um_myprofile_edit_menu_items_photo_selection' ), 10, 1 );
        add_action( 'um_before_upload_db_meta_cover_photo', array( $this, 'um_before_upload_db_meta_cover_photo_selection' ), 10, 1 );

        add_filter( 'um_settings_structure',                array( $this, 'um_settings_structure_cover_photo_selection' ), 10, 1 );
        add_filter( 'um_predefined_fields_hook',            array( $this, 'um_predefined_fields_cover_photo_selection' ), 10, 1 );
        add_filter( 'um_admin_pre_save_field_to_form',      array( $this, 'um_admin_pre_save_field_to_form_photo_selection' ), 10, 1 );

        add_filter( 'um_profile_tabs',                      array( $this, 'profile_tabs' ), 10, 1 );

        add_filter( "um_{$this->meta_key}_predefined_field_options", array( $this, 'my_cover_photo_predefined_field_options' ), 10, 1 );
        add_filter( "um_get_field__{$this->meta_key}",               array( $this, 'my_cover_photo_get_field_options' ), 10, 1 );

        

        $extensions = UM()->options()->get( 'cover_photo_selection_extensions' );
        if ( empty( $extensions )) {
            $extensions = 'jpg,jpeg,png';
        }

        $this->photo_extensions = array_map( 'trim', array_map( 'sanitize_text_field', explode( ",", $extensions )));
        $this->subdir           = UM()->uploader()->get_upload_user_base_dir( $this->cover_photos );
        $this->url              = UM()->uploader()->get_upload_base_url() . $this->cover_photos . DIRECTORY_SEPARATOR;
        $this->data_ratio       = 'data-ratio="' . esc_attr( UM()->options()->get( 'profile_cover_ratio' ) ) . '"';
        $this->title            = __( 'Cover Photo name and the number of users using this Cover Photo', 'ultimate-member' );
        $this->form_id          = sanitize_text_field( UM()->options()->get( 'cover_photo_selection_form_id' ));
    }

    public function get_selection_list() {

        global $wp_filesystem;

        require_once ( ABSPATH . '/wp-admin/includes/file.php' );

        WP_Filesystem();

        if ( $wp_filesystem->exists( $this->subdir ) ) {

            $files = new DirectoryIterator( $this->subdir );

            foreach ( $files as $file ) {

                if ( $file->isDot() || ! $file->isFile() ) continue;
                $path_parts = pathinfo( $file->getPathname());
                $path_parts['filename'] = esc_attr( ucfirst( $path_parts['filename'] ));

                if ( isset( $path_parts['extension'] ) && in_array( $path_parts['extension'], $this->photo_extensions )) {

                    $this->selection_list[esc_attr( $path_parts['basename'] )] = $path_parts['filename'];
                }

                asort( $this->selection_list );
            }

            $fields = UM()->query()->get_attr( 'custom_fields', $this->form_id );

            if ( ! empty( $this->selection_list ) && is_array( $this->selection_list )) {                

                if ( is_array( $fields[$this->meta_key]['options'] )) {

                    $diff_1 = array_diff( $this->selection_list,               $fields[$this->meta_key]['options'] ); 
                    $diff_2 = array_diff( $fields[$this->meta_key]['options'], $this->selection_list );

                    if ( count( $diff_1 ) > 0 || count( $diff_2 ) > 0 ) {

                        $fields[$this->meta_key]['options'] = array_merge( $diff_1, array_diff( $fields[$this->meta_key]['options'], $diff_2 ));
                        sort( $fields[$this->meta_key]['options'] );

                        UM()->query()->update_attr( 'custom_fields', $this->form_id, $fields );
                    }
                }

            } else {

                $this->message = __( 'No Predefined Cover Photos found', 'ultimate-member' );

                $fields[$this->meta_key]['options'] = array( $this->message );
                UM()->query()->update_attr( 'custom_fields', $this->form_id, $fields );                
            }

        } else {

            $new_dir = $wp_filesystem->mkdir( $this->subdir );

            if ( ! $new_dir ) {
                return new \WP_Error( 'FilesysError', __( 'Failed to create the new Cover Photos folder.', 'ultimate-member' ) );
            }

            $this->message = __( 'New Predefined Cover Photos folder created OK', 'ultimate-member' );
        }
    }

    public function um_init_my_predefined_cover_photo() {

        $this->get_selection_list();

        if ( ! empty( $this->selection_list ) && is_array( $this->selection_list )) {

            $html = array();
            foreach ( $this->selection_list as $basename => $file ) {

                $user_query = new WP_User_Query( array( 'meta_key' => esc_attr( $this->meta_key ), 'meta_value' => $file ) );
                $users_count = (int) $user_query->get_total();

                $html[$file] = '
                        
                        <div class="um-field-label">
                            <label title="' . $this->title . '">' . $file . ' - ' . esc_attr( $users_count ) . ' </label>
                            <div class="um-clear"></div>
                        </div>
                        <div class="um-cover has-cover" {data_user_id} ' . $this->data_ratio . '>
                            <div style="display:block" class="um-cover-e" ' . $this->data_ratio . '>
                                <img decoding="async" src="' . esc_url( $this->url . $basename ) . '?' . current_time( 'timestamp' ) . '" alt="' . $file . '" title="' . $file . '">
                            </div>
                        </div>
                        <hr class="wp-header-end">
                    ';
            }

            ksort( $html );
            $this->output = implode( '', $html );
        } 
    }

    public function um_get_default_cover_uri_filter_selection( $uri ) {

        $my_cover_photo = um_user( $this->meta_key );

        if ( ! empty( $my_cover_photo ) 
               && is_array( $this->selection_list )
               && in_array( $my_cover_photo, $this->selection_list )) {

            $cover_photo = array_search( $my_cover_photo, $this->selection_list );

            if ( file_exists( $this->subdir . DIRECTORY_SEPARATOR . $cover_photo )) {
                $uri = esc_url( $this->url . $cover_photo );
            }
        }

        return $uri;
    }

    public function cover_photo_selection_shortcode( $atts, $option ) {

        if ( ! empty( $this->output )) {
            $this->output = str_replace( '{data_user_id}', 'data-user_id="' . esc_attr( um_profile_id()) . '"', $this->output );

            return $this->output;
        }

        if ( ! empty( $this->message )) {

            return __( 'Shortcode Message: ', 'ultimate-member' ) . $this->message;
        }
    }

    public function um_settings_structure_cover_photo_selection( $settings_structure ) {

        $settings_structure['appearance']['sections']['']['fields'][] =
 
                array(
                    'id'          => 'cover_photo_selection_extensions',
                    'type'        => 'text',
                    'label'       => __( 'Cover Photo Selection - Photo File extensions', 'ultimate-member' ),
                    'tooltip'     => __( 'Enter the Cover Photos extensions comma separated. Examples: jpg,jpeg,png,webp', 'ultimate-member' ),
                    'size'        => 'medium',
                );

        $settings_structure['appearance']['sections']['']['fields'][] =

                array(
                    'id'          => 'cover_photo_selection_form_id',
                    'type'        => 'text',
                    'label'       => __( 'Cover Photo Selection - Photo Page Form ID', 'ultimate-member' ),
                    'tooltip'     => __( 'Enter the Cover Photos Form ID Number from the UM Forms Page', 'ultimate-member' ),
                    'size'        => 'small',
                );

        return $settings_structure;
    }

    public function um_predefined_fields_cover_photo_selection( $predefined_fields ) {

        $predefined_fields[$this->meta_key] = array(

                        'title'    => __( 'My Cover Photo', 'ultimate-member' ),
                        'metakey'  => $this->meta_key,
                        'type'     => 'select',
                        'label'    => __( 'My Cover Photo', 'ultimate-member' ),
                        'required' => 0,
                        'public'   => 1,
                        'editable' => 1,
                        'options'  => $this->get_selection_list(),
        );

        return $predefined_fields;
    }

    public function my_cover_photo_predefined_field_options( $array ) {

        $this->get_selection_list();

        return $this->selection_list;
    }

    public function um_myprofile_edit_menu_items_photo_selection( $items ) {

        global $current_user;

        $user_can_change_cover = false;

        if ( $current_user->ID == um_profile_id() ) {
            $user_can_change_cover = true;

        } else {

            $role = get_role( UM()->roles()->get_priority_user_role( $current_user->ID ) );
            $user_can_change_cover = current_user_can( 'edit_users' ) && $role->has_cap( 'edit_users' );
        }

        if ( $user_can_change_cover ) {

            if ( ! empty( $this->form_id )) {

                $subnav_form = '&amp;subnav=profileform-' . esc_attr( $this->form_id );
                $new_items = array();

                foreach( $items as $key => $item ) {

                    $new_items[$key] = $item;
                    if ( $key == 'editprofile' ) {
                        $new_items['photo_selection'] = '<a href="' . esc_url( um_edit_profile_url() ) . $subnav_form .'" class="real_url">' . __( 'Cover Photo', 'ultimate-member' ) . '</a>';
                    }
                }

                return $new_items;

            } else {

                $items['error'] = '<div style="font-size:14px;">* ' . __( 'No Predefined Cover Photos Form ID found', 'ultimate-member' ) . ' *</div>';
            }
        }

        return $items;
    }

    public function um_before_upload_db_meta_cover_photo_selection( $user_id ) {

        if ( ! empty( um_user( $this->meta_key ))) {

            update_user_meta( $user_id, $this->meta_key, '' );
            UM()->user()->remove_cache( $user_id );
            um_fetch_user( $user_id );
        }
    }

    public function um_admin_pre_save_field_to_form_photo_selection( $field_args ) {

        if ( $field_args['metakey'] == $this->meta_key ) {
            
            $this->get_selection_list();
            $field_args['options'] = $this->selection_list;
            sort( $field_args['options'] );
        }

        return $field_args;
    }

    public function my_cover_photo_get_field_options( $array ) {
            
        if ( isset( $array['options'] )) {

            $this->get_selection_list();
            $array['options'] = $this->selection_list;
        }

        return $array;
    }

    public function profile_tabs( $tabs ) {

        remove_filter( 'um_profile_tabs', array( UM()->UM_Profile_Forms(), 'profile_tabs' ), 20 ); 
        return $tabs;
    }
}

UM()->classes['cover_photo_selection'] = new UM_Cover_Photo_Selection();
