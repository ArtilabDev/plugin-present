<?php

if (!defined('ABSPATH')) {
    exit;
}


class MCS_Translations_Page {

    const NONCE_ACTION = 'mcs_translations_save';
    private $option_name = 'mcs_translations_option';
    private $languages = array('uk', 'ru', 'pl', 'en');
    private $fields = array(
        'mcs_text_before_price',
        'mcs_text_after_price',
        'mcs_more_button_text',
        'mcs_order_button_text'
    );

    public function __construct() {
        add_action('admin_menu', array($this, 'register_translation_page'));
        add_action('admin_init', array($this, 'handle_translation_submission'));
    }

    public function register_translation_page() {
        add_menu_page(
            __('Translations', 'mcs'),
            __('Translations', 'mcs'),
            'manage_options',
            'mcs_translations_menu',
            array($this, 'render_translation_page'),
            '',
            7
        );

        add_submenu_page(
            'mcs_main_menu',
            __('Translations', 'mcs'),
            __('Translations', 'mcs'),
            'manage_options',
            'mcs_translations_menu',
            array($this, 'render_translation_page')
        );

        add_action('admin_menu', function() {
            remove_menu_page('mcs_translations_menu');
        }, 999);
    }

    public function register_translation_settings() {
        register_setting('mcs_translations_group', $this->option_name, array($this, 'sanitize_translations'));
    }

    public function render_translation_page() {
        $stored_translations = get_option($this->option_name, "{}");
        $translations = json_decode($stored_translations, true);
        ob_start();
        ?>
        <div class="wrap">
            <h1><?php _e('Translations', 'mcs'); ?></h1>
            <form id="mcs_translations_page" method="post" action="">
                <input type="hidden" name="mcs_translations_nonce" value="<?php echo esc_attr(wp_create_nonce('mcs_translations_save')); ?>">
                <?php
                foreach ($this->languages as $lang) {
                    echo '<h2>' . esc_html(strtoupper($lang)) . '</h2>';
                    foreach ($this->fields as $field) {
                        $value = isset($translations[$field][$lang]) ? $translations[$field][$lang] : '';
                        $pretty_label = ucwords(str_replace('_', ' ', str_replace('mcs_', '', $field)));
                        ?>
                        <div class="input-box">
                            <label for="<?php echo esc_attr($field . '_' . $lang); ?>"><?php _e($pretty_label, 'mcs'); ?></label>
                            <input type="text" id="<?php echo esc_attr($field . '_' . $lang); ?>" name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($lang); ?>]" value="<?php echo esc_attr($value); ?>"/>
                        </div>
                        <?php
                    }
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
        echo ob_get_clean();
    }

    public function handle_translation_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !isset($_POST['mcs_translations_nonce']) ||
            !wp_verify_nonce($_POST['mcs_translations_nonce'], self::NONCE_ACTION)) {
            return;
        }

        $result = array();

        foreach ($this->fields as $field) {
            foreach ($this->languages as $lang) {
                $result[$field][$lang] = $_POST[$field][$lang] ?? '';
                if($result[$field][$lang]) {
                    $result[$field][$lang] = sanitize_text_field($result[$field][$lang]);
                }
            }
        }

        update_option($this->option_name, json_encode($result));
    }
}

new MCS_Translations_Page();
