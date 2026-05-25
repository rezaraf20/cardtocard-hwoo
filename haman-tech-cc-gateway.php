<?php
/**
 * Plugin Name: HamanTech WooCommerce Card to Card Gateway
 * Description: درگاه کارت به کارت ووکامرس با پشتیبانی از چند حساب بانکی، QRCode، آپلود رسید و رابط فارسی
 * Version: 6.0.0
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
            $this->method_title = 'درگاه کارت به کارت هامن تک';
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
                return array(
                    'result' => 'fail'
                );
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

            <style>
                .ht-wrap {
                    max-width: 760px;
                    margin: 30px auto;
                    direction: rtl;
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 0 15px 0 #80808038;
                }

                .ht-title {
                    font-size: 24px;
                    font-weight: 700;
                    margin-bottom: 20px;
                    text-align: center;
                }

                .ht-price {
                    background: #f5f7fb;
                    padding: 14px;
                    border-radius: 16px;
                    margin-bottom: 24px;
                    text-align: center;
                    font-size: 16px;
                    border: 1px solid #e5e7eb;
                }

                .ht-bank-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 18px;
                }

                .ht-card {
                    position: relative;
                    background: linear-gradient(135deg, #1e293b, #0f172a);
                    color: #fff;
                    border-radius: 24px;
                    padding: 18px;
                    overflow: hidden;
                    box-shadow: 0 12px 35px rgba(15, 23, 42, .18);
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: space-between;
                }

                .ht-card::before {
                    content: '';
                    position: absolute;
                    width: 180px;
                    height: 180px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, .05);
                    top: -70px;
                    left: -70px;
                }

                .ht-card::after {
                    content: '';
                    position: absolute;
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, .04);
                    bottom: -50px;
                    right: -50px;
                }

                .ht-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                    position: relative;
                    z-index: 2;
                    width: 100%;
                }

                .ht-bank-info {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .ht-logo {
                    width: 48px;
                    height: 48px;
                    border-radius: 14px;
                    object-fit: cover;
                    background: #fff;
                }

                .ht-bank-name {
                    font-size: 14px;
                    opacity: .9;
                }

                .ht-holder {
                    font-size: 16px;
                    font-weight: 700;
                    margin-top: 4px;
                }

                .ht-qr {
                    width: 85px;
                    height: 85px;
                    border-radius: 12px;
                    background: #fff;
                    padding: 4px;
                    object-fit: cover;
                }

                .ht-number-box {
                    background: rgba(255, 255, 255, .08);
                    border: 1px solid rgba(255, 255, 255, .08);
                    border-radius: 16px;
                    padding: 8px 10px;
                    margin-top: 10px;
                    position: relative;
                    z-index: 2;
                    max-width: 309px;
                    width: 100%;
                }

                .ht-label {
                    font-size: 12px;
                    opacity: .75;
                    margin-bottom: 0px;
                }

                .ht-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                }

                .ht-number {
                    font-size: 15px;
                    font-weight: 600;
                    direction: ltr;
                    text-align: left;
                    letter-spacing: 1px;
                }

                .ht-copy {
                    width: 36px;
                    height: 36px;
                    border: none;
                    border-radius: 12px;
                    background: rgba(255, 255, 255, .12);
                    color: #fff;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: .2s;
                    flex-shrink: 0;
                }

                .ht-copy:hover {
                    background: rgba(255, 255, 255, .2);
                }

                .ht-copy svg {
                    min-width: 18px;
                    width: 18px !important;
                    height: 18px;
                }

                .ht-form {
                    margin-top: 30px;
                    background: #003e63;
                    padding: 24px;
                    border-radius: 24px;
                    color: #fff;
                }

                .ht-form-title {
                    font-size: 22px;
                    font-weight: 700;
                    margin-bottom: 20px;
                }

                .ht-upload-area {
                    position: relative;
                    border: 2px dashed rgba(255, 255, 255, .25);
                    border-radius: 22px;
                    padding: 30px;
                    text-align: center;
                    transition: .2s;
                    cursor: pointer;
                }

                .ht-upload-area:hover {
                    border-color: rgba(255, 255, 255, .5);
                }

                .ht-upload-area input {
                    position: absolute;
                    inset: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0;
                    cursor: pointer;
                }

                .ht-upload-placeholder svg {
                    width: 52px;
                    height: 52px;
                    margin-bottom: 14px;
                }

                .ht-upload-placeholder span {
                    display: block;
                    font-size: 15px;
                }

                #htPreviewWrapper {
                    margin-top: 20px;
                }

                #htPreviewImage {
                    width: 100%;
                    max-width: 260px;
                    border-radius: 18px;
                    display: block;
                }

                .ht-submit-btn {
                    margin-top: 20px;
                    width: 100%;
                    height: 54px;
                    border: none;
                    border-radius: 18px;
                    background: #fff;
                    color: #003e63;
                    font-size: 16px;
                    font-weight: 700;
                    cursor: pointer;
                }

                .ht-submit-btn:disabled {
                    opacity: .6;
                    cursor: not-allowed;
                }

                #htUploadMessage {
                    margin-top: 18px;
                    font-size: 14px;
                }

                .ht-success {
                    background: #14532d;
                    padding: 14px;
                    border-radius: 14px;
                }

                .ht-error {
                    background: #7f1d1d;
                    padding: 14px;
                    border-radius: 14px;
                }
            </style>

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
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2 2h9a2 2 0 0 1 2 2v1"></path>
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M12 16V4"></path>
                                    <path d="M7 9L12 4L17 9"></path>
                                    <path d="M20 16.58A5 5 0 0018 7h-1.26A8 8 0 104 16.25"></path>
                                </svg>
                                <span>
                                    تصویر رسید را انتخاب کنید
                                </span>
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
            <script>
                function htCopy(id) {
                    let text =
                        document.getElementById(id).innerText;
                    navigator.clipboard.writeText(text);
                }
                document.addEventListener('DOMContentLoaded', function () {
                    const form =
                        document.getElementById('htReceiptForm');
                    if (!form) return;
                    const input =
                        document.getElementById('htReceiptInput');
                    const preview =
                        document.getElementById('htPreviewImage');
                    const previewWrapper =
                        document.getElementById('htPreviewWrapper');
                    const message =
                        document.getElementById('htUploadMessage');
                    const submitBtn =
                        form.querySelector('button');
                    input.addEventListener('change', function () {
                        const file = this.files[0];
                        if (!file) return;
                        if (file.type.includes('image')) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                preview.src = e.target.result;
                                previewWrapper.style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        submitBtn.disabled = true;
                        submitBtn.innerText = 'در حال ارسال...';
                        message.innerHTML = '';
                        const formData =
                            new FormData(form);
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        })
                            .then(res => res.json())
                            .then(res => {
                                submitBtn.disabled = false;
                                submitBtn.innerText =
                                    'ثبت رسید پرداخت';
                                if (res.success) {
                                    message.innerHTML =
                                        '<div class="ht-success">' +
                                        'با تشکر از پرداخت شما،<br>' +
                                        'در اسرع وقت پس از بررسی، پردازش سفارش شما انجام خواهد شد.' +
                                        '</div>';
                                    form.reset();
                                } else {
                                    message.innerHTML =
                                        '<div class="ht-error">' +
                                        res.data +
                                        '</div>';
                                }
                            })
                            .catch(() => {
                                submitBtn.disabled = false;
                                submitBtn.innerText =
                                    'ثبت رسید پرداخت';
                                message.innerHTML =
                                    '<div class="ht-error">' +
                                    'خطا در ارسال فایل' +
                                    '</div>';
                            });
                    });
                });
            </script>
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
add_action(
    'wp_ajax_ht_upload_receipt',
    'ht_upload_receipt'
);
add_action(
    'wp_ajax_nopriv_ht_upload_receipt',
    'ht_upload_receipt'
);
function ht_upload_receipt()
{
    check_ajax_referer(
        'ht_upload_receipt_nonce',
        'security'
    );
    if (
        empty($_FILES['receipt'])
    ) {
        wp_send_json_error(
            'فایلی انتخاب نشده است'
        );
    }
    $order_id = intval($_POST['order_id']);
    if (!$order_id) {
        wp_send_json_error(
            'شماره سفارش نامعتبر است'
        );
    }
    require_once(
        ABSPATH .
        'wp-admin/includes/file.php'
    );
    $uploaded = wp_handle_upload(
        $_FILES['receipt'],
        array(
            'test_form' => false
        )
    );
    if (
        isset($uploaded['error'])
    ) {
        wp_send_json_error(
            $uploaded['error']
        );
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
        <a href="<?php echo esc_url($receipt); ?>" target="_blank" class="button button-primary" style="width:100%">
            مشاهده تصویر
        </a>
    </div>
    <?php
}
