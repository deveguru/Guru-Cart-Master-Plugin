<?php
/**
 * Plugin Name: Guru Cart Master
 * Plugin URI:  https://github.com/deveguru
 * Description: یک سیستم جامع برای ایجاد، گروه‌بندی و نمایش کارت‌های محتوایی پیشرفته با قابلیت استایل‌دهی کامل و نمایش به صورت گرید یا کاروسل.
 * Version:     4.1.0
 * Author:      Alireza Fatemi
 * Author URI:  https://alirezafatemi.ir
 * Text Domain: guru-cart-master
 * License:     GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class GuruCartMaster {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_gcm_save_card', array($this, 'save_card'));
        add_action('wp_ajax_gcm_delete_card', array($this, 'delete_card'));
        add_action('wp_ajax_gcm_get_card', array($this, 'get_card'));
        add_action('wp_ajax_gcm_save_group', array($this, 'save_group'));
        add_action('wp_ajax_gcm_delete_group', array($this, 'delete_group'));
        add_action('wp_ajax_gcm_get_group', array($this, 'get_group'));
        add_shortcode('guru_card', array($this, 'render_single_card_shortcode'));
        add_shortcode('guru_card_group', array($this, 'render_card_group_shortcode'));
    }

    public function init() {
        $this->create_database_tables();
    }

    public function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_cards = $wpdb->prefix . 'gcm_cards';
        $sql_cards = "CREATE TABLE $table_cards (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            image_url varchar(500) NOT NULL,
            link_url varchar(500) NOT NULL,
            badge varchar(100) DEFAULT '',
            social_links text DEFAULT '',
            card_style varchar(50) DEFAULT 'classic',
            height varchar(20) DEFAULT 'auto',
            image_height varchar(20) DEFAULT '200px',
            primary_color varchar(20) DEFAULT '#007cba',
            secondary_color varchar(20) DEFAULT '#ff6b6b',
            text_color varchar(20) DEFAULT '#333333',
            bg_color_1 varchar(20) DEFAULT '#ffffff',
            bg_color_2 varchar(20) DEFAULT '',
            padding varchar(50) DEFAULT '20px',
            border_radius varchar(50) DEFAULT '10px',
            box_shadow varchar(100) DEFAULT '0 4px 6px rgba(0,0,0,0.1)',
            text_align varchar(20) DEFAULT 'right',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_cards);

        $table_groups = $wpdb->prefix . 'gcm_groups';
        $sql_groups = "CREATE TABLE $table_groups (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            uniform_height varchar(20) DEFAULT '',
            uniform_image_height varchar(20) DEFAULT '',
            primary_color varchar(20) DEFAULT '',
            secondary_color varchar(20) DEFAULT '',
            text_color varchar(20) DEFAULT '',
            bg_color_1 varchar(20) DEFAULT '',
            bg_color_2 varchar(20) DEFAULT '',
            padding varchar(50) DEFAULT '',
            border_radius varchar(50) DEFAULT '',
            box_shadow varchar(100) DEFAULT '',
            text_align varchar(20) DEFAULT '',
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql_groups);

        $table_relationships = $wpdb->prefix . 'gcm_relationships';
        $sql_relationships = "CREATE TABLE $table_relationships (
            group_id mediumint(9) NOT NULL,
            card_id mediumint(9) NOT NULL,
            PRIMARY KEY (group_id, card_id)
        ) $charset_collate;";
        dbDelta($sql_relationships);
    }

    public function admin_menu() {
        add_menu_page('کارت های گورو', 'کارت های گورو', 'manage_options', 'guru-cart-master',
            array($this, 'admin_page'), 'dashicons-pressthis', 30);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook === 'toplevel_page_guru-cart-master') {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
        wp_add_inline_style('font-awesome', $this->get_base_styles());
    }

    private function get_base_styles() {
        return '.gcm-card-container{font-family:Vazir,Tahoma,sans-serif;direction:rtl;margin:10px 0;width:100%;box-sizing:border-box}.gcm-card-container img{max-width:100%;height:auto;display:block}.gcm-card-container h3{margin:10px 0;font-size:1.2em;font-weight:700}.gcm-card-container a{text-decoration:none;transition:color .3s}.gcm-card-container p{line-height:1.6;margin:10px 0}.gcm-social-icons{display:flex;gap:10px;margin-top:15px}.gcm-social-icons a{display:inline-flex;align-items:center;justify-content:center;width:35px;height:35px;border-radius:50%;background:#f0f0f0;transition:all .3s ease}.gcm-social-icons a:hover{transform:translateY(-2px)}.gcm-social-icons i{font-size:16px}.gcm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;align-items:stretch}.gcm-grid .gcm-card-container{margin:0}.gcm-card{display:flex;flex-direction:column;height:100%}.gcm-card .card-content{flex-grow:1}.gcm-carousel{position:relative;padding:0 40px}.gcm-carousel .swiper-slide{height:auto}.gcm-carousel .swiper-button-next,.gcm-carousel .swiper-button-prev{color:#333}.gcm-card.style-classic .card-image{position:relative;overflow:hidden;flex-shrink:0}.gcm-card.style-classic .card-image img{width:100%;height:100%;object-fit:cover}.gcm-card.style-classic .card-badge{position:absolute;top:10px;right:10px;color:#fff;padding:5px 10px;border-radius:15px;font-size:12px;font-weight:700}.gcm-card.style-modern .card-image{position:relative;overflow:hidden;border-radius:inherit;flex-shrink:0}.gcm-card.style-modern .card-image img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}.gcm-card.style-modern:hover .card-image img{transform:scale(1.1)}.gcm-card.style-modern .card-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.5),transparent)}.gcm-card.style-profile{text-align:center}.gcm-card.style-profile .profile-image{width:120px;height:120px;margin:0 auto 20px;border-radius:50%;overflow:hidden;border:4px solid #f0f0f0;flex-shrink:0}.gcm-card.style-profile .gcm-social-icons{justify-content:center}';
    }

    public function admin_page() {
    global $wpdb;
    $cards_table = $wpdb->prefix . 'gcm_cards';
    $groups_table = $wpdb->prefix . 'gcm_groups';
    $all_cards = $wpdb->get_results("SELECT id, title FROM $cards_table ORDER BY title ASC");
    $all_groups = $wpdb->get_results("SELECT id, name FROM $groups_table ORDER BY name ASC");
    ?>
    <div class="wrap gcm-admin-wrap">
        <h1>مدیریت کارت های گورو (Guru Cart Master)</h1>
        <h2 class="nav-tab-wrapper">
            <a href="#cards" class="nav-tab nav-tab-active">مدیریت کارت‌ها</a>
            <a href="#groups" class="nav-tab">مدیریت گروه‌ها</a>
            <a href="#shortcodes" class="nav-tab">راهنمای شورتکدها</a>
        </h2>
        <div id="cards" class="gcm-tab-content active">
            <div class="gcm-container">
                <div class="gcm-form-section">
                    <h3>افزودن / ویرایش کارت</h3>
                    <form id="gcm-card-form">
                        <input type="hidden" id="gcm-card-id" value="">
                        <h4>محتوای کارت</h4>
                        <p><label>عنوان: <input type="text" id="gcm-title" class="widefat" required></label></p>
                        <p><label>توضیحات: <textarea id="gcm-description" class="widefat" rows="4"></textarea></label></p>
                        <p><label>URL تصویر: <input type="text" id="gcm-image-url" class="widefat" required><button type="button" class="button gcm-upload-btn">انتخاب تصویر</button></label></p>
                        <p><label>لینک کارت: <input type="url" id="gcm-link-url" class="widefat" required></label></p>
                        <p><label>برچسب (اختیاری): <input type="text" id="gcm-badge" class="widefat"></label></p>
                        <p><label>استایل پایه: 
                            <select id="gcm-card-style">
                                <option value="classic">کلاسیک</option>
                                <option value="modern">مدرن</option>
                                <option value="profile">پروفایل</option>
                            </select>
                        </label></p>
                        <h4>لینک‌های اجتماعی</h4>
                        <p><label>واتساپ: <input type="url" id="gcm-whatsapp" class="widefat"></label></p>
                        <p><label>اینستاگرام: <input type="url" id="gcm-instagram" class="widefat"></label></p>
                        <p><label>تلگرام: <input type="url" id="gcm-telegram" class="widefat"></label></p>
                        <h4>استایل و چیدمان</h4>
                        <p><label>ارتفاع کارت: <input type="text" id="gcm-height" value="auto" placeholder="auto یا 450px"></label></p>
                        <p><label>ارتفاع تصویر: <input type="text" id="gcm-image-height" value="200px" placeholder="200px یا auto"></label></p>
                        <p>رنگ پس‌زمینه: <label><input type="color" id="gcm-bg-color-1" value="#ffffff"> (برای گرادیان: <input type="color" id="gcm-bg-color-2">)</label></p>
                        <p>رنگ‌ها: <label>اصلی <input type="color" id="gcm-primary-color" value="#007cba"></label> <label>ثانویه <input type="color" id="gcm-secondary-color" value="#ff6b6b"></label> <label>متن <input type="color" id="gcm-text-color" value="#333333"></label></p>
                        <p><label>فاصله داخلی (Padding): <input type="text" id="gcm-padding" value="20px" placeholder="20px"></label></p>
                        <p><label>گردی گوشه‌ها (Border Radius): <input type="text" id="gcm-border-radius" value="10px" placeholder="10px"></label></p>
                        <p><label>سایه (Box Shadow): <input type="text" id="gcm-box-shadow" class="widefat" value="0 4px 6px rgba(0,0,0,0.1)" placeholder="0 5px 15px rgba(0,0,0,0.2)"></label></p>
                        <p><label>چینش متن: <select id="gcm-text-align"><option value="right">راست</option><option value="center">وسط</option><option value="left">چپ</option></select></label></p>
                        <p class="submit">
                            <button type="submit" class="button-primary">ذخیره کارت</button>
                            <button type="button" class="button" id="gcm-reset-card-form">فرم جدید</button>
                        </p>
                    </form>
                </div>
                <div class="gcm-list-section">
                    <h3>لیست کارت‌ها</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>عنوان</th><th>شورتکد</th><th>عملیات</th></tr></thead>
                        <tbody id="gcm-cards-tbody">
                        <?php foreach($all_cards as $card): ?>
                            <tr data-card-id="<?php echo esc_attr($card->id); ?>">
                                <td><?php echo esc_html($card->title); ?></td>
                                <td><code>[guru_card id="<?php echo esc_attr($card->id); ?>"]</code></td>
                                <td>
                                    <button class="button gcm-edit-card-btn">ویرایش</button>
                                    <button class="button button-danger gcm-delete-card-btn">حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="groups" class="gcm-tab-content">
            <div class="gcm-container">
                <div class="gcm-form-section">
                    <h3>افزودن / ویرایش گروه</h3>
                    <form id="gcm-group-form">
                        <input type="hidden" id="gcm-group-id" value="">
                        <h4>تنظیمات اصلی گروه</h4>
                        <p><label>نام گروه: <input type="text" id="gcm-group-name" class="widefat" required></label></p>
                        <h4>کارت‌های عضو گروه</h4>
                        <div id="gcm-group-cards-checklist" class="gcm-checklist">
                            <?php foreach($all_cards as $card): ?>
                                <label><input type="checkbox" value="<?php echo esc_attr($card->id); ?>"> <?php echo esc_html($card->title); ?></label>
                            <?php endforeach; ?>
                        </div>
                        <h4>استایل یکپارچه گروه (اختیاری - جایگزین استایل کارت‌ها می‌شود)</h4>
                        <p><label>یکسان‌سازی ارتفاع کارت‌ها: <input type="text" id="gcm-group-height" placeholder="auto یا 450px"></label></p>
                        <p><label>یکسان‌سازی ارتفاع تصویرها: <input type="text" id="gcm-group-image-height" placeholder="200px یا auto"></label></p>
                        <p>رنگ پس‌زمینه: <label><input type="color" id="gcm-group-bg-color-1"> (گرادیان: <input type="color" id="gcm-group-bg-color-2">)</label></p>
                        <p>رنگ‌ها: <label>اصلی <input type="color" id="gcm-group-primary-color"></label> <label>ثانویه <input type="color" id="gcm-group-secondary-color"></label> <label>متن <input type="color" id="gcm-group-text-color"></label></p>
                        <p><label>فاصله داخلی (Padding): <input type="text" id="gcm-group-padding" placeholder="20px"></label></p>
                        <p><label>گردی گوشه‌ها (Border Radius): <input type="text" id="gcm-group-border-radius" placeholder="10px"></label></p>
                        <p><label>سایه (Box Shadow): <input type="text" id="gcm-group-box-shadow" class="widefat" placeholder="0 5px 15px rgba(0,0,0,0.2)"></label></p>
                        <p><label>چینش متن: <select id="gcm-group-text-align"><option value="">پیش‌فرض کارت</option><option value="right">راست</option><option value="center">وسط</option><option value="left">چپ</option></select></label></p>
                         <p class="submit">
                            <button type="submit" class="button-primary">ذخیره گروه</button>
                            <button type="button" class="button" id="gcm-reset-group-form">فرم جدید</button>
                        </p>
                    </form>
                </div>
                <div class="gcm-list-section">
                    <h3>لیست گروه‌ها</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>نام گروه</th><th>شورتکدها</th><th>عملیات</th></tr></thead>
                        <tbody id="gcm-groups-tbody">
                            <?php foreach($all_groups as $group): ?>
                            <tr data-group-id="<?php echo esc_attr($group->id); ?>">
                                <td><?php echo esc_html($group->name); ?></td>
                                <td>
                                    <code>[guru_card_group id="<?php echo esc_attr($group->id); ?>"]</code><br>
                                    <code>[guru_card_group id="<?php echo esc_attr($group->id); ?>" display="carousel"]</code>
                                </td>
                                <td>
                                    <button class="button gcm-edit-group-btn">ویرایش</button>
                                    <button class="button button-danger gcm-delete-group-btn">حذف</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="shortcodes" class="gcm-tab-content">
            <h3>راهنمای استفاده از شورتکدها</h3>
            <p>برای نمایش یک کارت به تنهایی، از شورتکد زیر در هر نوشته یا برگه‌ای استفاده کنید و <code>id</code> آن را با شناسه کارت مورد نظر جایگزین کنید:</p>
            <code>[guru_card id="1"]</code>
            <hr>
            <p>برای نمایش یک گروه از کارت‌ها، از شورتکد گروه استفاده کنید. این شورتکد دو حالت نمایش دارد:</p>
            <h4>1. نمایش گرید (Grid)</h4>
            <p>این حالت پیش‌فرض است و کارت‌ها را به صورت یک شبکه واکنش‌گرا نمایش می‌دهد:</p>
            <code>[guru_card_group id="1"]</code>
            <h4>2. نمایش کاروسل (Carousel)</h4>
            <p>برای نمایش کارت‌ها به صورت اسلایدر، پارامتر <code>display="carousel"</code> را به شورتکد اضافه کنید:</p>
            <code>[guru_card_group id="1" display="carousel"]</code>
            <p><strong>توجه:</strong> برای یافتن <code>id</code> هر کارت یا گروه، به لیست آن‌ها در تب‌های مربوطه مراجعه کنید.</p>
        </div>
    </div>
    <style>
        .gcm-admin-wrap .gcm-container { display: grid; grid-template-columns: 400px 1fr; gap: 20px; }
        .gcm-admin-wrap .gcm-tab-content { display: none; padding: 20px; background: #fff; margin-top: -1px; border: 1px solid #ccc; }
        .gcm-admin-wrap .gcm-tab-content.active { display: block; }
        .gcm-admin-wrap h4 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 20px; }
        .gcm-checklist { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
        .gcm-checklist label { display: block; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.gcm-admin-wrap input[type="color"]').wpColorPicker();
        $('.nav-tab-wrapper a').on('click', function(e) { e.preventDefault(); $('.nav-tab').removeClass('nav-tab-active'); $(this).addClass('nav-tab-active'); $('.gcm-tab-content').removeClass('active'); $($(this).attr('href')).addClass('active'); });
        $('.gcm-admin-wrap').on('click', '.gcm-upload-btn', function(e) {
            e.preventDefault(); var button = $(this);
            wp.media({ title: 'انتخاب تصویر', button: { text: 'استفاده از این تصویر' }, multiple: false }).on('select', function() { var attachment = wp.media.frame.state().get('selection').first().toJSON(); button.prev('input').val(attachment.url); }).open();
        });

        $('#gcm-reset-card-form').on('click', function() {
            $('#gcm-card-form')[0].reset(); $('#gcm-card-id').val('');
            $('#gcm-card-form .wp-color-picker').each(function(){ $(this).val($(this).data('default-color')).trigger('change'); });
        });
        $('#gcm-card-form').on('submit', function(e) {
            e.preventDefault();
            var cardData = {
                action: 'gcm_save_card', _nonce: '<?php echo wp_create_nonce("gcm_save_card_nonce"); ?>',
                id: $('#gcm-card-id').val(), title: $('#gcm-title').val(), description: $('#gcm-description').val(),
                image_url: $('#gcm-image-url').val(), link_url: $('#gcm-link-url').val(), badge: $('#gcm-badge').val(),
                card_style: $('#gcm-card-style').val(), height: $('#gcm-height').val(), image_height: $('#gcm-image-height').val(),
                social_links: JSON.stringify({ whatsapp: $('#gcm-whatsapp').val(), instagram: $('#gcm-instagram').val(), telegram: $('#gcm-telegram').val() }),
                primary_color: $('#gcm-primary-color').val(), secondary_color: $('#gcm-secondary-color').val(), text_color: $('#gcm-text-color').val(),
                bg_color_1: $('#gcm-bg-color-1').val(), bg_color_2: $('#gcm-bg-color-2').val(), padding: $('#gcm-padding').val(),
                border_radius: $('#gcm-border-radius').val(), box_shadow: $('#gcm-box-shadow').val(), text_align: $('#gcm-text-align').val(),
            };
            $.post(ajaxurl, cardData, function(res) { if (res.success) { alert('کارت ذخیره شد!'); location.reload(); } else { alert('خطا: ' + res.data); } });
        });
        $('#gcm-cards-tbody').on('click', '.gcm-edit-card-btn', function() {
            var cardId = $(this).closest('tr').data('card-id');
            $.post(ajaxurl, { action: 'gcm_get_card', id: cardId, _nonce: '<?php echo wp_create_nonce("gcm_get_card_nonce"); ?>' }, function(res) {
                if(res.success) {
                    var card = res.data;
                    $('#gcm-card-id').val(card.id); $('#gcm-title').val(card.title); $('#gcm-description').val(card.description);
                    $('#gcm-image-url').val(card.image_url); $('#gcm-link-url').val(card.link_url); $('#gcm-badge').val(card.badge);
                    $('#gcm-card-style').val(card.card_style); $('#gcm-height').val(card.height); $('#gcm-image-height').val(card.image_height);
                    var social = JSON.parse(card.social_links || '{}');
                    $('#gcm-whatsapp').val(social.whatsapp || ''); $('#gcm-instagram').val(social.instagram || ''); $('#gcm-telegram').val(social.telegram || '');
                    $('#gcm-primary-color').val(card.primary_color).trigger('change'); $('#gcm-secondary-color').val(card.secondary_color).trigger('change');
                    $('#gcm-text-color').val(card.text_color).trigger('change'); $('#gcm-bg-color-1').val(card.bg_color_1).trigger('change');
                    $('#gcm-bg-color-2').val(card.bg_color_2).trigger('change'); $('#gcm-padding').val(card.padding);
                    $('#gcm-border-radius').val(card.border_radius); $('#gcm-box-shadow').val(card.box_shadow); $('#gcm-text-align').val(card.text_align);
                    $('#gcm-title').focus();
                }
            });
        });
        $('#gcm-cards-tbody').on('click', '.gcm-delete-card-btn', function() {
            if (!confirm('آیا از حذف این کارت مطمئن هستید؟')) return;
            var cardId = $(this).closest('tr').data('card-id');
            $.post(ajaxurl, { action: 'gcm_delete_card', id: cardId, _nonce: '<?php echo wp_create_nonce("gcm_delete_card_nonce"); ?>' }, function(res) { if(res.success) { alert('کارت حذف شد.'); location.reload(); } else { alert('خطا: ' + res.data); } });
        });

        $('#gcm-reset-group-form').on('click', function() {
            $('#gcm-group-form')[0].reset(); $('#gcm-group-id').val('');
            $('#gcm-group-cards-checklist input').prop('checked', false);
            $('#gcm-group-form .wp-color-picker').each(function(){ $(this).val('').trigger('change'); });
        });
        $('#gcm-group-form').on('submit', function(e) {
            e.preventDefault();
            var card_ids = $('#gcm-group-cards-checklist input:checked').map(function(){ return $(this).val(); }).get();
            var groupData = {
                action: 'gcm_save_group', _nonce: '<?php echo wp_create_nonce("gcm_save_group_nonce"); ?>',
                id: $('#gcm-group-id').val(), name: $('#gcm-group-name').val(), card_ids: card_ids,
                uniform_height: $('#gcm-group-height').val(), uniform_image_height: $('#gcm-group-image-height').val(), primary_color: $('#gcm-group-primary-color').val(), 
                secondary_color: $('#gcm-group-secondary-color').val(), text_color: $('#gcm-group-text-color').val(), bg_color_1: $('#gcm-group-bg-color-1').val(),
                bg_color_2: $('#gcm-group-bg-color-2').val(), padding: $('#gcm-group-padding').val(), border_radius: $('#gcm-group-border-radius').val(),
                box_shadow: $('#gcm-group-box-shadow').val(), text_align: $('#gcm-group-text-align').val(),
            };
            $.post(ajaxurl, groupData, function(res) { if (res.success) { alert('گروه ذخیره شد!'); location.reload(); } else { alert('خطا: ' + res.data); } });
        });
        $('#gcm-groups-tbody').on('click', '.gcm-edit-group-btn', function() {
            var groupId = $(this).closest('tr').data('group-id');
            $.post(ajaxurl, { action: 'gcm_get_group', id: groupId, _nonce: '<?php echo wp_create_nonce("gcm_get_group_nonce"); ?>' }, function(res) {
                if(res.success) {
                    var group = res.data;
                    $('#gcm-group-id').val(group.id); $('#gcm-group-name').val(group.name);
                    $('#gcm-group-cards-checklist input').prop('checked', false);
                    if(group.cards) { group.cards.forEach(function(cardId) { $('#gcm-group-cards-checklist input[value="' + cardId + '"]').prop('checked', true); }); }
                    $('#gcm-group-height').val(group.uniform_height); $('#gcm-group-image-height').val(group.uniform_image_height);
                    $('#gcm-group-primary-color').val(group.primary_color).trigger('change'); $('#gcm-group-secondary-color').val(group.secondary_color).trigger('change');
                    $('#gcm-group-text-color').val(group.text_color).trigger('change'); $('#gcm-group-bg-color-1').val(group.bg_color_1).trigger('change');
                    $('#gcm-group-bg-color-2').val(group.bg_color_2).trigger('change'); $('#gcm-group-padding').val(group.padding);
                    $('#gcm-group-border-radius').val(group.border_radius); $('#gcm-group-box-shadow').val(group.box_shadow);
                    $('#gcm-group-text-align').val(group.text_align); $('#gcm-group-name').focus();
                }
            });
        });
        $('#gcm-groups-tbody').on('click', '.gcm-delete-group-btn', function() {
            if (!confirm('آیا از حذف این گروه مطمئن هستید؟')) return;
            var groupId = $(this).closest('tr').data('group-id');
            $.post(ajaxurl, { action: 'gcm_delete_group', id: groupId, _nonce: '<?php echo wp_create_nonce("gcm_delete_group_nonce"); ?>' }, function(res) { if(res.success) { alert('گروه حذف شد.'); location.reload(); } else { alert('خطا: ' + res.data); } });
        });
    });
    </script>
    <?php
    }
    
    public function save_card() {
        check_ajax_referer('gcm_save_card_nonce', '_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('عدم دسترسی');
        global $wpdb; $table = $wpdb->prefix . 'gcm_cards';
        $data = [
            'title' => sanitize_text_field($_POST['title']), 'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']), 'link_url' => esc_url_raw($_POST['link_url']),
            'badge' => sanitize_text_field($_POST['badge']), 'card_style' => sanitize_text_field($_POST['card_style']),
            'height' => sanitize_text_field($_POST['height']), 'image_height' => sanitize_text_field($_POST['image_height']),
            'social_links' => wp_kses_post($_POST['social_links']), 'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color']), 'text_color' => sanitize_hex_color($_POST['text_color']),
            'bg_color_1' => sanitize_hex_color($_POST['bg_color_1']), 'bg_color_2' => sanitize_hex_color($_POST['bg_color_2']),
            'padding' => sanitize_text_field($_POST['padding']), 'border_radius' => sanitize_text_field($_POST['border_radius']),
            'box_shadow' => sanitize_text_field($_POST['box_shadow']), 'text_align' => sanitize_text_field($_POST['text_align']),
        ];
        $id = isset($_POST['id']) && !empty($_POST['id']) ? absint($_POST['id']) : 0;
        if ($id) { $wpdb->update($table, $data, ['id' => $id]); } else { $wpdb->insert($table, $data); }
        wp_send_json_success();
    }

    public function get_card() {
        check_ajax_referer('gcm_get_card_nonce', '_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('عدم دسترسی');
        global $wpdb; $id = absint($_POST['id']);
        $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gcm_cards WHERE id = %d", $id));
        if ($card) { wp_send_json_success($card); } else { wp_send_json_error('کارت یافت نشد.'); }
    }

    public function delete_card() {
        check_ajax_referer('gcm_delete_card_nonce', '_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('عدم دسترسی');
        global $wpdb; $id = absint($_POST['id']);
        $wpdb->delete($wpdb->prefix . 'gcm_cards', ['id' => $id]);
        $wpdb->delete($wpdb->prefix . 'gcm_relationships', ['card_id' => $id]);
        wp_send_json_success();
    }

    public function save_group() {
        check_ajax_referer('gcm_save_group_nonce', '_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('عدم دسترسی');
        global $wpdb;
        $groups_table = $wpdb->prefix . 'gcm_groups';
        $rel_table = $wpdb->prefix . 'gcm_relationships';
        $data = [
            'name' => sanitize_text_field($_POST['name']), 'uniform_height' => sanitize_text_field($_POST['uniform_height']),
            'uniform_image_height' => sanitize_text_field($_POST['uniform_image_height']),
            'primary_color' => sanitize_hex_color($_POST['primary_color']), 'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
            'text_color' => sanitize_hex_color($_POST['text_color']), 'bg_color_1' => sanitize_hex_color($_POST['bg_color_1']),
            'bg_color_2' => sanitize_hex_color($_POST['bg_color_2']), 'padding' => sanitize_text_field($_POST['padding']),
            'border_radius' => sanitize_text_field($_POST['border_radius']), 'box_shadow' => sanitize_text_field($_POST['box_shadow']),
            'text_align' => sanitize_text_field($_POST['text_align']),
        ];
        $id = isset($_POST['id']) && !empty($_POST['id']) ? absint($_POST['id']) : 0;
        $card_ids = isset($_POST['card_ids']) ? array_map('absint', $_POST['card_ids']) : [];
        if ($id) { $wpdb->update($groups_table, $data, ['id' => $id]); } 
        else { $wpdb->insert($groups_table, $data); $id = $wpdb->insert_id; }
        $wpdb->delete($rel_table, ['group_id' => $id]);
        if (!empty($card_ids)) { foreach ($card_ids as $card_id) { $wpdb->insert($rel_table, ['group_id' => $id, 'card_id' => $card_id]); } }
        wp_send_json_success();
    }

    public function get_group() {
        check_ajax_referer('gcm_get_group_nonce', '_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('عدم دسترسی');
        global $wpdb; $id = absint($_POST['id']);
        $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gcm_groups WHERE id = %d", $id));
        if ($group) {
            $group->cards = $wpdb->get_col($wpdb->prepare("SELECT card_id FROM {$wpdb->prefix}gcm_relationships WHERE group_id = %d", $id));
            wp_send_json_success($group);
        } else { wp_send_json_error('گروه یافت نشد.'); }
    }

    public function delete_group() {
        check_ajax_referer('gcm_delete_group_nonce', '_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('عدم دسترسی');
        global $wpdb; $id = absint($_POST['id']);
        $wpdb->delete($wpdb->prefix . 'gcm_groups', ['id' => $id]);
        $wpdb->delete($wpdb->prefix . 'gcm_relationships', ['group_id' => $id]);
        wp_send_json_success();
    }

    public function render_single_card_shortcode($atts) {
        $atts = shortcode_atts(['id' => 0], $atts, 'guru_card');
        if (!$atts['id']) return '';
        global $wpdb;
        $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gcm_cards WHERE id = %d", $atts['id']));
        return $card ? $this->render_card_html($card) : '<!-- Card Not Found -->';
    }

    public function render_card_group_shortcode($atts) {
        $atts = shortcode_atts(['id' => 0, 'display' => 'grid'], $atts, 'guru_card_group');
        if (!$atts['id']) return '';
        global $wpdb;
        $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gcm_groups WHERE id = %d", $atts['id']));
        $card_ids = $wpdb->get_col($wpdb->prepare("SELECT card_id FROM {$wpdb->prefix}gcm_relationships WHERE group_id = %d", $atts['id']));
        if (empty($card_ids)) return '<!-- Group is empty -->';
        $cards_html = '';
        foreach ($card_ids as $card_id) {
            $card = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gcm_cards WHERE id = %d", $card_id));
            if($card) {
                $rendered_card = $this->render_card_html($card, $group);
                $cards_html .= ($atts['display'] === 'carousel') ? "<div class='swiper-slide'>{$rendered_card}</div>" : $rendered_card;
            }
        }
        if ($atts['display'] === 'carousel') {
            wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css');
            wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], null, true);
            $carousel_id = 'gcm-carousel-' . uniqid();
            $output = "<div id='{$carousel_id}' class='gcm-carousel swiper'><div class='swiper-wrapper'>{$cards_html}</div><div class='swiper-button-next'></div><div class='swiper-button-prev'></div></div>";
            $init_script = "new Swiper('#{$carousel_id}', { loop: true, autoHeight: true, navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }, breakpoints: { 320: { slidesPerView: 1, spaceBetween: 10 }, 768: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 30 } } });";
            wp_add_inline_script('swiper-js', $init_script, 'after');
            return $output;
        }
        return "<div class='gcm-grid'>{$cards_html}</div>";
    }

    private function render_card_html($card, $group_style = null) {
        $height = !empty($group_style->uniform_height) ? $group_style->uniform_height : $card->height;
        $image_height = !empty($group_style->uniform_image_height) ? $group_style->uniform_image_height : $card->image_height;
        $bg1 = !empty($group_style->bg_color_1) ? $group_style->bg_color_1 : $card->bg_color_1;
        $bg2 = !empty($group_style->bg_color_2) ? $group_style->bg_color_2 : $card->bg_color_2;
        $primary_color = !empty($group_style->primary_color) ? $group_style->primary_color : $card->primary_color;
        $secondary_color = !empty($group_style->secondary_color) ? $group_style->secondary_color : $card->secondary_color;
        $text_color = !empty($group_style->text_color) ? $group_style->text_color : $card->text_color;
        $padding = !empty($group_style->padding) ? $group_style->padding : $card->padding;
        $border_radius = !empty($group_style->border_radius) ? $group_style->border_radius : $card->border_radius;
        $box_shadow = !empty($group_style->box_shadow) ? $group_style->box_shadow : $card->box_shadow;
        $text_align = !empty($group_style->text_align) ? $group_style->text_align : $card->text_align;

        ob_start();
        $bg_style = $bg2 ? "background: linear-gradient(45deg, {$bg1}, {$bg2});" : "background-color: {$bg1};";
        ?>
        <div class="gcm-card-container">
            <style>
                #gcm-card-<?php echo $card->id; ?> {
                    <?php echo $bg_style; ?>
                    color: <?php echo $text_color; ?>;
                    padding: <?php echo $padding; ?>;
                    border-radius: <?php echo $border_radius; ?>;
                    box-shadow: <?php echo $box_shadow; ?>;
                    text-align: <?php echo $text_align; ?>;
                    height: <?php echo $height; ?>;
                }
                <?php if (!empty($image_height) && ($card->card_style === 'classic' || $card->card_style === 'modern')): ?>
                #gcm-card-<?php echo $card->id; ?> .card-image {
                    height: <?php echo esc_attr($image_height); ?>;
                }
                <?php endif; ?>
                #gcm-card-<?php echo $card->id; ?> h3 a { color: inherit; }
                #gcm-card-<?php echo $card->id; ?> a:hover { color: <?php echo $primary_color; ?>; }
                #gcm-card-<?php echo $card->id; ?> .card-badge, #gcm-card-<?php echo $card->id; ?> .profile-badge { background-color: <?php echo $secondary_color; ?>; }
                #gcm-card-<?php echo $card->id; ?> .gcm-social-icons a { color: <?php echo $primary_color; ?>; }
                #gcm-card-<?php echo $card->id; ?> .gcm-social-icons a:hover { background-color: <?php echo $primary_color; ?>; color: #fff; }
            </style>
            <div id="gcm-card-<?php echo $card->id; ?>" class="gcm-card style-<?php echo esc_attr($card->card_style); ?>">
                <?php
                $social_links = json_decode($card->social_links, true); $social_html = '';
                if ($social_links && is_array($social_links) && array_filter($social_links)) {
                    $social_html .= '<div class="gcm-social-icons">';
                    if (!empty($social_links['whatsapp'])) $social_html .= '<a href="'.esc_url($social_links['whatsapp']).'" target="_blank"><i class="fab fa-whatsapp"></i></a>';
                    if (!empty($social_links['instagram'])) $social_html .= '<a href="'.esc_url($social_links['instagram']).'" target="_blank"><i class="fab fa-instagram"></i></a>';
                    if (!empty($social_links['telegram'])) $social_html .= '<a href="'.esc_url($social_links['telegram']).'" target="_blank"><i class="fab fa-telegram"></i></a>';
                    $social_html .= '</div>';
                }
                
                if ($card->card_style === 'classic' || $card->card_style === 'modern') { ?>
                    <div class="card-image">
                        <a href="<?php echo esc_url($card->link_url); ?>"><img src="<?php echo esc_url($card->image_url); ?>" alt="<?php echo esc_attr($card->title); ?>"></a>
                        <?php if ($card->badge): ?><div class="card-badge"><?php echo esc_html($card->badge); ?></div><?php endif; ?>
                        <?php if ($card->card_style === 'modern'): ?><div class="card-overlay"></div><?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3><a href="<?php echo esc_url($card->link_url); ?>"><?php echo esc_html($card->title); ?></a></h3>
                        <p><?php echo esc_html($card->description); ?></p>
                        <?php echo $social_html; ?>
                    </div>
                <?php } elseif ($card->card_style === 'profile') { ?>
                    <div class="profile-image"><img src="<?php echo esc_url($card->image_url); ?>" alt="<?php echo esc_attr($card->title); ?>"></div>
                    <div class="card-content">
                        <h3><a href="<?php echo esc_url($card->link_url); ?>"><?php echo esc_html($card->title); ?></a></h3>
                        <p><?php echo esc_html($card->description); ?></p>
                        <?php echo $social_html; ?>
                    </div>
                    <?php if ($card->badge): ?><div class="profile-badge"><?php echo esc_html($card->badge); ?></div><?php endif; ?>
                <?php } ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new GuruCartMaster();
register_activation_hook(__FILE__, function() { $plugin = new GuruCartMaster(); $plugin->create_database_tables(); });
