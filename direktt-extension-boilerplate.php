<?php

/**
 * Plugin Name: Direktt Extension BoilerPlate
 * Plugin URI: https://direktt.com
 * Description: Minimal Direktt Extension Boilerplate.
 * Version: 1.0.0
 * Author: Direktt
 * Author URI: https://direktt.com
 * License: GPL2
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'direktt_fe_boilerplate');

add_action('plugins_loaded', 'direktt_bp_activation_check', -20);

// add_action('direktt/event/chat/message_sent', 'on_message_sent');
// add_action('direktt/action/test_action', 'on_test_action');
// add_action('direktt/action/test_action_recurring', 'on_test_action_recurring');

add_shortcode('direktt_user_info', 'direktt_user_info');

function direktt_user_info( $atts ) {
  // Merge attributes with defaults (both attributes are comma-separated slugs).
  $atts = shortcode_atts(
      array(
          'categories' => '',
          'tags'       => '',
      ),
      $atts,
      'direktt_user_info'
  );

  // Parse categories/tags attributes into arrays, trim whitespace, ignore empty.
  $categories = array_filter( array_map( 'trim', explode( ',', $atts['categories'] ) ) );
  $tags       = array_filter( array_map( 'trim', explode( ',', $atts['tags'] ) ) );

  global $direktt_user;

  ob_start();

  var_dump($_GET);

  // Users without correct role/taxonomy or not Direktt users see nothing.
  return ob_get_clean();

}

add_action('init', function () {

    if (class_exists('Direktt_Automation_ProcessorRegistry')) {
        Direktt_Automation_ProcessorRegistry::register('send_direktt_message', 'process');
        Direktt_Automation_ProcessorRegistry::register('send_direktt_recurring_message', 'process_recurr');
    }
});

function direktt_fe_boilerplate()
{
    add_action('direktt_enqueue_public_scripts', 'enqueue_plugin_assets');
}

function direktt_bp_activation_check()
{

    if (! function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $required_plugin = 'direktt/direktt.php';
    $is_required_active = is_plugin_active($required_plugin)
        || (is_multisite() && is_plugin_active_for_network($required_plugin));

    if (! $is_required_active) {
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Prevent the “Plugin activated.” notice
        if (isset($_GET['activate'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Justification: not a form processing, just removing a query var.
            unset($_GET['activate']);
        }

        // Show an error notice for this request
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html__('Boilerplate activation failed: The Direktt WordPress Plugin must be active first.', 'direktt-cf7')
                . '</p></div>';
        });

        // Optionally also show the inline row message in the plugins list
        add_action(
            'after_plugin_row_direktt-frontend-boilerplate/direktt-frontend-boilerplate.php',
            function () {
                echo '<tr class="plugin-update-tr"><td colspan="3" style="box-shadow:none;">'
                    . '<div style="color:#b32d2e;font-weight:bold;">'
                    . esc_html__('Boilerplate requires the Direktt WordPress Plugin to be active. Please activate it first.', 'direktt-cf7')
                    . '</div></td></tr>';
            },
            10,
            0
        );
    }
}

function enqueue_plugin_assets(string $suffix)
{

    global $direktt_user;

    if ($direktt_user) {
        wp_enqueue_script(
            'direktt_fe_boilerplate',
            plugin_dir_url(__FILE__) . 'js/direktt-fe-frontend.js',
            array('jquery', 'direktt_public'),
            '',
            [
                'in_footer' => true,
            ]
        );

        if (array_key_exists('direktt_user_id', $direktt_user) && $direktt_user['direktt_user_id']) {
            global $wp;

            Direktt_Event::insert_event(
                array(
                    "direktt_user_id" => $direktt_user['direktt_user_id'],
                    "event_target" => "page",
                    "event_type" => "visited",
                    "event_value" => home_url($wp->request)
                )
            );
        }
    }
}

// Sample Ajax call

function direktt_sample_ajax($atts)
{

    $atts = shortcode_atts(
        array(
            'categories' => '',
            'tags' => ''
        ),
        $atts,
        'direktt_user_profile'
    );

    $categories = array_filter(array_map('trim', explode(',', $atts['categories'])));
    $tags = array_filter(array_map('trim', explode(',', $atts['tags'])));

    global $direktt_user;

    ob_start();

    $direktt_user_post = isset($direktt_user['direktt_user_id'])
        ? Direktt_User::get_user_by_subscription_id($direktt_user['direktt_user_id'])
        : false;

    if ($direktt_user_post && ((!$categories && !$tags) || Direktt_User::has_direktt_taxonomies($direktt_user, $categories, $tags) || Direktt_User::is_direktt_admin())) {

        $nonce = wp_create_nonce('direktt_btnclick_nonce');

?>

        <button id="btn">Click me</button>
        <script type="text/javascript">
            document.getElementById('btn').addEventListener('click', function() {
                var data = new FormData();
                data.append('action', 'direktt_btnclick');
                data.append('nonce', '<?php echo esc_js($nonce); ?>');
                data.append('post_id', direktt_public.direktt_post_id);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: data
                    })
                    .then(response => response.json())
                    .then(result => {
                        console.log('Server says: ' + result.message);
                    });
            });
        </script>

    <?php

    }

    return ob_get_clean();
}

function direktt_btnclick_handler()
{
    if (!isset($_POST['post_id'])) {
        wp_send_json(['status' => 'post_id_failed'], 400);
    }

    $post_id = intval($_POST['post_id']);

    $post = get_post($post_id);

    if ($post && Direktt_Public::direktt_ajax_check_user($post)) {

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'direktt_btnclick_nonce')) {
            wp_send_json(['status' => 'nonce_failed'], 401);
        }

        $current_user = wp_get_current_user();
        $direktt_user = Direktt_User::get_direktt_user_by_wp_user($current_user);

        wp_send_json(['message' => 'Subscription Id: ' . $direktt_user['direktt_user_id']], 200);
    } else {
        wp_send_json(['status' => 'non_authorized'], 401);
    }
}

add_shortcode('direktt_sample_ajax', 'direktt_sample_ajax');
add_action('wp_ajax_direktt_btnclick', 'direktt_btnclick_handler');

// End of Sample Ajax call


// Sample Rest call

function direktt_sample_rest($atts)
{

    $atts = shortcode_atts(
        array(
            'categories' => '',
            'tags' => ''
        ),
        $atts,
        'direktt_user_profile'
    );

    $categories = array_filter(array_map('trim', explode(',', $atts['categories'])));
    $tags = array_filter(array_map('trim', explode(',', $atts['tags'])));

    global $direktt_user;

    ob_start();

    $direktt_user_post = isset($direktt_user['direktt_user_id'])
        ? Direktt_User::get_user_by_subscription_id($direktt_user['direktt_user_id'])
        : false;

    if ($direktt_user_post && ((!$categories && !$tags) || Direktt_User::has_direktt_taxonomies($direktt_user, $categories, $tags) || Direktt_User::is_direktt_admin())) {

    ?>

        <button id="btnrest">Click me</button>
        <script type="text/javascript">
            document.getElementById('btnrest').addEventListener('click', function() {
                var data = JSON.stringify({
                    post_id: direktt_public.direktt_post_id
                });
                fetch('<?php echo get_rest_url(null, 'direktt/v1/sampleRest/'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': direktt_public.direktt_wp_rest_nonce
                        },
                        credentials: 'same-origin',
                        body: data
                    })
                    .then(response => response.json())
                    .then(result => {
                        console.log('Server says: ' + result.message);
                    });
            });
        </script>

    <?php

    }

    return ob_get_clean();
}

function register_direktt_sample_rest()
{
    register_rest_route('direktt/v1', '/sampleRest/', array(
        'methods' => 'POST',
        'callback' => 'direktt_btnclick_rest_handler',
        'args' => array(),
        'permission_callback' => 'api_validate_sample_handler'
    ));
}

function api_validate_sample_handler(WP_REST_Request $request)
{
    $parameters = json_decode($request->get_body(), true);

    if (array_key_exists('post_id', $parameters)) {
        $post_id = sanitize_text_field($parameters['post_id']);
        $post = get_post($post_id);
        if ($post && Direktt_Public::direktt_ajax_check_user($post)) {
            return true;
        }
    }
    return false;
}

function direktt_btnclick_rest_handler(WP_REST_Request $request)
{
    $current_user = wp_get_current_user();
    $direktt_user = Direktt_User::get_direktt_user_by_wp_user($current_user);
    wp_send_json(['message' => 'Subscription Id: ' . $direktt_user['direktt_user_id']], 200);
}

add_shortcode('direktt_sample_rest', 'direktt_sample_rest');
add_action('rest_api_init', 'register_direktt_sample_rest');


// End Rest sample

function on_message_sent($event)
{
    //plugin_log($event);

    // Link
    $pushNotificationMessage = array(
        "type" =>  "rich",
        "content" => json_encode(
            array(
                "subtype" => "button",
                "txt" => "This is my button",
                "label" => "This is button you can click",
                "action" => array(
                    "type" => "api",
                    "params" => array(
                        "actionType" => "test_action"
                    ),
                    "retVars" => array(
                        "testVar" => "blah"
                    )
                )
            )
        )
    );

    /*$pushNotificationMessageAdmin = array(
        "type" =>  "rich",
        "content" => json_encode(
            array(
                "subtype" => "button",
                "txt" => $event['direktt_user_id'],
                "label" => "Click to open chat",
                "action" => array(
                    "type" => "chat",
                    "params" => array(
                        "subscriptionId" => $event['direktt_user_id']
                    ),
                    "retVars" => new stdClass()
                )
            )
        )
    );*/

    /*$pushNotificationMessageAdmin = array(
        "type" =>  "rich",
        "content" => json_encode(
            array(
                "subtype" => "button",
                "txt" => $event['direktt_user_id'],
                "label" => "Click to open profile",
                "action" => array(
                    "type" => "profile",
                    "params" => array(
                        "subscriptionId" => $event['direktt_user_id']
                    ),
                    "retVars" => new stdClass()
                )
            )
        )
    );*/

    //Direktt_Message::send_message(array( $event['direktt_user_id']) => $pushNotificationMessage );
    //Direktt_Message::send_message_template( array( $event['direktt_user_id'] ), 192);
    //Direktt_Message::send_message_to_admin( $pushNotificationMessageAdmin );
}

function on_test_action($params)
{
    global $direktt_user;

    $pushNotificationMessageAdmin = array(
        "type" =>  "text",
        "content" => "stigao API " . json_encode($params)
    );

    $pushNotificationMessage = array(
        "type" =>  "picture",
        "media" =>  "https://omnicom.rs/wp-content/uploads/2025/02/2-background.png",
        "thumbnail" =>  "https://omnicom.rs/wp-content/uploads/2025/02/2-background.png",
        "content" => "Photo description",
        "width" =>  1200,
        "height" =>  1200
    );

    //Direktt_Message::send_message_to_admin($pushNotificationMessageAdmin);

    //Direktt_Message::send_message( array( $direktt_user['direktt_user_id'] => $pushNotificationMessage ) );

    Direktt_Automation::run_and_queue(
        "direktt_auto_message",
        $direktt_user['direktt_user_id'],
        $pushNotificationMessage,
        'send_direktt_message',
        10,
        'message_10s',
    );
}

function on_test_action_recurring($params)
{
    global $direktt_user;

    $pushNotificationMessage = array(
        "type" =>  "text",
        "content" => "stigao API recurr"
    );

    Direktt_Automation::run_and_queue_recurring(
        'direktt_auto_message_recurring',
        $direktt_user['direktt_user_id'],
        $pushNotificationMessage,
        'send_direktt_recurring_message',
        10,          // start in 10s
        20,          // every 20s
        'recurr_step_1',
        1,           // max_runs
        null,        // end_ts
        false,       // allow_overlap
        0            // priority
    );
}

function process(array $queue_item, array $run)
{
    $runs  = new Direktt_Automation_RunRepository();

    $this_step_id = $payload['step_id'] ?? ($run['current_step'] ?? 'message_10s');

    if ($this_step_id !== 'final_step') {
        $payload = isset($queue_item['payload']) && is_array($queue_item['payload']) ? $queue_item['payload'] : [];
        $messages = new Direktt_Automation_MessagesLogRepository();
        $msg_id   = $messages->log_queued(
            (int) $queue_item['run_id'],
            $queue_item['direktt_user_id'],
            $this_step_id,
            'direktt_message',
            $payload['template_id'] ?? null,
            $queue_item['scheduled_at']
        );

        $sent = Direktt_Message::send_message(array($queue_item['direktt_user_id'] => $payload));

        if ($sent) {
            $messages->mark_sent($msg_id, null);
        } else {
            $messages->mark_failed($msg_id, 'direktt send_message returned false');
            throw new \RuntimeException('send_message failed');
        }

        // 3) Update run state (append some useful info)
        $state = is_array($run['state'] ?? null) ? $run['state'] : [];
        $state['last_message'] = [
            'message_id' => $msg_id,
            'step_id'    => $this_step_id,
            'sent_at'    => Direktt_Automation_Time::now_utc(),
            'to'         => $queue_item['direktt_user_id'],
        ];
        $runs->update_state((int)$run['id'], $state);

        if ($this_step_id == 'message_10s') {
            $next_step_id = 'message_20s';
        } else if ($this_step_id == 'message_20s') {
            $next_step_id = 'final_step';
        }

        $advanced = $runs->set_step_if_current((int)$run['id'], $this_step_id, $next_step_id, true);

        if ($advanced && $next_step_id === 'final_step') {
            $runs->set_status((int)$run['id'], 'completed');
            return;
        }

        if ($advanced && $next_step_id !== 'final_step') {
            // Only enqueue the next step if we actually advanced.
            $queue = new Direktt_Automation_QueueRepository();
            $next_payload = array(
                "type" =>  "text",
                "content" => "20 seconds later",
            );

            $queue->enqueue(
                (int)$run['id'],
                $queue_item['direktt_user_id'],
                'send_direktt_message',
                $next_payload, //tekuci payload
                time() + 20, // schedule in 20 seconds
                0
            );
        }
    }
}

function process_recurr(array $queue_item, array $run)
{
    $runs  = new Direktt_Automation_RunRepository();
    $messages = new Direktt_Automation_MessagesLogRepository();

    $payload = isset($queue_item['payload']) && is_array($queue_item['payload']) ? $queue_item['payload'] : [];

    $this_step_id = $payload['step_id'] ?? ($run['current_step'] ?? 'message_recurring');

    $msg_id   = $messages->log_queued(
        (int) $queue_item['run_id'],
        $queue_item['direktt_user_id'],
        $this_step_id,
        'direktt_message',
        $payload['template_id'] ?? null,
        $queue_item['scheduled_at']
    );

    $sent = Direktt_Message::send_message(array($queue_item['direktt_user_id'] => $payload));

    if ($sent) {
        $messages->mark_sent($msg_id, null);
    } else {
        $messages->mark_failed($msg_id, 'direktt send_message returned false');
        throw new \RuntimeException('send_message failed');
    }

    /*
        // Update run state (but DO NOT complete the run here)
        $state = is_array($run['state'] ?? null) ? $run['state'] : [];
        $state['sent_count'] = isset($state['sent_count']) ? (int)$state['sent_count'] + 1 : 1;
        $state['last_message'] = [
            'message_id' => $msg_id,
            'step_id'    => $this_step_id,
            'sent_at'    => Direktt_Automation_Time::now_utc(),
            'to'         => $queue_item['direktt_user_id'],
        ];
        $runs->update_state((int)$run['id'], $state);
    */
}

function plugin_log($request)
{
    $location = $_SERVER['REQUEST_URI'];
    $time = date("F jS Y, H:i", time() + 25200);
    $debug_info = var_export($request, true);
    $ban = "#$time\r\n$location\r\n$debug_info\r\n";
    $file = plugin_dir_path(__FILE__) . '/plugin.txt';
    $open = fopen($file, "a");
    $write = fputs($open, $ban);
    fclose($open);
}

//// Welcome plugin

add_action('direktt_setup_settings_pages', 'setup_settings_pages');
add_action('direktt/user/subscribe', 'on_direktt_subscribe_user');

function setup_settings_pages()
{

    Direktt::add_settings_page(
        array(
            "id" => "welcome-message",
            "label" => __('Welcome Message Settings', 'direktt_boilerplate'),
            "callback" => 'render_welcome_settings',
            "priority" => 1
        )
    );
}

function on_direktt_subscribe_user($direktt_user_id)
{
    $user_obj = Direktt_User::get_user_by_subscription_id($direktt_user_id);

    $user_title = get_the_title($user_obj['ID']);

    $welcome_user = get_option('direktt_welcome_user', 'no') === 'yes';
    $welcome_user_template = intval(get_option('direktt_welcome_user_template', 0));
    $welcome_admin = get_option('direktt_welcome_admin', 'no') === 'yes';
    $welcome_admin_template = intval(get_option('direktt_welcome_admin_template', 0));

    if ($welcome_user && $welcome_user_template != 0) {

        Direktt_Message::send_message_template(
            [$direktt_user_id],
            $welcome_user_template,
            [
                "title" =>  $user_title
            ]
        );
    }

    if ($welcome_admin && $welcome_admin_template != 0) {

        Direktt_Message::send_message_template_to_admin(
            $welcome_admin_template,
            [
                "title" =>  $user_title,
                "subscriptionId" => strval($direktt_user_id)
            ]
        );
    }
}

function render_welcome_settings()
{
    // Success message flag
    $success = false;

    // Handle form submission
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direktt_admin_welcome_nonce'])
        && wp_verify_nonce($_POST['direktt_admin_welcome_nonce'], 'direktt_admin_welcome_save')
    ) {
        // Sanitize and update options
        update_option('direktt_welcome_user', isset($_POST['direktt_welcome_user']) ? 'yes' : 'no');
        update_option('direktt_welcome_user_template', intval($_POST['direktt_welcome_user_template']));
        update_option('direktt_welcome_admin', isset($_POST['direktt_welcome_admin']) ? 'yes' : 'no');
        update_option('direktt_welcome_admin_template', intval($_POST['direktt_welcome_admin_template']));
        $success = true;
    }

    // Load stored values
    $welcome_user = get_option('direktt_welcome_user', 'no') === 'yes';
    $welcome_user_template = intval(get_option('direktt_welcome_user_template', 0));
    $welcome_admin = get_option('direktt_welcome_admin', 'no') === 'yes';
    $welcome_admin_template = intval(get_option('direktt_welcome_admin_template', 0));

    // Query for template posts
    $template_args = [
        'post_type'      => 'direkttmtemplates',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => 'direkttMTType',
                'value'   => ['all', 'none'],
                'compare' => 'IN',
            ]
        ]
    ];
    $template_posts = get_posts($template_args);
    ?>
    <div class="wrap">
        <?php if ($success): ?>
            <div class="updated notice is-dismissible">
                <p>Settings saved successfully.</p>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <?php wp_nonce_field('direktt_admin_welcome_save', 'direktt_admin_welcome_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="direktt_welcome_user">New Subscribers</label></th>
                    <td>
                        <input type="checkbox" name="direktt_welcome_user" id="direktt_welcome_user" value="yes" <?php checked($welcome_user); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_welcome_user_template">Subscriber Message Template</label></th>
                    <td>
                        <select name="direktt_welcome_user_template" id="direktt_welcome_user_template">
                            <option value="0">Select Template</option>
                            <?php foreach ($template_posts as $post): ?>
                                <option value="<?php echo esc_attr($post->ID); ?>" <?php selected($welcome_user_template, $post->ID); ?>>
                                    <?php echo esc_html($post->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_welcome_admin">Admin</label></th>
                    <td>
                        <input type="checkbox" name="direktt_welcome_admin" id="direktt_welcome_admin" value="yes" <?php checked($welcome_admin); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="direktt_welcome_admin_template">Admin Message Template</label></th>
                    <td>
                        <select name="direktt_welcome_admin_template" id="direktt_welcome_admin_template">
                            <option value="0">Select Template</option>
                            <?php foreach ($template_posts as $post): ?>
                                <option value="<?php echo esc_attr($post->ID); ?>" <?php selected($welcome_admin_template, $post->ID); ?>>
                                    <?php echo esc_html($post->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
<?php
}

//// End welcome plugin


//// Shortcode example

add_shortcode('direktt_sample_shortcode', 'direktt_sample_queries');

function direktt_sample_shortcode($atts)
{
    $atts = shortcode_atts(
        array(
            'categories' => '',
            'tags' => ''
        ),
        $atts,
        'direktt_user_profile'
    );

    $categories = array_filter(array_map('trim', explode(',', $atts['categories'])));
    $tags = array_filter(array_map('trim', explode(',', $atts['tags'])));

    global $direktt_user;

    ob_start();

    $direktt_user_post = isset($direktt_user['direktt_user_id'])
        ? Direktt_User::get_user_by_subscription_id($direktt_user['direktt_user_id'])
        : false;

    if ($direktt_user_post && ((!$categories && !$tags) || Direktt_User::has_direktt_taxonomies($direktt_user, $categories, $tags) || Direktt_User::is_direktt_admin())) {
        if (Direktt_User::is_direktt_admin()) {
            echo ('<p>Channel Admin</p>');
        } else if (Direktt_User::has_direktt_taxonomies($direktt_user, ["sales-representatives"], [])) {
            echo ('<p>Sales Representative</p>');
        } else {
            echo ('<p>Channel Subscriber</p>');
        }
    }
}

//// End shortcode example

function direktt_sample_queries($atts)
{
    $atts = shortcode_atts(
        array(
            'categories' => '',
            'tags' => ''
        ),
        $atts,
        'direktt_user_profile'
    );

    $categories = array_filter(array_map('trim', explode(',', $atts['categories'])));
    $tags = array_filter(array_map('trim', explode(',', $atts['tags'])));

    global $direktt_user;

    ob_start();

    $direktt_user_post = isset($direktt_user['direktt_user_id'])
        ? Direktt_User::get_user_by_subscription_id($direktt_user['direktt_user_id'])
        : false;

    if ($direktt_user_post && ((!$categories && !$tags) || Direktt_User::has_direktt_taxonomies($direktt_user, $categories, $tags) || Direktt_User::is_direktt_admin())) {
        if (Direktt_User::is_direktt_admin()) {
            echo ('<p>Channel Admin</p>');
        } else if (Direktt_User::has_direktt_taxonomies($direktt_user, ["sales-representatives"], [])) {
            echo ('<p>Sales Representative</p>');
        } else {
            echo ('<p>Channel Subscriber</p>');
        }
    }

    echo $_SERVER['QUERY_STRING'];

    return ob_get_clean();
}


//// Send Message Profle plugin

add_action('direktt_setup_profile_tools', 'setup_profile_tools');

function setup_profile_tools()
{
    Direktt_Profile::add_profile_tool(
        array(
            "id" => "sample-profile-tool",
            "label" => __('Sample Profile Tool', 'direktt_boilerplate'),
            "callback" => 'render_sample_profile_tool',
            "categories" => ['basic-direktt-users'],
            "tags" => ['direktttag1'],
            "priority" => 1,
            "cssEnqueueArray" => [
                array(
                    "handle" => "my-css",
                    "src" => plugin_dir_url(__FILE__) . 'css/mycss.css'
                ),
            ],
            "jsEnqueueArray" => [
                array(
                    "handle" => "my-js",
                    "src" => plugin_dir_url(__FILE__) . 'js/myjs.js'
                )
            ]
        )
    );
}

function render_sample_profile_tool()
{
    echo 'Sample profile tool';
}

//// End Send Message profile plugin
