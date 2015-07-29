<?php
    /**
     * Plugin Name: DeMomentSomTres Woocommerce Free Shipping Message
     * Plugin URI:  http://demomentsomtres.com/english/wordpress-plugins/woocommerce-minimum-purchase-message/
     * Version: 1.0.1
     * Author URI: demomentsomtres.com
     * Author: Marc Queralt
     * Description: Shows a message if the user didn't reach the minimum purchase order
     * Requires at least: 3.9
     * Tested up to: 4.2.3
     * License: GPLv3 or later
     * License URI: http://www.opensource.org/licenses/gpl-license.php
     */

    require_once (dirname(__FILE__) . '/lib/class-tgm-plugin-activation.php');
    define('DMS3_WCMPM_TEXT_DOMAIN', 'DeMomentSomTres-WC-minPurchaseMessage');
    $dms3_wcmpm = new DeMomentSomTresWCminimumPurchaseMessage();

    class DeMomentSomTresWCminimumPurchaseMessage {

        const TEXT_DOMAIN = DMS3_WCMPM_TEXT_DOMAIN;
        const MENU_SLUG = 'dmst_wc_minimumPurchase';
        const OPTIONS = 'dmst_wc_minimum_purchase_options';
        // const PAGE = 'dmst_wc_minimumpurchase';
        // const SECTION_1 = 'dmst_wcmpm_1';
        const OPTION_MINIMUM = 'minimumTotal';
        const OPTION_MESSAGE = 'message';

        private $pluginURL;
        private $pluginPath;
        private $langDir;

        /**
         * @since 1.0
         */
        function __construct() {
            $this -> pluginURL = plugin_dir_url(__FILE__);
            $this -> pluginPath = plugin_dir_path(__FILE__);
            $this -> langDir = dirname(plugin_basename(__FILE__)) . '/languages';

            add_action('plugins_loaded', array(
                &$this,
                'plugin_init'
            ));
            add_action('tgmpa_register', array(
                $this,
                'required_plugins'
            ));
            add_action('tf_create_options', array(
                $this,
                'administracio'
            ));

            add_action('woocommerce_before_cart_contents', array(
                &$this,
                'message'
            ));
            add_action('woocommerce_before_checkout_form', array(
                &$this,
                'message'
            ));
        }

        /**
         * @since 1.0
         */
        function plugin_init() {
            load_plugin_textdomain(DMS3_WCMPM_TEXT_DOMAIN, false, $this -> langDir);
        }

        /**
         * @since 1.0
         */
        function required_plugins() {
            $plugins = array(
                array(
                    'name' => 'Titan Framework',
                    'slug' => 'titan-framework',
                    'required' => true
                ),
                array(
                    'name' => 'WooCommerce',
                    'slug' => 'woocommerce',
                    'required' => true
                ),
            );
            $config = array(
                'default_path' => '', // Default absolute path to pre-packaged plugins.
                'menu' => 'tgmpa-install-plugins', // Menu slug.
                'has_notices' => true, // Show admin notices or not.
                'dismissable' => false, // If false, a user cannot dismiss the nag message.
                'dismiss_msg' => __('Some plugins are missing!', self::TEXT_DOMAIN), // If 'dismissable' is false, this message will be output at top of nag.
                'is_automatic' => false, // Automatically activate plugins after installation or not.
                'message' => __('This are the required plugins', self::TEXT_DOMAIN), // Message to output right before the plugins table.
                'strings' => array(
                    'page_title' => __('Install Required Plugins', self::TEXT_DOMAIN),
                    'menu_title' => __('Install Plugins', self::TEXT_DOMAIN),
                    'installing' => __('Installing Plugin: %s', self::TEXT_DOMAIN), // %s = plugin name.
                    'oops' => __('Something went wrong with the plugin API.', self::TEXT_DOMAIN),
                    'notice_can_install_required' => _n_noop('This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_can_install_recommended' => _n_noop('This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_cannot_install' => _n_noop('Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_can_activate_required' => _n_noop('The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_can_activate_recommended' => _n_noop('The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_cannot_activate' => _n_noop('Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_ask_to_update' => _n_noop('The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'notice_cannot_update' => _n_noop('Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', self::TEXT_DOMAIN), // %1$s = plugin name(s).
                    'install_link' => _n_noop('Begin installing plugin', 'Begin installing plugins', self::TEXT_DOMAIN),
                    'activate_link' => _n_noop('Begin activating plugin', 'Begin activating plugins', self::TEXT_DOMAIN),
                    'return' => __('Return to Required Plugins Installer', self::TEXT_DOMAIN),
                    'plugin_activated' => __('Plugin activated successfully.', self::TEXT_DOMAIN),
                    'complete' => __('All plugins installed and activated successfully. %s', self::TEXT_DOMAIN), // %s = dashboard link.
                    'nag_type' => 'error' // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
                )
            );
            tgmpa($plugins, $config);
        }

        function administracio() {
            $oldOptions = get_option(self::OPTIONS);
            $titan = TitanFramework::getInstance(self::OPTIONS);
            $panel = $titan -> createAdminPanel(array(
                'name' => __('DeMomentSomTres Minimum Purchase Message', self::TEXT_DOMAIN),
                'title' => __('DeMomentSomTres Minimum Purchase Message', self::TEXT_DOMAIN),
                'desc' => __('Sets a message if total sale is below a certain amount', self::TEXT_DOMAIN),
                'parent' => 'options-general.php',
            ));
            $confTab = $panel -> createTab(array(
                'name' => __("Configuration", self::TEXT_DOMAIN),
                'id' => "config"
            ));
            $confTab -> createOption(array(
                'name' => __('Minimum order total to get free shipping', self::TEXT_DOMAIN),
                'id' => self::OPTION_MINIMUM,
                'type' => 'number',
                'desc' => __('If order total is below this amount, the message will be shown.', self::TEXT_DOMAIN),
            ));
            $confTab -> createOption(array(
                'name' => __('Message to show if minimum order is not reached', self::TEXT_DOMAIN),
                'id' => self::OPTION_MESSAGE,
                'default' => __('Your purchase is below the minimum purchase of %s€ needed to get free shipping', self::TEXT_DOMAIN),
                'desc' => __('Message should include %s in order to show the minimum amount inside it.', self::TEXT_DOMAIN),
                'type' => 'textarea',
                'is_code' => 'true',
            ));
            $confTab -> createOption(array(
                'type' => 'save',
                'save' => __("Save settings", self::TEXT_DOMAIN),
                'use_reset' => false,
            ));
            if ($oldOptions) :
                $oldTab = $panel -> createTab(array(
                    'name' => __("Earlier versions", self::TEXT_DOMAIN),
                    'id' => "earlier",
                    'desc' => __("Configuration in earlier versions of this plugin", self::TEXT_DOMAIN)
                ));
                $oldTab->createOption(array(
                    'type'=>"heading",
                    'name'=>__('Minimum order total to get free shipping', self::TEXT_DOMAIN),
                ));
                $oldTab->createOption(array(
                    'type'=>"note",
                    'desc'=>$oldOptions[self::OPTION_MINIMUM],
                ));
                $oldTab->createOption(array(
                    'type'=>"heading",
                    'name'=>__('Message to show if minimum order is not reached', self::TEXT_DOMAIN),
                ));
                $oldTab->createOption(array(
                    'type'=>"note",
                    'desc'=>$oldOptions[self::OPTION_MESSAGE],
                ));
                
            endif;
        }

        /**
         * Gets the cart contents total (after calculation).
         *
         * @return string formatted price
         */
        private function get_cart_total() {
            global $woocommerce;

            if (!$woocommerce -> cart -> prices_include_tax) {
                // if prices don't include tax, just return the total
                $cart_contents_total = $woocommerce -> cart -> cart_contents_total;
            }
            else {
                // if prices do include tax, add the tax amount back in
                $cart_contents_total = $woocommerce -> cart -> cart_contents_total + $woocommerce -> cart -> tax_total;
            }

            return $cart_contents_total;
        }

        /**
         * Prints the message
         * @since 1.1
         * @global type $woocommerce
         */
        function message() {
            global $woocommerce;
            
            $titan = TitanFramework::getInstance(self::OPTIONS);
            
            $minimumPurchase = $titan->getOption(self::OPTION_MINIMUM);
            $message = $titan->getOption(self::OPTION_MESSAGE);
            $total = $this -> get_cart_total();
            if ($total < $minimumPurchase) :
                echo "<div class='woocommerce-error alert alert-danger'>";
                printf($message, $minimumPurchase);
                echo "</div>";
            endif;
        }

    }
