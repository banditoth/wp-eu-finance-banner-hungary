<?php
defined( 'ABSPATH' ) || exit;

final class EUSV_Plugin {
    private static $instance = null;
    const POST_TYPE = 'eusv_support';
    const OPTION = 'eusv_settings';
    const NONCE = 'eusv_meta_nonce';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_support' ) );
        add_action( 'admin_menu', array( $this, 'submenu_pages' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_banner' ), 99 );
        add_shortcode( 'eu_support', array( $this, 'single_shortcode' ) );
        add_shortcode( 'eu_supports', array( $this, 'list_shortcode' ) );
    }

    public function register_rest_routes() {
        register_rest_route(
            'eusv/v1',
            '/supports',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'rest_supports' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function rest_supports() {
        return rest_ensure_response(
            array(
                'title'   => 'Pályázatok',
                'content' => $this->list_shortcode(),
            )
        );
    }

    public function register_post_type() {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name'          => 'Pályázatok',
                    'singular_name' => 'Pályázat',
                    'add_new_item'  => 'Új pályázat felvétele',
                    'edit_item'     => 'Pályázat szerkesztése',
                    'menu_name'     => 'Pályázatok',
                    'all_items'     => 'Pályázatok',
                    'add_new'       => 'Új pályázat',
                ),
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => true,
                'supports'        => array( 'page-attributes' ),
                'menu_icon'       => 'dashicons-awards',
                'capability_type' => 'post',
                'map_meta_cap'    => true,
            )
        );
    }

    private function fields() {
        return array(
            'period' => array(
                'label'   => 'Támogatási időszak',
                'type'    => 'select',
                'required' => true,
                'options' => array(
                    '2021-2027' => '2021–2027',
                    '2014-2020' => '2014–2020',
                    'other'     => 'Egyéb / közvetlen EU-program',
                ),
            ),
            'programme' => array(
                'label'    => 'Program vagy alap neve',
                'type'     => 'programme',
                'required' => true,
            ),
            'beneficiary' => array(
                'label'    => 'Kedvezményezett neve',
                'type'     => 'text',
                'required' => true,
            ),
            'project_id' => array(
                'label'    => 'Projektazonosító',
                'type'     => 'text',
                'required' => true,
            ),
            'description' => array(
                'label'    => 'Rövid projektleírás',
                'type'     => 'textarea',
                'required' => true,
            ),
            'aims' => array(
                'label'    => 'A projekt célja',
                'type'     => 'textarea',
                'required' => true,
            ),
            'results' => array(
                'label'    => 'Eredmények / várható eredmények',
                'type'     => 'textarea',
                'required' => true,
            ),
            'amount' => array(
                'label'    => 'Szerződött támogatás összege (Ft)',
                'type'     => 'number',
                'required' => true,
            ),
            'intensity' => array(
                'label'    => 'Támogatási intenzitás (%)',
                'type'     => 'number',
                'required' => false,
            ),
            'start_date' => array(
                'label'    => 'Projekt kezdete',
                'type'     => 'date',
                'required' => false,
            ),
            'end_date' => array(
                'label'    => 'Projekt várható befejezése',
                'type'     => 'date',
                'required' => false,
            ),
            'asset_mode' => array(
                'label'    => 'Hivatalos arculati blokk',
                'type'     => 'select',
                'required' => true,
                'options'  => array(
                    'sztp'   => 'Beépített: Széchenyi Terv Plusz / EU társfinanszírozás',
                    'rrf'    => 'Beépített: RRF / NextGenerationEU',
                    'custom' => 'Egyedi, hivatalos grafika feltöltése',
                ),
            ),
            'official_asset_id' => array(
                'label'    => 'Egyedi hivatalos infoblokk',
                'type'     => 'media',
                'required' => false,
            ),
            'asset_source_url' => array(
                'label'       => 'Egyedi grafika hivatalos forrásának URL-je',
                'type'        => 'url',
                'required'    => false,
                'placeholder' => 'https://www.palyazat.gov.hu/…',
            ),
        );
    }

    private function programme_options() {
        return array(
            'GINOP Plusz'           => 'GINOP Plusz – Gazdaságfejlesztési és Innovációs Operatív Program Plusz',
            'DIMOP Plusz'           => 'DIMOP Plusz – Digitális Megújulás Operatív Program Plusz',
            'EFOP Plusz'            => 'EFOP Plusz – Emberi Erőforrás Fejlesztési Operatív Program Plusz',
            'IKOP Plusz'            => 'IKOP Plusz – Integrált Közlekedésfejlesztési Operatív Program Plusz',
            'KEHOP Plusz'           => 'KEHOP Plusz – Környezeti és Energiahatékonysági Operatív Program Plusz',
            'MAHOP Plusz'           => 'MAHOP Plusz – Magyar Halgazdálkodási Operatív Program Plusz',
            'TOP Plusz'             => 'TOP Plusz – Terület- és Településfejlesztési Operatív Program Plusz',
            'VOP Plusz'             => 'VOP Plusz – Végrehajtás Operatív Program Plusz',
            'BBA Plusz'             => 'BBA Plusz – Belső Biztonsági Alap Plusz',
            'MMIA Plusz'            => 'MMIA Plusz – Menekültügyi, Migrációs és Integrációs Alap Plusz',
            'RRF / NextGenerationEU' => 'RRF / NextGenerationEU – Helyreállítási és Ellenállóképességi Eszköz',
            'Széchenyi 2020'        => 'Széchenyi 2020 (2014–2020)',
        );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'eusv_project_data',
            'Projektadatok és kötelező tájékoztatás',
            array( $this, 'project_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'eusv_compliance',
            'Megfelelőségi ellenőrzés',
            array( $this, 'compliance_box' ),
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public function project_box( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );

        echo '<p>A beépített Széchenyi Terv Plusz és RRF grafikák a Pályázati Portálról származó, változatlan hivatalos infoblokkok. Egyedi módban kizárólag az irányító hatóság által kiadott, változatlan fájlt tölts fel.</p><table class="form-table"><tbody>';

        foreach ( $this->fields() as $key => $field ) {
            $value = get_post_meta( $post->ID, '_eusv_' . $key, true );

            if ( 'asset_mode' === $key && ! $value ) {
                $value = $this->settings()['default_asset_mode'];
            }

            echo '<tr><th><label for="eusv_' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . ( ! empty( $field['required'] ) ? ' <span aria-hidden="true">*</span>' : '' ) . '</label></th><td>';

            if ( 'textarea' === $field['type'] ) {
                echo '<textarea class="large-text" rows="4" id="eusv_' . esc_attr( $key ) . '" name="eusv_' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
            } elseif ( 'programme' === $field['type'] ) {
                $options   = $this->programme_options();
                $is_custom = $value && ! isset( $options[ $value ] );

                echo '<select id="eusv_programme_select" name="eusv_programme_select"><option value="">– Válassz programot vagy alapot –</option>';

                foreach ( $options as $option_value => $option_label ) {
                    echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
                }

                echo '<option value="custom" ' . selected( $is_custom, true, false ) . '>Egyedi program vagy alap neve</option></select><p class="eusv-custom-programme-wrap"' . ( $is_custom ? '' : ' hidden' ) . '><label for="eusv_programme_custom" class="screen-reader-text">Egyedi program vagy alap neve</label><input class="regular-text" type="text" id="eusv_programme_custom" name="eusv_programme_custom" value="' . esc_attr( $is_custom ? $value : '' ) . '" placeholder="Írd be az egyedi program vagy alap nevét"></p>';
            } elseif ( 'select' === $field['type'] ) {
                echo '<select id="eusv_' . esc_attr( $key ) . '" name="eusv_' . esc_attr( $key ) . '">';

                foreach ( $field['options'] as $option_value => $option_label ) {
                    echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
                }

                echo '</select>';
            } elseif ( 'media' === $field['type'] ) {
                $url = $value ? wp_get_attachment_image_url( absint( $value ), 'medium' ) : '';

                echo '<input type="hidden" id="eusv_official_asset_id" name="eusv_official_asset_id" value="' . esc_attr( $value ) . '"><button type="button" class="button eusv-media-button">Hivatalos grafika kiválasztása</button><p class="description">Csak az „Egyedi” arculati blokkhoz szükséges.</p><div class="eusv-preview">' . ( $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '' ) . '</div>';
            } else {
                $step = 'number' === $field['type'] ? ' step="any" min="0"' : '';

                echo '<input class="regular-text" type="' . esc_attr( $field['type'] ) . '"' . esc_attr( $step ) . ' id="eusv_' . esc_attr( $key ) . '" name="eusv_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ) . '">';
            }

            echo '</td></tr>';
        }

        echo '</tbody></table>';

        wp_enqueue_media();
        wp_enqueue_script(
            'eusv-admin',
            EUSV_URL . 'assets/admin.js',
            array( 'jquery' ),
            EUSV_VERSION,
            true
        );
    }

    public function compliance_box( $post ) {
        $missing = $this->missing_fields( $post->ID );

        if ( $missing ) {
            echo '<p class="notice notice-warning inline"><strong>Nem teljes:</strong> ' . esc_html( implode( ', ', $missing ) ) . '</p>';
        } else {
            echo '<p class="notice notice-success inline">A pluginhez előírt adatmezők ki vannak töltve.</p>';
        }

        echo '<p><strong>Fontos:</strong> az egyedi támogatási szerződés és felhívás előírásai elsőbbséget élveznek. A bejegyzés állapota nem jogi megfelelőségi tanúsítvány.</p>';
    }

    public function save_support( $post_id ) {
        if (
            ! isset( $_POST[ self::NONCE ] ) ||
            ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ),
                self::NONCE
            ) ||
            ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
            ! current_user_can( 'edit_post', $post_id )
        ) {
            return;
        }

        foreach ( $this->fields() as $key => $field ) {
            if ( 'programme' === $field['type'] ) {
                $choice = isset( $_POST['eusv_programme_select'] )
                    ? sanitize_text_field( wp_unslash( $_POST['eusv_programme_select'] ) )
                    : '';

                $custom = isset( $_POST['eusv_programme_custom'] )
                    ? sanitize_text_field( wp_unslash( $_POST['eusv_programme_custom'] ) )
                    : '';

                $programmes = $this->programme_options();

                $value = 'custom' === $choice
                    ? $custom
                    : ( isset( $programmes[ $choice ] ) ? $choice : '' );

                update_post_meta( $post_id, '_eusv_' . $key, $value );
                continue;
            }

            $input_key = 'eusv_' . $key;

            if ( ! isset( $_POST[ $input_key ] ) ) {
                continue;
            }

            if ( 'textarea' === $field['type'] ) {
                $value = sanitize_textarea_field(
                    wp_unslash( $_POST[ $input_key ] )
                );
            } elseif ( 'url' === $field['type'] ) {
                $value = esc_url_raw(
                    wp_unslash( $_POST[ $input_key ] )
                );
            } elseif ( 'media' === $field['type'] ) {
                $value = absint(
                    wp_unslash( $_POST[ $input_key ] )
                );
            } elseif ( 'number' === $field['type'] ) {
                $raw = sanitize_text_field(
                    wp_unslash( $_POST[ $input_key ] )
                );

                $value = is_numeric( $raw ) ? (string) $raw : '';
            } else {
                $value = sanitize_text_field(
                    wp_unslash( $_POST[ $input_key ] )
                );
            }

            update_post_meta( $post_id, '_eusv_' . $key, $value );
        }

        $programme = get_post_meta( $post_id, '_eusv_programme', true );
        $amount    = get_post_meta( $post_id, '_eusv_amount', true );

        if ( $programme && '' !== $amount ) {
            $generated_title = sprintf(
                '%s – %s Ft támogatás',
                $programme,
                number_format_i18n( (float) $amount, 0 )
            );

            if ( get_the_title( $post_id ) !== $generated_title ) {
                remove_action(
                    'save_post_' . self::POST_TYPE,
                    array( $this, 'save_support' )
                );

                wp_update_post(
                    array(
                        'ID'        => $post_id,
                        'post_title' => $generated_title,
                        'post_name' => sanitize_title( $generated_title ),
                    )
                );

                add_action(
                    'save_post_' . self::POST_TYPE,
                    array( $this, 'save_support' )
                );
            }
        }
    }

    private function missing_fields( $post_id ) {
        $missing = array();

        foreach ( $this->fields() as $key => $field ) {
            if (
                ! empty( $field['required'] ) &&
                ! get_post_meta( $post_id, '_eusv_' . $key, true )
            ) {
                $missing[] = $field['label'];
            }
        }

        $mode = get_post_meta( $post_id, '_eusv_asset_mode', true );

        if ( ! $mode ) {
            $mode = $this->settings()['default_asset_mode'];
        }

        $asset_id = absint(
            get_post_meta( $post_id, '_eusv_official_asset_id', true )
        );

        if (
            'custom' === $mode &&
            ( ! $asset_id || ! wp_attachment_is_image( $asset_id ) )
        ) {
            $missing[] = 'Érvényes képfájl az egyedi hivatalos infoblokkhoz';
        }

        if (
            'custom' === $mode &&
            ! get_post_meta( $post_id, '_eusv_asset_source_url', true )
        ) {
            $missing[] = 'Az egyedi grafika hivatalos forrásának URL-je';
        }

        return $missing;
    }

    public function submenu_pages() {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            'Pályázatok – megjelenítés',
            'Megjelenítés',
            'manage_options',
            'eusv-settings',
            array( $this, 'settings_html' )
        );
    }

    public function register_settings() {
        register_setting(
            'eusv_settings_group',
            self::OPTION,
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
            )
        );

        add_settings_section(
            'eusv_banner',
            'Sarokban megjelenő támogatási blokk',
            '__return_false',
            'eusv-settings'
        );

        $fields = array(
            'enabled'           => 'Sarokblokk bekapcsolása',
            'default_asset_mode' => 'Új pályázat alapértelmezett arculati blokkja',
            'position'          => 'Pozíció',
            'close_enabled'     => 'Bezárás gomb megjelenítése',
            'auto_hide_seconds' => 'Automatikus elrejtés (másodperc; 0 = kikapcsolva)',
            'hide_on_scroll'    => 'Elrejtés görgetéskor',
            'custom_css'        => 'Egyedi CSS',
        );

        foreach ( $fields as $id => $label ) {
            add_settings_field(
                $id,
                $label,
                array( $this, 'settings_field' ),
                'eusv-settings',
                'eusv_banner',
                array( 'id' => $id )
            );
        }
    }

    private function defaults() {
        return array(
            'enabled'            => 1,
            'default_asset_mode' => 'sztp',
            'position'           => 'bottom-right',
            'close_enabled'      => 0,
            'auto_hide_seconds'  => 0,
            'hide_on_scroll'     => 0,
            'custom_css'         => '',
        );
    }

    private function settings() {
        return wp_parse_args(
            (array) get_option( self::OPTION, array() ),
            $this->defaults()
        );
    }

    public function settings_field( $args ) {
        $id       = $args['id'];
        $settings = $this->settings();
        $value    = $settings[ $id ];

        if ( 'custom_css' === $id ) {
            echo '<textarea class="large-text code" rows="12" name="' . esc_attr( self::OPTION ) . '[custom_css]" placeholder=".eusv-banner { /* saját stílus */ }">' . esc_textarea( $value ) . '</textarea><p class="description">Csak a bővítmény nyilvános elemeinek (például <code>.eusv-banner</code>, <code>.eusv-support</code>) felülírásához használd.</p>';
        } elseif ( 'default_asset_mode' === $id ) {
            echo '<select name="' . esc_attr( self::OPTION ) . '[default_asset_mode]"><option value="sztp" ' . selected( $value, 'sztp', false ) . '>Széchenyi Terv Plusz / EU társfinanszírozás</option><option value="rrf" ' . selected( $value, 'rrf', false ) . '>RRF / NextGenerationEU</option></select><p class="description">Csak új vagy korábban beállítatlan támogatások alapértéke. Projektenként felülírható.</p>';
        } elseif ( 'position' === $id ) {
            echo '<select name="' . esc_attr( self::OPTION ) . '[position]"><option value="bottom-right" ' . selected( $value, 'bottom-right', false ) . '>Jobb alsó</option><option value="bottom-left" ' . selected( $value, 'bottom-left', false ) . '>Bal alsó</option><option value="top-right" ' . selected( $value, 'top-right', false ) . '>Jobb felső</option><option value="top-left" ' . selected( $value, 'top-left', false ) . '>Bal felső</option></select><p class="description">A hely megváltoztatása ütközhet a támogatási szerződés láthatósági előírásaival. Csak a saját kötelezettségeid ellenőrzése után módosítsd.</p>';
        } elseif ( 'auto_hide_seconds' === $id ) {
            echo '<input type="number" min="0" max="86400" name="' . esc_attr( self::OPTION ) . '[auto_hide_seconds]" value="' . esc_attr( $value ) . '"><p class="description">Az automatikus elrejtés nem biztos, hogy megfelel a folyamatos láthatóság követelményének.</p>';
        } else {
            echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION ) . '[' . esc_attr( $id ) . ']" value="1" ' . checked( $value, 1, false ) . '> Engedélyezve</label>';

            if ( in_array( $id, array( 'close_enabled', 'hide_on_scroll' ), true ) ) {
                echo '<p class="description">Ez az opció csökkentheti a támogatási információ láthatóságát. Ellenőrizd a szerződésedet.</p>';
            }
        }
    }

    public function sanitize_settings( $input ) {
        $old     = $this->settings();
        $allowed = array(
            'bottom-right',
            'bottom-left',
            'top-right',
            'top-left',
        );

        return array(
            'enabled' => empty( $input['enabled'] ) ? 0 : 1,
            'default_asset_mode' => isset( $input['default_asset_mode'] ) && in_array(
                $input['default_asset_mode'],
                array( 'sztp', 'rrf' ),
                true
            ) ? $input['default_asset_mode'] : 'sztp',
            'position' => isset( $input['position'] ) && in_array(
                $input['position'],
                $allowed,
                true
            ) ? $input['position'] : $old['position'],
            'close_enabled' => empty( $input['close_enabled'] ) ? 0 : 1,
            'auto_hide_seconds' => isset( $input['auto_hide_seconds'] )
                ? min( 86400, max( 0, absint( $input['auto_hide_seconds'] ) ) )
                : 0,
            'hide_on_scroll' => empty( $input['hide_on_scroll'] ) ? 0 : 1,
            'custom_css' => isset( $input['custom_css'] )
                ? wp_strip_all_tags( wp_unslash( $input['custom_css'] ) )
                : '',
        );
    }

    public function settings_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap"><h1>Pályázatok – megjelenítés</h1><div class="notice notice-warning"><p><strong>Figyelmeztetés:</strong> az elhelyezés, bezárás vagy automatikus elrejtés engedélyezése nem biztos, hogy megfelel a támogatási szerződésednek. A folyamatosan látható, jobb alsó sarokban lévő blokk az alapértelmezés.</p></div><form method="post" action="options.php">';

        settings_fields( 'eusv_settings_group' );
        do_settings_sections( 'eusv-settings' );
        submit_button();

        echo '</form><hr><h2>Shortcode-ok</h2><p><code>[eu_support id="123"]</code> – egy pályázat részletes tájékoztatója.</p><p><code>[eu_supports]</code> – minden teljes adatú pályázat a menüsorrend szerint.</p></div>';
    }

    public function admin_notices() {
        $screen = function_exists( 'get_current_screen' )
            ? get_current_screen()
            : null;

        $is_plugin_screen = $screen && (
            self::POST_TYPE === $screen->post_type ||
            false !== strpos( (string) $screen->id, self::POST_TYPE )
        );

        if ( ! $is_plugin_screen || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        echo '<div class="notice notice-info"><p><strong>Köszönjük, hogy használod a Pályázatok bővítményt!</strong> A bővítmény minden funkciója ingyenes; licenc nem szükséges a használathoz vagy a frissítésekhez. Ha hasznosnak találod, önkéntes támogató szoftverlicenc vásárlásával segítheted a fejlesztést. Ez nem ad további funkciót és nem változtatja meg a bővítmény működését. <a class="button button-secondary" href="https://www.bitfox.hu/termek/wordpress-eu-palyazatok-beepulo-modul-tamogatoi-szoftverlicensz/" target="_blank" rel="noopener noreferrer">Támogató licenc vásárlása</a></p></div>';

        $supports = get_posts(
            array(
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields'      => 'ids',
            )
        );

        $incomplete = array_filter(
            $supports,
            function ( $id ) {
                return ! empty( $this->missing_fields( $id ) );
            }
        );

        if ( $incomplete ) {
            echo '<div class="notice notice-warning"><p><strong>Pályázatok:</strong> ' . esc_html( count( $incomplete ) ) . ' közzétett pályázat hiányos, ezért nem kerül a nyilvános bannerbe vagy az összesítő shortcode-ba.</p></div>';
        }
    }

    public function enqueue_public_assets() {
        if ( ! is_admin() ) {
            wp_enqueue_style(
                'eusv-public',
                EUSV_URL . 'assets/public.css',
                array(),
                EUSV_VERSION
            );

            $css = $this->settings()['custom_css'];

            if ( $css ) {
                wp_add_inline_style( 'eusv-public', $css );
            }

            wp_enqueue_script(
                'eusv-public',
                EUSV_URL . 'assets/public.js',
                array(),
                EUSV_VERSION,
                true
            );

            wp_localize_script(
                'eusv-public',
                'eusvPopup',
                array(
                    'endpoint' => esc_url_raw( rest_url( 'eusv/v1/supports' ) ),
                )
            );
        }
    }

    private function supports() {
        return get_posts(
            array(
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby'     => 'menu_order',
                'order'       => 'ASC',
            )
        );
    }

    private function valid_supports() {
        return array_filter(
            $this->supports(),
            function ( $support ) {
                return ! $this->missing_fields( $support->ID );
            }
        );
    }

    private function asset_data( $post_id ) {
        $mode = get_post_meta( $post_id, '_eusv_asset_mode', true );

        if ( ! $mode ) {
            $mode = $this->settings()['default_asset_mode'];
        }

        $source = 'https://www.palyazat.gov.hu/informacio/kommunikacio-es-lathatosag/szechenyi-terv-plusz';

        if ( 'sztp' === $mode ) {
            return array(
                'url'    => EUSV_URL . 'assets/images/sztp-kedvezmenyezetti-infoblokk.jpg',
                'source' => $source,
                'name'   => 'Széchenyi Terv Plusz kedvezményezetti infoblokk',
            );
        }

        if ( 'rrf' === $mode ) {
            return array(
                'url'    => EUSV_URL . 'assets/images/rrf-kedvezmenyezetti-infoblokk.jpg',
                'source' => $source,
                'name'   => 'RRF / NextGenerationEU kedvezményezetti infoblokk',
            );
        }

        $asset_id = absint(
            get_post_meta( $post_id, '_eusv_official_asset_id', true )
        );

        return array(
            'url'    => wp_attachment_is_image( $asset_id )
                ? wp_get_attachment_image_url( $asset_id, 'full' )
                : false,
            'source' => get_post_meta(
                $post_id,
                '_eusv_asset_source_url',
                true
            ),
            'name'   => 'Egyedi hivatalos kedvezményezetti infoblokk',
        );
    }

    private function support_markup( $support, $compact = false ) {
        $id         = $support->ID;
        $get        = function ( $key ) use ( $id ) {
            return get_post_meta( $id, '_eusv_' . $key, true );
        };
        $asset_data = $this->asset_data( $id );
        $asset      = $asset_data['url'];

        if ( ! $asset ) {
            return '';
        }

        $title = get_the_title( $id );

        $html = '<article class="eusv-support" data-eusv-support="' . esc_attr( $id ) . '"><div class="eusv-official-asset"><img src="' . esc_url( $asset ) . '" alt="' . esc_attr( 'Hivatalos támogatási információs blokk: ' . $title ) . '" loading="lazy"></div>';

        if ( ! $compact ) {
            $html .= '<div class="eusv-details"><h2>' . esc_html( $title ) . '</h2><dl><dt>Kedvezményezett</dt><dd>' . esc_html( $get( 'beneficiary' ) ) . '</dd><dt>Projektazonosító</dt><dd>' . esc_html( $get( 'project_id' ) ) . '</dd><dt>Program</dt><dd>' . esc_html( $get( 'programme' ) ) . '</dd><dt>Projekt leírása</dt><dd>' . nl2br( esc_html( $get( 'description' ) ) ) . '</dd><dt>Cél</dt><dd>' . nl2br( esc_html( $get( 'aims' ) ) ) . '</dd><dt>Eredmények</dt><dd>' . nl2br( esc_html( $get( 'results' ) ) ) . '</dd>';

            if ( '' !== $get( 'amount' ) ) {
                $html .= '<dt>Szerződött támogatás</dt><dd>' . esc_html( number_format_i18n( (float) $get( 'amount' ), 0 ) ) . ' Ft</dd>';
            }

            if ( '' !== $get( 'intensity' ) ) {
                $html .= '<dt>Támogatási intenzitás</dt><dd>' . esc_html( $get( 'intensity' ) ) . '%</dd>';
            }

            if ( $asset_data['source'] ) {
                $html .= '<dt>Arculati elem hivatalos forrása</dt><dd><a href="' . esc_url( $asset_data['source'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $asset_data['name'] ) . '</a></dd>';
            }

            $html .= '</dl></div>';
        }

        return $html . '</article>';
    }

    public function single_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
            ),
            $atts,
            'eu_support'
        );

        $support = get_post( absint( $atts['id'] ) );

        return (
            $support &&
            self::POST_TYPE === $support->post_type &&
            'publish' === $support->post_status &&
            ! $this->missing_fields( $support->ID )
        )
            ? '<section class="eusv-support-details">' . $this->support_markup( $support ) . '</section>'
            : '';
    }

    public function list_shortcode() {
        $html = '';

        foreach ( $this->valid_supports() as $support ) {
            $html .= $this->support_markup( $support );
        }

        return $html
            ? '<section class="eusv-support-list" aria-label="Európai uniós támogatással megvalósuló projektek">' . $html . '</section>'
            : '';
    }

    public function render_banner() {
        $settings = $this->settings();
        $supports = $this->valid_supports();

        if (
            ! $settings['enabled'] ||
            empty( $supports ) ||
            is_admin() ||
            wp_doing_ajax()
        ) {
            return;
        }

        $attrs = array(
            'class="eusv-banner eusv-' . esc_attr( $settings['position'] ) . '"',
            'data-auto-hide="' . esc_attr( $settings['auto_hide_seconds'] ) . '"',
            'data-hide-on-scroll="' . esc_attr( $settings['hide_on_scroll'] ) . '"',
        );

        echo '<aside ' . esc_attr( implode( ' ', $attrs ) ) . ' aria-label="Európai uniós támogatási információ"><button type="button" class="eusv-banner-link" aria-haspopup="dialog" aria-controls="eusv-popup" aria-label="Pályázatok részletes megtekintése"><div class="eusv-banner-items">';

        foreach ( $supports as $support ) {
            echo wp_kses_post( $this->support_markup( $support, true ) );
        }

        echo '</div></button>';

        if ( $settings['close_enabled'] ) {
            echo '<button type="button" class="eusv-close" aria-label="Támogatási információ bezárása">×</button>';
        }

        echo '</aside>';
    }
}