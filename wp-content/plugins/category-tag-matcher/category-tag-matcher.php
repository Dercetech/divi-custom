<?php
/**
 * Plugin Name: Category Tag Matcher
 * Plugin URI:  https://www.treehouse.solar
 * Description: A plugin to associate products to tags, and link those to use cases.
 * Version:     1.0
 * Author:      Jérémie Mercier
 * Author URI:  https://www.dercetech.com
 * License:     GPL-2.0+
 * Text Domain: category-tag-matcher
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add a new menu item to the WordPress admin dashboard
function create_admin_menu_entry() {
  add_menu_page(
      'Power Stations',   // Page title
      'Power Stations',       // Menu title
      'manage_options',       // Capability required
      'treehouse-products',   // Menu slug
      'main_page',            // Callback function
      'dashicons-palmtree',   // Icon (WordPress Dashicons)
      5                       // Position in the menu
  );
}

// Callback function to display content on the plugin page
function main_page() {
  // Get all tags and filter those starting with "case"
  $tags = get_terms(array(
      'taxonomy' => 'post_tag',
      'hide_empty' => false,
  ));
  $filtered_tags = array_filter($tags, function($tag) {
      return strpos($tag->slug, 'case') === 0;
  });
?>

  <div class="wrap">
      <h1>Treehouse Solar</h1>
      
      <h2>Assign power stations to use cases</h2>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th scope="col" id="title" class="manage-column column-title column-primary">Use case</th>
            <?php
            $power_stations = get_posts(array(
              'category_name' => 'power-stations',
              'post_status'   => 'publish',
              'posts_per_page' => -1,
            ));
            foreach ($power_stations as $power_station) {

                $title = get_post_meta($power_station->ID, 'powerstation_tag', true);
                if (!$title) {
                  $title = explode(' ', $power_station->post_title)[0];
                }

              echo '<th scope="col" class="manage-column column-power-station">' . $title . '</th>';
            }
            ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $use_cases = get_posts(array(
            'category_name' => 'use-cases',
            'post_status'   => 'publish',
            'posts_per_page' => -1,
          ));
          foreach ($use_cases as $use_case) {
            echo '<tr>';
            echo '<td class="title column-title has-row-actions column-primary"><strong>' . esc_html($use_case->post_title) . '</strong></td>';
            foreach ($power_stations as $power_station) {
              $checked = has_tag($power_station->post_name, $use_case->ID) ? 'checked' : '';
              $custom_field_value = get_post_meta($use_case->ID, 'usecase_tag', true);
              $matching_tag = get_term_by('slug', $custom_field_value, 'post_tag');

              echo '<td class="power-station column-power-station">
                  <input type="checkbox" class="usecase-powerstation-checkbox" data-usecase-id="' . esc_attr($matching_tag->term_id) . '" data-powerstation-id="' . esc_attr($power_station->ID) . '" ' . esc_attr($checked) . '>
                  </td>';
            }
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>

      <script>
        document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('.usecase-powerstation-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
              let usecaseId = this.dataset.usecaseId;
              let powerstationId = this.dataset.powerstationId;
              let checked = this.checked ? 1 : 0;

              fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                  action: 'toggle_post_tag',
                  post_id: powerstationId, // the API expects a the power station id, the "post_id" can be misleading here
                  tag_id: usecaseId,
                  checked: checked,
                  security: '<?php echo wp_create_nonce("toggle_post_tag_nonce"); ?>'
                })
              })
              .then(response => response.json())
              .then(data => console.log(data.message))
              .catch(error => console.error('Error:', error));
            });
          });
        });
      </script>

      <h2>Power stations management</h2>
      <table class="wp-list-table widefat fixed striped" style="max-width: 600px;">
          <thead>
              <tr>
                  <th scope="col" id="title" class="manage-column column-title column-primary">power station</th>
                  <th scope="col" class="manage-column column-custom-field">tag</th>
                  <?php
                  /*
                  foreach ($filtered_tags as $tag) {
                      echo '<th scope="col" class="manage-column column-tag">' . esc_html($tag->name) . '</th>';
                  }
                      */
                  ?>
              </tr>
          </thead>
          <tbody>
              <?php
              $args = array(
                  'category_name' => 'power-stations',
                  'post_status'   => 'publish',
                  'posts_per_page' => -1,
              );
              $query = new WP_Query($args);
              if ($query->have_posts()) :
                  while ($query->have_posts()) : $query->the_post();
                      $post_id = get_the_ID();
                      $custom_field_value = get_post_meta($post_id, 'powerstation_tag', true);
                      ?>
                      <tr>
                          <td class="title column-title has-row-actions column-primary">
                              <strong><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></strong>
                          </td>
                          <td class="custom-field column-custom-field">
                              <input type="text" class="custom-field-input" data-post-id="<?php echo esc_attr($post_id); ?>" value="<?php echo esc_attr($custom_field_value); ?>">
                          </td>
                          <?php
                          /*
                            foreach ($filtered_tags as $tag) {
                                $has_tag = has_tag($tag->slug) ? 'checked' : '';
                                echo '<td class="tag column-tag">
                                        <input type="checkbox" class="tag-checkbox" data-post-id="' . esc_attr($post_id) . '" data-tag-id="' . esc_attr($tag->term_id) . '" ' . esc_attr($has_tag) . '>
                                      </td>';
                            }
                          */
                          ?>
                      </tr>
                      <?php
                  endwhile;
                  wp_reset_postdata();
              else :
                  ?>
                  <tr>
                      <td colspan="<?php echo 2 + count($filtered_tags); ?>">No posts found in the "power-stations" category.</td>
                  </tr>
                  <?php
              endif;
              ?>
          </tbody>
      </table>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('.tag-checkbox').forEach(function (checkbox) {
              checkbox.addEventListener('change', function () {
                  let postId = this.dataset.postId;
                  let tagId = this.dataset.tagId;
                  let checked = this.checked ? 1 : 0;

                  fetch(ajaxurl, {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                      body: new URLSearchParams({
                          action: 'toggle_post_tag',
                          post_id: postId,
                          tag_id: tagId,
                          checked: checked,
                          security: '<?php echo wp_create_nonce("toggle_post_tag_nonce"); ?>'
                      })
                  })
                  .then(response => response.json())
                  .then(data => console.log(data.message))
                  .catch(error => console.error('Error:', error));
              });
          });

          document.querySelectorAll('.custom-field-input').forEach(function (input) {
              input.addEventListener('input', function () {
                  let postId = this.dataset.postId;
                  let value = this.value;

                  fetch(ajaxurl, {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                      body: new URLSearchParams({
                          action: 'update_powerstation_tag',
                          post_id: postId,
                          value: value,
                          security: '<?php echo wp_create_nonce("update_powerstation_tag_nonce"); ?>'
                      })
                  })
                  .then(response => response.json())
                  .then(data => console.log(data.message))
                  .catch(error => console.error('Error:', error));
              });
          });
      });
    </script>

    <h2>Use cases management</h2>
    <table class="wp-list-table widefat fixed striped" style="max-width: 720px;">
      <thead>
        <tr>
          <th scope="col" id="title" class="manage-column column-title column-primary">use case</th>
          <th scope="col" class="manage-column column-tag">tag</th>
          <th scope="col" class="manage-column column-tag">actions</th>
        </tr>
      </thead>
      <tbody>
        <?php

        $args = array(
          'category_name' => 'use-cases',
          'post_status'   => 'publish',
          'posts_per_page' => -1,
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) :
          while ($query->have_posts()) : $query->the_post();
            $post_id = get_the_ID();
            $custom_field_value = get_post_meta($post_id, 'usecase_tag', true);
            ?>
            <tr>
              <td class="title column-title has-row-actions column-primary">
                <strong><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></strong>
              </td>
              <td class="tag column-tag">
                <input type="text" class="custom-field-input" data-post-id="<?php echo esc_attr($post_id); ?>" value="<?php echo esc_attr($custom_field_value); ?>">
              </td>
              <td class="actions column-actions">
                <?php 
                $existing_tag = get_term_by('slug', $custom_field_value, 'post_tag');
                if (!$existing_tag) : ?>
                  <button class="button create-tag-button" data-post-id="<?php echo esc_attr($post_id); ?>">Create</button>
                <?php else : ?>
                  <span>Tag exists</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php
          endwhile;
          wp_reset_postdata();
        else :
          ?>
          <tr>
            <td colspan="2">No posts found in the "use-cases" category.</td>
          </tr>
          <?php
        endif;
        ?>
      </tbody>
    </table>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.custom-field-input').forEach(function (input) {
          input.addEventListener('input', function () {
            let postId = this.dataset.postId;
            let value = this.value;

            fetch(ajaxurl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({
                action: 'update_usecase_tag',
                post_id: postId,
                value: value,
                security: '<?php echo wp_create_nonce("update_usecase_tag_nonce"); ?>'
              })
            })
            .then(response => response.json())
            .then(data => console.log(data.message))
            .catch(error => console.error('Error:', error));
          });
        });
      });

      document.querySelectorAll('.create-tag-button').forEach(function (button) {
        button.addEventListener('click', function () {
          let postId = this.dataset.postId;
          let input = document.querySelector('.custom-field-input[data-post-id="' + postId + '"]');
          let value = input.value;

          fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'create_usecase_tag',
              post_id: postId,
              value: value,
              security: '<?php echo wp_create_nonce("create_usecase_tag_nonce"); ?>'
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Tag created successfully.');
              location.reload();
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => console.error('Error:', error));
        });
      });
    </script>
  </div>

  <?php
}

// Hook the function to the admin menu
add_action('admin_menu', 'create_admin_menu_entry');


// AJAX handler to update post tags
function toggle_post_tag() {
  // Verify nonce for security
  check_ajax_referer('toggle_post_tag_nonce', 'security');

  if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => 'Permission denied.']);
  }

  $post_id = intval($_POST['post_id']);
  $tag_id  = intval($_POST['tag_id']);
  $checked = intval($_POST['checked']);

  if (!$post_id || !$tag_id) {
      wp_send_json_error(['message' => 'Invalid data.']);
  }

  $tag = get_term($tag_id, 'post_tag');
  if (!$tag) {
      wp_send_json_error(['message' => 'Tag not found.']);
  }

  if ($checked) {
      wp_set_post_tags($post_id, $tag->slug, true); // Add tag
  } else {
      $current_tags = wp_get_post_tags($post_id, ['fields' => 'slugs']);
      $new_tags = array_diff($current_tags, [$tag->slug]);
      wp_set_post_tags($post_id, $new_tags, false); // Remove tag
  }

  // New functionality: Update related use case post
  if ($checked) {
      // Get the power station's custom field value
      $powerstation_tag = get_post_meta($post_id, 'powerstation_tag', true);

      // Find the related use case post
      $usecase_query = new WP_Query(array(
          'category_name' => 'use-cases',
          'meta_key'      => 'usecase_tag',
          'meta_value'    => $tag->slug,
          'post_status'   => 'publish',
          'posts_per_page' => 1,
      ));

      if ($usecase_query->have_posts()) {
          $usecase_query->the_post();
          $usecase_post_id = get_the_ID();

          // Update the use case post's custom field with the power station's custom field value
            wp_set_post_tags($usecase_post_id, $powerstation_tag, true);

          wp_reset_postdata();
      }
  }

  else {
      // Get the power station's custom field value
      $powerstation_tag = get_post_meta($post_id, 'powerstation_tag', true);

      // Find the related use case post
      $usecase_query = new WP_Query(array(
          'category_name' => 'use-cases',
          'meta_key'      => 'usecase_tag',
          'meta_value'    => $tag->slug,
          'post_status'   => 'publish',
          'posts_per_page' => 1,
      ));

      if ($usecase_query->have_posts()) {
          $usecase_query->the_post();
          $usecase_post_id = get_the_ID();

          // Remove the tag from the use case post's custom field
          $current_tags = wp_get_post_tags($usecase_post_id, ['fields' => 'slugs']);
          $new_tags = array_diff($current_tags, [$powerstation_tag]);
          wp_set_post_tags($usecase_post_id, $new_tags, false);
          wp_send_json_error(['message' => $new_tags]);

          wp_reset_postdata();
      }
      else {
          wp_send_json_error(['message' => 'Use case post not found.']);
      }
  }

  wp_send_json_success(['message' => 'Tag updated successfully.']);
}

function update_powerstation_tag() {
  // Verify nonce for security
  check_ajax_referer('update_powerstation_tag_nonce', 'security');

  if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => 'Permission denied.']);
  }

  $post_id = intval($_POST['post_id']);
  $value   = sanitize_text_field($_POST['value']);

  if (!$post_id) {
      wp_send_json_error(['message' => 'Invalid data.']);
  }

  update_post_meta($post_id, 'powerstation_tag', $value);

  wp_send_json_success(['message' => 'Custom field updated successfully.']);
}

function update_usecase_tag() {
  // Verify nonce for security
  check_ajax_referer('update_usecase_tag_nonce', 'security');

  if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => 'Permission denied.']);
  }

  $post_id = intval($_POST['post_id']);
  $value   = sanitize_text_field($_POST['value']);

  if (!$post_id) {
      wp_send_json_error(['message' => 'Invalid data.']);
  }

  update_post_meta($post_id, 'usecase_tag', $value);

  wp_send_json_success(['message' => 'Custom field updated successfully.']);
}

function create_usecase_tag() {
  // Verify nonce for security
  check_ajax_referer('create_usecase_tag_nonce', 'security');

  if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => 'Permission denied.']);
  }

  $post_id = intval($_POST['post_id']);
  $value   = sanitize_text_field($_POST['value']);

  if (!$post_id || !$value) {
      wp_send_json_error(['message' => 'Invalid data.']);
  }

  $existing_tag = get_term_by('slug', $value, 'post_tag');
  if ($existing_tag) {
      wp_send_json_error(['message' => 'Tag already exists.']);
  }

  $tag_id = wp_insert_term($value, 'post_tag');
  if (is_wp_error($tag_id)) {
      wp_send_json_error(['message' => 'Error creating tag.']);
  }

  update_post_meta($post_id, 'usecase_tag', $value);

  wp_send_json_success(['message' => 'Tag created successfully.']);
}



// Hook AJAX functions
add_action('wp_ajax_toggle_post_tag', 'toggle_post_tag');
add_action('wp_ajax_update_powerstation_tag', 'update_powerstation_tag');
add_action('wp_ajax_update_usecase_tag', 'update_usecase_tag');
add_action('wp_ajax_create_usecase_tag', 'create_usecase_tag');