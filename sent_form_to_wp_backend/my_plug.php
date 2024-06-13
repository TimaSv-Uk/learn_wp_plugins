<?php
/*
 *
 * Plugin Name: My Plugin
 * Description: my nooby plugin
 * Version:     0.0.0
 * Text Domain: my_plugin
 *
 */

if (!defined("ABSPATH")) {
    exit;
}

class ConatactForm
{


    public function __construct()
    {
        add_action("init", array($this, "create_my_post_type"));

        add_action("wp_enqueue_scripts", array($this, "load_assets"));

        add_shortcode("contact_form", array($this, "load_shortcode"));

        add_action("wp_footer", array($this, "load_scrypt"));

        add_action("rest_api_init", array($this, "register_rest_api"));
    }
    public function create_my_post_type()
    {
        $args = array(
            "public" => true,
            "has_archive" => true,
            "exclude_from_search" => true,
            "publicly_queryable" => false,
            "capability" => [],
            "labels" => array(
                "name" => "Contact Form",
                "singular_name" => "Contact Form Entry"
            ),
            "menu_icon" => "dashicons-media-text",
            "supports" => array("title", "editor", "custom-fields"),
        );
        register_post_type("ContactFormEntry", $args);
    }
    public function load_assets()
    {
        wp_enqueue_style(
            "my_plugin",
            plugin_dir_url(__FILE__) . "css/form.css",
            array(),
            1,
            "all"
        );
        wp_enqueue_script(
            "my_plugin",
            plugin_dir_url(__FILE__) . "js/form.js",
            array("jquery"),
            1,
            true
        );
    }
    public function load_shortcode()
    { ?>
        <div>
            <h1>Send us an email</h1>
            <p>Please fill the below form</p>
            <form id="simple-contact-form___form">

                <div class="form-group">
                    <div class="form-group mb-2">
                        <input type="text" name="name" placeholder="Name" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <input type="email" name="email" placeholder="Email" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <input type="text" name="phone" placeholder="Phone" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <textarea name="message" placeholder="Type your message" class="form-control"></textarea>
                    </div>
                    <button class="btn btn-success btn-block w-100">Send Message</button>
                </div>
            </form>
            <div id="form_success" style="background-color: aquamarine; width: 100px;"></div>
            <div id="form_error" style="background-color: red; width: 100px;"></div>
        </div>
        <?php
    }

    public function load_scrypt()
    { ?>
        <script>
            (function ($) {
                let nonce = "<?php echo wp_create_nonce("wp_rest"); ?>"
                $("#simple-contact-form___form").submit(function (event) {
                    event.preventDefault();
                    let form = $(this).serialize();
                    $.ajax({
                        method: "post",
                        url: "<?php echo get_rest_url(null, "my_plugin/v1/send_email") ?>",
                        headers: {"X-WP-Nonce": nonce},
                        data: form,
                        success: function () {
                            $("#form_success").html("Your messege was sent").fadeIn();
                            $("#form_error").hide();
                        },
                        error: function () {
                            $("#form_error").html("Your messege was not sent").fadeIn()
                            $("#form_success").hide();
                        },
                    })
                });

            })(jQuery)
        </script>
        <?php
    }

    public function register_rest_api()
    {
        register_rest_route(
            "my_plugin/v1",
            "send_email",
            array(
                "methods" => "POST",
                "callback" => array($this, "handle_contact_form")
            )
        );
    }

    public function handle_contact_form($data)
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers["x_wp_nonce"][0];
        if (!wp_verify_nonce($nonce, "wp_rest")) {
            return new WP_REST_Response("Messege not sent", 422);
        }

        $name = sanitize_text_field($params['name']);
        $email = sanitize_email($params['email']);
        $phone = sanitize_text_field($params['phone']);
        $message = sanitize_textarea_field($params['message']);

        $post_id = wp_insert_post(
            array(
                "post_type" => "ContactFormEntry",
                "post_title" => $name,
                "post_status" => "publish",
                "post_content" => $message,
                "meta_input" => array(
                    "email" => $email,
                    "phone" => $phone,
                    "message" => $message,
                )
            )
        );

        if (!$post_id) {
            return new WP_REST_Response("messege was not sent", 500);
        }

        return new WP_REST_Response("messege was  sent", 200);
    }

}

new ConatactForm;