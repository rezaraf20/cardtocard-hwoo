<?php
/**
 * Plugin Name: HamanTech WooCommerce Card to Card Gateway
 * Description: درگاه کارت به کارت ووکامرس با پشتیبانی از چند حساب بانکی، QRCode، آپلود رسید و رابط فارسی
 * Version: 7.0.0
 * Author: HamanTech Reza Rafiei
 * Text Domain: haman-tech-cc-gateway
 */

if (!defined('ABSPATH'))
    exit;

add_action('plugins_loaded', 'ht_cc_gateway_bootstrap', 20);
function ht_cc_gateway_bootstrap()
{
    if (!class_exists('WooCommerce'))
        return;
    if (!class_exists('WC_Payment_Gateway'))
        return;
    class WC_Gateway_Haman_Card_To_Card extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'ht_card_to_card';
            $this->method_title = 'درگاه کارت به کارت هامان تک';
            $this->method_description = 'پرداخت کارت به کارت با چند حساب بانکی';
            $this->has_fields = false;
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array($this, 'process_admin_options')
            );
            add_action(
                'woocommerce_thankyou_' . $this->id,
                array($this, 'thankyou_page')
            );
            add_action(
                'wp_enqueue_scripts',
                array($this, 'ht_enqueue_assets')
            );
        }
        public function ht_enqueue_assets()
        {
            if (!is_order_received_page()) {
                return;
            }
            wp_enqueue_style(
                'ht-card-to-card-style',
                plugin_dir_url(__FILE__) . 'assets/css/style.css',
                array(),
                '7.0.0'
            );
            wp_enqueue_script(
                'ht-card-to-card-script',
                plugin_dir_url(__FILE__) . 'assets/js/script.js',
                array(),
                '7.0.0',
                true
            );
            wp_localize_script(
                'ht-card-to-card-script',
                'ht_ajax_obj',
                array(
                    'ajax_url' => admin_url('admin-ajax.php')
                )
            );
        }
        public function init_form_fields()
        {
            $this->form_fields = array(

                'enabled' => array(
                    'title' => 'فعال سازی',
                    'type' => 'checkbox',
                    'label' => 'فعال سازی درگاه',
                    'default' => 'yes'
                ),

                'title' => array(
                    'title' => 'عنوان درگاه',
                    'type' => 'text',
                    'default' => 'کارت به کارت'
                ),

                'description' => array(
                    'title' => 'توضیحات',
                    'type' => 'textarea',
                    'default' => 'پس از پرداخت رسید خود را ارسال نمایید.'
                ),

                'holder_1' => array(
                    'title' => 'نام دارنده کارت 1',
                    'type' => 'text'
                ),

                'card_1' => array(
                    'title' => 'شماره کارت 1',
                    'type' => 'text'
                ),

                'iban_1' => array(
                    'title' => 'شماره شبا 1',
                    'type' => 'text'
                ),

                'logo_1' => array(
                    'title' => 'لوگو بانک 1',
                    'type' => 'text'
                ),

                'qr_1' => array(
                    'title' => 'QR Code 1',
                    'type' => 'text'
                ),

                'holder_2' => array(
                    'title' => 'نام دارنده کارت 2',
                    'type' => 'text'
                ),

                'card_2' => array(
                    'title' => 'شماره کارت 2',
                    'type' => 'text'
                ),

                'iban_2' => array(
                    'title' => 'شماره شبا 2',

                    'type' => 'text'
                ),

                'logo_2' => array(
                    'title' => 'لوگو بانک 2',
                    'type' => 'text'
                ),

                'qr_2' => array(
                    'title' => 'QR Code 2',
                    'type' => 'text'
                ),

                'holder_3' => array(
                    'title' => 'نام دارنده کارت 3',
                    'type' => 'text'
                ),

                'card_3' => array(
                    'title' => 'شماره کارت 3',
                    'type' => 'text'
                ),

                'iban_3' => array(
                    'title' => 'شماره شبا 3',
                    'type' => 'text'
                ),

                'logo_3' => array(
                    'title' => 'لوگو بانک 3',
                    'type' => 'text'
                ),

                'qr_3' => array(
                    'title' => 'QR Code 3',
                    'type' => 'text'
                ),

            );
        }
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            if (!$order) {
                return array('result' => 'fail');
            }
            $order->update_status(
                'on-hold',
                'در انتظار پرداخت کارت به کارت'
            );
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
        public function thankyou_page($order_id)
        {
            $order = wc_get_order($order_id);
            if (!$order)
                return;
            $amount = number_format($order->get_total());
            ?>
            <div class="ht-wrap">
                <div class="ht-title">
                    پرداخت کارت به کارت
                </div>
                <div class="ht-price">
                    مبلغ قابل پرداخت:
                    <strong>
                        <?php echo esc_html($amount); ?>
                    </strong>
                    تومان
                </div>
                <div class="ht-bank-grid">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php if ($this->get_option("card_$i")): ?>
                            <div class="ht-card">
                                <div class="ht-top">
                                    <div class="ht-bank-info">
                                        <?php if ($this->get_option("logo_$i")): ?>
                                            <img class="ht-logo" src="<?php echo esc_url($this->get_option("logo_$i")); ?>">
                                        <?php endif; ?>
                                        <div>
                                            <div class="ht-bank-name">
                                                حساب <?php echo $i; ?>
                                            </div>
                                            <div class="ht-holder">
                                                <?php echo esc_html($this->get_option("holder_$i")); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($this->get_option("qr_$i")): ?>
                                        <img class="ht-qr" src="<?php echo esc_url($this->get_option("qr_$i")); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="ht-number-box">
                                    <div class="ht-label">
                                        شماره کارت
                                    </div>
                                    <div class="ht-row">
                                        <div class="ht-number" id="card<?php echo $i; ?>">
                                            <?php echo esc_html($this->get_option("card_$i")); ?>
                                        </div>
                                        <button type="button" class="ht-copy" onclick="htCopy('card<?php echo $i; ?>')">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <?php if ($this->get_option("iban_$i")): ?>
                                    <div class="ht-number-box">
                                        <div class="ht-label">
                                            شماره شبا
                                        </div>
                                        <div class="ht-row">
                                            <div class="ht-number" id="iban<?php echo $i; ?>">
                                                <?php echo esc_html($this->get_option("iban_$i")); ?>
                                            </div>
                                            <button type="button" class="ht-copy" onclick="htCopy('iban<?php echo $i; ?>')">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                            </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <div class="ht-form">
                    <div class="ht-form-title">
                        ارسال رسید پرداخت
                    </div>
                    <form id="htReceiptForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="ht_upload_receipt">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                        <input type="hidden" name="security" value="<?php echo wp_create_nonce('ht_upload_receipt_nonce'); ?>">
                        <div class="ht-upload-area">
                            <input type="file" name="receipt" id="htReceiptInput" accept="image/*,.pdf" required>
                            <div class="ht-upload-placeholder">
                                تصویر رسید را انتخاب کنید
                            </div>
                        </div>
                        <div id="htPreviewWrapper" style="display:none">
                            <img id="htPreviewImage">
                        </div>
                        <button type="submit" class="ht-submit-btn">
                            ثبت رسید پرداخت
                        </button>
                        <div id="htUploadMessage"></div>
                    </form>
                </div>
            </div>
            <?php
        }
    }

    add_filter(
        'woocommerce_payment_gateways',
        function ($methods) {

            $methods[] =
                'WC_Gateway_Haman_Card_To_Card';

            return $methods;
        }
    );
}

add_action('wp_ajax_ht_upload_receipt', 'ht_upload_receipt');
add_action('wp_ajax_nopriv_ht_upload_receipt', 'ht_upload_receipt');
function ht_upload_receipt()
{
    check_ajax_referer(
        'ht_upload_receipt_nonce',
        'security'
    );
    if (empty($_FILES['receipt'])) {
        wp_send_json_error('فایلی انتخاب نشده است');
    }
    $order_id = intval($_POST['order_id']);
    require_once(
        ABSPATH . 'wp-admin/includes/file.php'
    );
    $uploaded = wp_handle_upload(
        $_FILES['receipt'],
        array(
            'test_form' => false
        )
    );
    if (isset($uploaded['error'])) {
        wp_send_json_error($uploaded['error']);
    }
    $file_url = $uploaded['url'];
    update_post_meta(
        $order_id,
        '_ht_payment_receipt',
        esc_url_raw($file_url)
    );
    $order = wc_get_order($order_id);

    if ($order) {
        $order->add_order_note(
            'رسید پرداخت توسط مشتری ارسال شد.'
        );
        $order->update_status('on-hold');
    }

    wp_send_json_success();
}
add_action(
    'add_meta_boxes',
    'ht_register_receipt_metabox'
);
function ht_register_receipt_metabox()
{

    add_meta_box(
        'ht-payment-receipt',
        'رسید پرداخت مشتری',
        'ht_render_receipt_metabox',
        'shop_order',
        'side',
        'high'
    );
}
function ht_render_receipt_metabox($post)
{
    $receipt = get_post_meta(
        $post->ID,
        '_ht_payment_receipt',
        true
    );

    if (!$receipt) {
        echo '<p>رسیدی ثبت نشده است.</p>';
        return;
    }
    ?>
    <div style="text-align:center">

        <a href="<?php echo esc_url($receipt); ?>" target="_blank">

            <img src="<?php echo esc_url($receipt); ?>" style="
                    width:100%;
                    border-radius:12px;
                    border:1px solid #ddd;
                    margin-bottom:10px;
                ">
        </a>
    </div>
    <?php
}
