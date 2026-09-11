<?php
/*
Plugin Name: BunkerStats Prediction Plugin
Description: Allow readers to predict hockey player stats for a season.
Version: 1.2
Author: Andreas Galistel

Shortcodes:
  [bunkerstats_form id="FORM_ID"]      - Display the prediction form for a given form id.
  [bunkerstats_results id="FORM_ID"]   - Display the results table for a given form id.
    Optional: type="predicted" or type="final"

Admin:
  - Players: Add and list players.
  - Seasons: Add and list seasons.
  - Forms: Create forms, assign players and eliminator question.
  - Submissions: View all submissions and details.
*/

if (!defined('ABSPATH')) exit;

// Include model classes
require_once plugin_dir_path(__FILE__) . 'includes/class-bunkerstats-player.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bunkerstats-season.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bunkerstats-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bunkerstats-submission.php';

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'bunkerstats_activate');
register_deactivation_hook(__FILE__, 'bunkerstats_deactivate');

function bunkerstats_activate() {
    BunkerStats_Player::activate();
    BunkerStats_Season::activate();
    BunkerStats_Form::activate();
    BunkerStats_Submission::activate();
}

function bunkerstats_deactivate() {
    BunkerStats_Player::deactivate();
    BunkerStats_Season::deactivate();
    BunkerStats_Form::deactivate();
    BunkerStats_Submission::deactivate();
}

// ...existing code for plugin initialization...

if (is_admin()) {
    add_action('admin_menu', function() {
        add_menu_page(
            __('BunkerStats', 'bunkerstats'), __('BunkerStats', 'bunkerstats'), 'manage_options', 'bunkerstats', 'bunkerstats_admin_dashboard', 'dashicons-groups'
        );
        add_submenu_page(
            'bunkerstats', __('Players', 'bunkerstats'), __('Players', 'bunkerstats'), 'manage_options', 'bunkerstats_players', 'bunkerstats_admin_players'
        );
        add_submenu_page(
            'bunkerstats', __('Seasons', 'bunkerstats'), __('Seasons', 'bunkerstats'), 'manage_options', 'bunkerstats_seasons', 'bunkerstats_admin_seasons'
        );
        add_submenu_page(
            'bunkerstats', __('Forms', 'bunkerstats'), __('Forms', 'bunkerstats'), 'manage_options', 'bunkerstats_forms', 'bunkerstats_admin_forms'
        );
        add_submenu_page(
            'bunkerstats', __('Submissions', 'bunkerstats'), __('Submissions', 'bunkerstats'), 'manage_options', 'bunkerstats_submissions', 'bunkerstats_admin_submissions'
        );
        add_submenu_page(
            'bunkerstats', __('Statistics', 'bunkerstats'), __('Statistics', 'bunkerstats'), 'manage_options', 'bunkerstats_statistics', 'bunkerstats_admin_statistics'
        );
    });

    function bunkerstats_admin_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html__('BunkerStats Dashboard', 'bunkerstats') . '</h1>';
        echo '<p>' . esc_html__('Welcome to BunkerStats! Use the sections below to manage your hockey prediction game.', 'bunkerstats') . '</p>';
        echo '<ul style="font-size:1.1em;">';
        echo '<li><a href="' . admin_url('admin.php?page=bunkerstats_players') . '"><strong>' . esc_html__('Players', 'bunkerstats') . '</strong></a> &mdash; ' . esc_html__('Add, edit, and copy players between seasons.', 'bunkerstats') . '</li>';
        echo '<li><a href="' . admin_url('admin.php?page=bunkerstats_seasons') . '"><strong>' . esc_html__('Seasons', 'bunkerstats') . '</strong></a> &mdash; ' . esc_html__('Manage seasons for your prediction games.', 'bunkerstats') . '</li>';
        echo '<li><a href="' . admin_url('admin.php?page=bunkerstats_forms') . '"><strong>' . esc_html__('Forms', 'bunkerstats') . '</strong></a> &mdash; ' . esc_html__('Create and manage prediction forms for your readers.', 'bunkerstats') . '</li>';
        echo '<li><a href="' . admin_url('admin.php?page=bunkerstats_submissions') . '"><strong>' . esc_html__('Submissions', 'bunkerstats') . '</strong></a> &mdash; ' . esc_html__('View and review all user submissions.', 'bunkerstats') . '</li>';
        echo '<li><a href="' . admin_url('admin.php?page=bunkerstats_statistics') . '"><strong>' . esc_html__('Statistics', 'bunkerstats') . '</strong></a> &mdash; ' . esc_html__('View summary statistics for all submissions.', 'bunkerstats') . '</li>';
        echo '</ul>';
        echo '<p style="margin-top:2em;"><strong>' . esc_html__('Shortcodes:', 'bunkerstats') . '</strong></p>';
        echo '<ul>';
        echo '<li><code>[bunkerstats_form id="FORM_ID"]</code> - ' . esc_html__('Display a prediction form on any page or post.', 'bunkerstats') . '</li>';
        echo '<li><code>[bunkerstats_results id="FORM_ID"]</code> - ' . esc_html__('Show the results table for a form. Add', 'bunkerstats') . ' <code>type="final"</code> ' . esc_html__('for final results.', 'bunkerstats') . '</li>';
        echo '</ul>';
    }

    function bunkerstats_admin_players() {
        echo '<div class="wrap"><h1>' . esc_html__('Players', 'bunkerstats') . '</h1>';
        // Handle edit
        if (isset($_GET['edit']) && ($player = BunkerStats_Player::get(intval($_GET['edit'])))) {
            if (isset($_POST['bunkerstats_update_player']) && check_admin_referer('bunkerstats_update_player_nonce_' . $player->id)) {
                $name = sanitize_text_field($_POST['name']);
                $season_id = intval($_POST['season_id']);
                $games_played = max(0, intval($_POST['games_played']));
                $goals = max(0, intval($_POST['goals']));
                $points = max(0, intval($_POST['points']));
                if ($name && $season_id) {
                    BunkerStats_Player::update($player->id, [
                        'name' => $name,
                        'season_id' => $season_id,
                        'games_played' => $games_played,
                        'goals' => $goals,
                        'points' => $points
                    ]);
                    echo '<div class="updated"><p>' . esc_html__('Player updated.', 'bunkerstats') . '</p></div>';
                    // Refresh player data
                    $player = BunkerStats_Player::get($player->id);
                } else {
                    echo '<div class="error"><p>' . esc_html__('Invalid input.', 'bunkerstats') . '</p></div>';
                }
            }
            $seasons = BunkerStats_Season::get_all();
            echo '<form method="post">';
            wp_nonce_field('bunkerstats_update_player_nonce_' . $player->id);
            echo '<h2>' . esc_html__('Edit Player', 'bunkerstats') . '</h2>
                <p><label>' . esc_html__('Name:', 'bunkerstats') . '<br><input type="text" name="name" value="' . esc_attr($player->name) . '" required></label></p>
                <p><label>' . esc_html__('Season:', 'bunkerstats') . '<br>
                    <select name="season_id" required>
                        <option value="">' . esc_html__('Select Season', 'bunkerstats') . '</option>';
            foreach ($seasons as $season) {
                echo '<option value="' . esc_attr($season->id) . '"' . selected($player->season_id, $season->id, false) . '>' . esc_html($season->name) . '</option>';
            }
            echo '  </select>
                </label></p>
                <p><label>' . esc_html__('Games Played:', 'bunkerstats') . '<br><input type="number" name="games_played" value="' . esc_attr($player->games_played) . '" min="0"></label></p>
                <p><label>' . esc_html__('Goals:', 'bunkerstats') . '<br><input type="number" name="goals" value="' . esc_attr($player->goals) . '" min="0"></label></p>
                <p><label>' . esc_html__('Points:', 'bunkerstats') . '<br><input type="number" name="points" value="' . esc_attr($player->points) . '" min="0"></label></p>
                <p><input type="submit" name="bunkerstats_update_player" class="button button-primary" value="' . esc_attr__('Update Player', 'bunkerstats') . '"></p>
            </form>
            <p><a href="' . admin_url('admin.php?page=bunkerstats_players') . '">&laquo; ' . esc_html__('Back to Players', 'bunkerstats') . '</a></p>';
            echo '</div>';
            return;
        }

        // Handle add
        if (isset($_POST['bunkerstats_add_player']) && check_admin_referer('bunkerstats_add_player_nonce')) {
            $name = sanitize_text_field($_POST['name']);
            $season_id = intval($_POST['season_id']);
            $games_played = max(0, intval($_POST['games_played']));
            $goals = max(0, intval($_POST['goals']));
            $points = max(0, intval($_POST['points']));
            if ($name && $season_id) {
                BunkerStats_Player::create($name, $season_id, $games_played, $goals, $points);
                echo '<div class="updated"><p>' . esc_html__('Player added.', 'bunkerstats') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Invalid input.', 'bunkerstats') . '</p></div>';
            }
        }
        $seasons = BunkerStats_Season::get_all();
        echo '<form method="post">';
        wp_nonce_field('bunkerstats_add_player_nonce');
        echo '<h2>' . esc_html__('Add Player', 'bunkerstats') . '</h2>
            <p><input type="text" name="name" placeholder="' . esc_attr__('Name', 'bunkerstats') . '" required></p>
            <p>
                <select name="season_id" required>
                    <option value="">' . esc_html__('Select Season', 'bunkerstats') . '</option>';
        foreach ($seasons as $season) {
            echo '<option value="' . esc_attr($season->id) . '">' . esc_html($season->name) . '</option>';
        }
        echo '  </select>
            </p>
            <p><input type="number" name="games_played" placeholder="' . esc_attr__('Games Played', 'bunkerstats') . '" min="0"></p>
            <p><input type="number" name="goals" placeholder="' . esc_attr__('Goals', 'bunkerstats') . '" min="0"></p>
            <p><input type="number" name="points" placeholder="' . esc_attr__('Points', 'bunkerstats') . '" min="0"></p>
            <p><input type="submit" name="bunkerstats_add_player" class="button button-primary" value="' . esc_attr__('Add Player', 'bunkerstats') . '"></p>
        </form>';

        // Handle copy to new season (keep only one form and handler)
        if (isset($_POST['bunkerstats_copy_players']) && check_admin_referer('bunkerstats_copy_players_nonce')) {
            $selected_players = isset($_POST['copy_player_ids']) ? array_map('intval', $_POST['copy_player_ids']) : [];
            $target_season = isset($_POST['copy_target_season']) ? intval($_POST['copy_target_season']) : 0;
            if (!empty($selected_players) && $target_season) {
                foreach ($selected_players as $pid) {
                    $player = BunkerStats_Player::get($pid);
                    if ($player) {
                        BunkerStats_Player::create($player->name, $target_season, 0, 0, 0);
                    }
                }
                echo '<div class="updated"><p>' . esc_html__('Selected players copied to new season with empty stats.', 'bunkerstats') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Please select players and a target season.', 'bunkerstats') . '</p></div>';
            }
        }

        // Only one copy-to-new-season form (remove duplicate)
        echo '<form method="post" style="margin-bottom:1em;">';
        wp_nonce_field('bunkerstats_copy_players_nonce');
        echo '<h2>' . esc_html__('Copy Selected Players to New Season', 'bunkerstats') . '</h2>';
        echo '<p>
            <select name="copy_target_season" required>
                <option value="">' . esc_html__('Select Target Season', 'bunkerstats') . '</option>';
        foreach ($seasons as $season) {
            echo '<option value="' . esc_attr($season->id) . '">' . esc_html($season->name) . '</option>';
        }
        echo '</select>
            <input type="submit" name="bunkerstats_copy_players" class="button" value="' . esc_attr__('Copy to New Season', 'bunkerstats') . '">
        </p>';

        // Sorting logic
        $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'id';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'desc' : 'asc';
        $sortable = [
            'id' => 'ID',
            'name' => esc_html__('Name', 'bunkerstats'),
            'season' => esc_html__('Season', 'bunkerstats'),
            'games_played' => esc_html__('Games', 'bunkerstats'),
            'goals' => esc_html__('Goals', 'bunkerstats'),
            'points' => esc_html__('Points', 'bunkerstats')
        ];

        $players = BunkerStats_Player::get_all();

        // Prepare season lookup for sorting by season name
        $season_lookup = [];
        foreach (BunkerStats_Season::get_all() as $season) {
            $season_lookup[$season->id] = $season->name;
        }

        // Sort players array
        usort($players, function($a, $b) use ($sort, $order, $season_lookup) {
            $v1 = $v2 = null;
            switch ($sort) {
                case 'name':
                    $v1 = strtolower($a->name);
                    $v2 = strtolower($b->name);
                    break;
                case 'season':
                    $v1 = isset($season_lookup[$a->season_id]) ? strtolower($season_lookup[$a->season_id]) : '';
                    $v2 = isset($season_lookup[$b->season_id]) ? strtolower($season_lookup[$b->season_id]) : '';
                    break;
                case 'games_played':
                    $v1 = intval($a->games_played);
                    $v2 = intval($b->games_played);
                    break;
                case 'goals':
                    $v1 = intval($a->goals);
                    $v2 = intval($b->goals);
                    break;
                case 'points':
                    $v1 = intval($a->points);
                    $v2 = intval($b->points);
                    break;
                default:
                    $v1 = intval($a->id);
                    $v2 = intval($b->id);
            }
            if ($v1 == $v2) return 0;
            if ($order === 'asc') {
                return ($v1 < $v2) ? -1 : 1;
            } else {
                return ($v1 > $v2) ? -1 : 1;
            }
        });

        // Helper for sort links
        function bunkerstats_sort_link($label, $col, $current_sort, $current_order) {
            $order = ($current_sort === $col && $current_order === 'asc') ? 'desc' : 'asc';
            $arrow = '';
            if ($current_sort === $col) {
                $arrow = $current_order === 'asc' ? ' &uarr;' : ' &darr;';
            }
            $url = add_query_arg(['sort' => $col, 'order' => $order]);
            return '<a href="' . esc_url($url) . '">' . $label . $arrow . '</a>';
        }


        // Player table with sorting links
        echo '<h2>' . esc_html__('All Players', 'bunkerstats') . '</h2><table class="widefat"><thead><tr>
            <th><input type="checkbox" id="bunkerstats_check_all" onclick="jQuery(\'.bunkerstats_copy_checkbox\').prop(\'checked\', this.checked);"></th>
            <th>' . bunkerstats_sort_link('ID', 'id', $sort, $order) . '</th>
            <th>' . bunkerstats_sort_link(esc_html__('Name', 'bunkerstats'), 'name', $sort, $order) . '</th>
            <th>' . bunkerstats_sort_link(esc_html__('Season', 'bunkerstats'), 'season', $sort, $order) . '</th>
            <th>' . bunkerstats_sort_link(esc_html__('Games', 'bunkerstats'), 'games_played', $sort, $order) . '</th>
            <th>' . bunkerstats_sort_link(esc_html__('Goals', 'bunkerstats'), 'goals', $sort, $order) . '</th>
            <th>' . bunkerstats_sort_link(esc_html__('Points', 'bunkerstats'), 'points', $sort, $order) . '</th>
            <th>' . esc_html__('Actions', 'bunkerstats') . '</th></tr></thead><tbody>';
        foreach ($players as $player) {
            $season = isset($season_lookup[$player->season_id]) ? $season_lookup[$player->season_id] : '';
            echo '<tr>
                <td><input type="checkbox" class="bunkerstats_copy_checkbox" name="copy_player_ids[]" value="' . esc_attr($player->id) . '"></td>
                <td>' . esc_html($player->id) . '</td>
                <td>' . esc_html($player->name) . '</td>
                <td>' . esc_html($season) . '</td>
                <td>' . esc_html($player->games_played) . '</td>
                <td>' . esc_html($player->goals) . '</td>
                <td>' . esc_html($player->points) . '</td>
                <td><a href="' . admin_url('admin.php?page=bunkerstats_players&edit=' . $player->id) . '">' . esc_html__('Edit', 'bunkerstats') . '</a></td>
            </tr>';
        }
        echo '</tbody></table></form></div>';

        // Add a little JS for "check all"
        echo '<script>
        if (window.jQuery) {
            jQuery(document).ready(function() {
                jQuery("#bunkerstats_check_all").on("change", function() {
                    jQuery(".bunkerstats_copy_checkbox").prop("checked", this.checked);
                });
            });
        }
        </script>';
    }

    function bunkerstats_admin_seasons() {
        echo '<div class="wrap"><h1>' . esc_html__('Seasons', 'bunkerstats') . '</h1>';
        // Handle edit
        if (isset($_GET['edit']) && ($season = BunkerStats_Season::get(intval($_GET['edit'])))) {
            if (isset($_POST['bunkerstats_update_season']) && check_admin_referer('bunkerstats_update_season_nonce_' . $season->id)) {
                $name = sanitize_text_field($_POST['name']);
                if ($name) {
                    BunkerStats_Season::update($season->id, ['name' => $name]);
                    echo '<div class="updated"><p>' . esc_html__('Season updated.', 'bunkerstats') . '</p></div>';
                    $season = BunkerStats_Season::get($season->id);
                } else {
                    echo '<div class="error"><p>' . esc_html__('Invalid input.', 'bunkerstats') . '</p></div>';
                }
            }
            echo '<form method="post">';
            wp_nonce_field('bunkerstats_update_season_nonce_' . $season->id);
            echo '<h2>' . esc_html__('Edit Season', 'bunkerstats') . '</h2>
                <p><label>' . esc_html__('Season Name:', 'bunkerstats') . '<br><input type="text" name="name" value="' . esc_attr($season->name) . '" required></label></p>
                <p><input type="submit" name="bunkerstats_update_season" class="button button-primary" value="' . esc_attr__('Update Season', 'bunkerstats') . '"></p>
            </form>
            <p><a href="' . admin_url('admin.php?page=bunkerstats_seasons') . '">&laquo; ' . esc_html__('Back to Seasons', 'bunkerstats') . '</a></p>';
            echo '</div>';
            return;
        }

        // Handle add
        if (isset($_POST['bunkerstats_add_season']) && check_admin_referer('bunkerstats_add_season_nonce')) {
            $name = sanitize_text_field($_POST['name']);
            if ($name) {
                BunkerStats_Season::create($name);
                echo '<div class="updated"><p>' . esc_html__('Season added.', 'bunkerstats') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Invalid input.', 'bunkerstats') . '</p></div>';
            }
        }
        echo '<form method="post">';
        wp_nonce_field('bunkerstats_add_season_nonce');
        echo '<h2>' . esc_html__('Add Season', 'bunkerstats') . '</h2>
            <p><input type="text" name="name" placeholder="' . esc_attr__('Season Name', 'bunkerstats') . '" required></p>
            <p><input type="submit" name="bunkerstats_add_season" class="button button-primary" value="' . esc_attr__('Add Season', 'bunkerstats') . '"></p>
        </form>';

        // Add edit link to season table
        $seasons = BunkerStats_Season::get_all();
        echo '<h2>' . esc_html__('All Seasons', 'bunkerstats') . '</h2><table class="widefat"><thead><tr>
            <th>ID</th><th>' . esc_html__('Name', 'bunkerstats') . '</th><th>' . esc_html__('Actions', 'bunkerstats') . '</th></tr></thead><tbody>';
        foreach ($seasons as $season) {
            echo '<tr>
                <td>' . esc_html($season->id) . '</td>
                <td>' . esc_html($season->name) . '</td>
                <td><a href="' . admin_url('admin.php?page=bunkerstats_seasons&edit=' . $season->id) . '">' . esc_html__('Edit', 'bunkerstats') . '</a></td>
            </tr>';
        }
        echo '</tbody></table></div>';
    }

    function bunkerstats_admin_forms() {
        echo '<div class="wrap"><h1>' . esc_html__('Forms', 'bunkerstats') . '</h1>';

        if (isset($_GET['delete'])) {
            $form_id = intval($_GET['delete']);
            $form = BunkerStats_Form::get($form_id);
            if ($form && check_admin_referer('bunkerstats_delete_form_' . $form_id)) {
                foreach (BunkerStats_Submission::get_all($form_id) as $submission) {
                    BunkerStats_Submission::delete($submission->id);
                }
                BunkerStats_Form::delete($form_id);
                echo '<div class="updated"><p>' . esc_html__('Form deleted.', 'bunkerstats') . '</p></div>';
            } elseif (!$form) {
                echo '<div class="error"><p>' . esc_html__('Form not found.', 'bunkerstats') . '</p></div>';
            }
        }

        if (isset($_GET['edit'])) {
            $form_id = intval($_GET['edit']);
            $form = BunkerStats_Form::get($form_id);
            if (!$form) {
                echo '<div class="error"><p>' . esc_html__('Form not found.', 'bunkerstats') . '</p></div></div>';
                return;
            }

            $seasons = BunkerStats_Season::get_all();
            if (isset($_POST['bunkerstats_update_form']) && check_admin_referer('bunkerstats_update_form_nonce_' . $form_id)) {
                $name = sanitize_text_field(wp_unslash($_POST['name']));
                $season_id = intval($_POST['season_id']);
                $eliminator_question = sanitize_text_field(wp_unslash($_POST['eliminator_question']));
                $player_objs = BunkerStats_Player::get_all($season_id);
                $player_ids = array_map(function($player) { return $player->id; }, $player_objs);

                if ($name && $season_id && $eliminator_question && !empty($player_ids)) {
                    BunkerStats_Form::update($form_id, [
                        'name' => $name,
                        'season_id' => $season_id,
                        'eliminator_question' => $eliminator_question
                    ], $player_ids);
                    $form = BunkerStats_Form::get($form_id);
                    echo '<div class="updated"><p>' . esc_html__('Form updated.', 'bunkerstats') . '</p></div>';
                } else {
                    echo '<div class="error"><p>' . esc_html__('Invalid input or no players in selected season.', 'bunkerstats') . '</p></div>';
                }
            }

            echo '<form method="post">';
            wp_nonce_field('bunkerstats_update_form_nonce_' . $form_id);
            echo '<h2>' . esc_html__('Edit Form', 'bunkerstats') . '</h2>
                <p><input type="text" name="name" value="' . esc_attr($form->name) . '" placeholder="' . esc_attr__('Form Name', 'bunkerstats') . '" required></p>
                <p>
                    <select name="season_id" required>
                        <option value="">' . esc_html__('Select Season', 'bunkerstats') . '</option>';
            foreach ($seasons as $season) {
                echo '<option value="' . esc_attr($season->id) . '"' . selected($form->season_id, $season->id, false) . '>' . esc_html($season->name) . '</option>';
            }
            echo '  </select>
                </p>
                <p><input type="text" name="eliminator_question" value="' . esc_attr($form->eliminator_question) . '" placeholder="' . esc_attr__('Eliminator Question', 'bunkerstats') . '" required></p>
                <p><em>' . esc_html__('All players from the selected season will be included in the form.', 'bunkerstats') . '</em></p>
                <p><input type="submit" name="bunkerstats_update_form" class="button button-primary" value="' . esc_attr__('Update Form', 'bunkerstats') . '"></p>
            </form>
            <p><a href="' . esc_url(admin_url('admin.php?page=bunkerstats_forms')) . '">&laquo; ' . esc_html__('Back to Forms', 'bunkerstats') . '</a></p>
            </div>';
            return;
        }

        if (isset($_POST['bunkerstats_add_form']) && check_admin_referer('bunkerstats_add_form_nonce')) {
            $name = sanitize_text_field(wp_unslash($_POST['name']));
            $season_id = intval($_POST['season_id']);
            $eliminator_question = sanitize_text_field(wp_unslash($_POST['eliminator_question']));
            // Get all player IDs for the selected season
            $player_objs = BunkerStats_Player::get_all($season_id);
            $player_ids = array_map(function($p) { return $p->id; }, $player_objs);
            if ($name && $season_id && $eliminator_question && !empty($player_ids)) {
                BunkerStats_Form::create($name, $season_id, $eliminator_question, $player_ids);
                echo '<div class="updated"><p>' . esc_html__('Form created.', 'bunkerstats') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Invalid input or no players in selected season.', 'bunkerstats') . '</p></div>';
            }
        }
        $seasons = BunkerStats_Season::get_all();
        echo '<form method="post">';
        wp_nonce_field('bunkerstats_add_form_nonce');
        echo '<h2>' . esc_html__('Create Form', 'bunkerstats') . '</h2>
            <p><input type="text" name="name" placeholder="' . esc_attr__('Form Name', 'bunkerstats') . '" required></p>
            <p>
                <select name="season_id" required>
                    <option value="">' . esc_html__('Select Season', 'bunkerstats') . '</option>';
        foreach ($seasons as $season) {
            echo '<option value="' . esc_attr($season->id) . '">' . esc_html($season->name) . '</option>';
        }
        echo '  </select>
            </p>
            <p><input type="text" name="eliminator_question" placeholder="' . esc_attr__('Eliminator Question', 'bunkerstats') . '" required></p>
            <p><em>' . esc_html__('All players from the selected season will be included in the form.', 'bunkerstats') . '</em></p>
            <p><input type="submit" name="bunkerstats_add_form" class="button button-primary" value="' . esc_attr__('Create Form', 'bunkerstats') . '"></p>
        </form>';

        $forms = BunkerStats_Form::get_all();
        echo '<h2>' . esc_html__('All Forms', 'bunkerstats') . '</h2><table class="widefat"><thead><tr>
            <th>ID</th><th>' . esc_html__('Name', 'bunkerstats') . '</th><th>' . esc_html__('Season', 'bunkerstats') . '</th><th>' . esc_html__('Eliminator Question', 'bunkerstats') . '</th><th>' . esc_html__('Players', 'bunkerstats') . '</th><th>' . esc_html__('Actions', 'bunkerstats') . '</th></tr></thead><tbody>';
        foreach ($forms as $form) {
            $season = BunkerStats_Season::get($form->season_id);
            $form_obj = BunkerStats_Form::get($form->id);
            $player_names = [];
            if (!empty($form_obj->player_ids)) {
                foreach ($form_obj->player_ids as $pid) {
                    $p = BunkerStats_Player::get($pid);
                    if ($p) $player_names[] = $p->name;
                }
            }
            echo '<tr>
                <td>' . esc_html($form->id) . '</td>
                <td>' . esc_html($form->name) . '</td>
                <td>' . esc_html($season ? $season->name : '') . '</td>
                <td>' . esc_html($form->eliminator_question) . '</td>
                <td>' . esc_html(implode(', ', $player_names)) . '</td>
                <td><a href="' . esc_url(admin_url('admin.php?page=bunkerstats_forms&edit=' . $form->id)) . '">' . esc_html__('Edit', 'bunkerstats') . '</a> |
                    <a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=bunkerstats_forms&delete=' . $form->id), 'bunkerstats_delete_form_' . $form->id)) . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to delete this form and all submissions?', 'bunkerstats')) . '\');">' . esc_html__('Delete', 'bunkerstats') . '</a></td>
            </tr>';
        }
        echo '</tbody></table></div>';
    }

    function bunkerstats_admin_submissions() {
        echo '<div class="wrap"><h1>' . esc_html__('Submissions', 'bunkerstats') . '</h1>';
        $forms = BunkerStats_Form::get_all();
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        echo '<form method="get" style="margin-bottom:1em;">
            <input type="hidden" name="page" value="bunkerstats_submissions">
            <select name="form_id" onchange="this.form.submit()">
                <option value="">' . esc_html__('Select Form', 'bunkerstats') . '</option>';
        foreach ($forms as $form) {
            echo '<option value="' . esc_attr($form->id) . '"' . selected($form_id, $form->id, false) . '>' . esc_html($form->name) . '</option>';
        }
        echo '</select></form>';

        // Handle edit submission
        if (isset($_GET['edit'])) {
            $submission_id = intval($_GET['edit']);
            $submission = BunkerStats_Submission::get($submission_id);
            $form = BunkerStats_Form::get($submission->form_id);
            if (!$submission || !$form) {
                echo '<div class="error"><p>' . esc_html__('Submission not found.', 'bunkerstats') . '</p></div>';
                echo '</div>';
                return;
            }
            if (isset($_POST['bunkerstats_update_submission']) && check_admin_referer('bunkerstats_update_submission_' . $submission_id)) {
                $alias = sanitize_text_field($_POST['alias']);
                $email = sanitize_email($_POST['email']);
                $eliminator_answer = sanitize_text_field($_POST['eliminator_answer']);
                $player_guesses = [];
                $valid = $alias && is_email($email) && $eliminator_answer;
                foreach ($form->player_ids as $pid) {
                    $goals = isset($_POST['goals'][$pid]) ? max(0, intval($_POST['goals'][$pid])) : 0;
                    $points = isset($_POST['points'][$pid]) ? max(0, intval($_POST['points'][$pid])) : 0;
                    $player_guesses[] = [
                        'player_id' => $pid,
                        'guessed_goals' => $goals,
                        'guessed_points' => $points
                    ];
                    if ($goals < 0 || $points < 0) $valid = false;
                }
                if ($valid) {
                    // Update submission (assumes BunkerStats_Submission::update exists)
                    BunkerStats_Submission::update($submission_id, [
                        'alias' => $alias,
                        'email' => $email,
                        'eliminator_answer' => $eliminator_answer,
                        'player_guesses' => $player_guesses
                    ]);
                    echo '<div class="updated"><p>' . esc_html__('Submission updated.', 'bunkerstats') . '</p></div>';
                    // Refresh submission
                    $submission = BunkerStats_Submission::get($submission_id);
                } else {
                    echo '<div class="error"><p>' . esc_html__('Invalid input. Please check your entries.', 'bunkerstats') . '</p></div>';
                }
            }
            echo '<form method="post">';
            wp_nonce_field('bunkerstats_update_submission_' . $submission_id);
            echo '<h3>' . esc_html__('Edit Submission', 'bunkerstats') . '</h3>';
            echo '<p><label>' . esc_html__('Alias:', 'bunkerstats') . '<br><input type="text" name="alias" value="' . esc_attr($submission->alias) . '" required></label></p>';
            echo '<p><label>' . esc_html__('Email:', 'bunkerstats') . '<br><input type="email" name="email" value="' . esc_attr($submission->email) . '" required></label></p>';
            echo '<p><label>' . esc_html__('Eliminator Answer:', 'bunkerstats') . '<br><input type="text" name="eliminator_answer" value="' . esc_attr($submission->eliminator_answer) . '" required></label></p>';
            echo '<table class="widefat"><thead><tr><th>' . esc_html__('Player', 'bunkerstats') . '</th><th>' . esc_html__('Guessed Goals', 'bunkerstats') . '</th><th>' . esc_html__('Guessed Points', 'bunkerstats') . '</th></tr></thead><tbody>';
            $guesses_by_pid = [];
            foreach ($submission->player_guesses as $guess) {
                $guesses_by_pid[$guess->player_id] = $guess;
            }
            foreach ($form->player_ids as $pid) {
                $player = BunkerStats_Player::get($pid);
                $guess = isset($guesses_by_pid[$pid]) ? $guesses_by_pid[$pid] : (object)['guessed_goals'=>0,'guessed_points'=>0];
                echo '<tr>
                    <td>' . esc_html($player ? $player->name : '') . '</td>
                    <td><input type="number" name="goals[' . esc_attr($pid) . ']" value="' . esc_attr($guess->guessed_goals) . '" min="0"></td>
                    <td><input type="number" name="points[' . esc_attr($pid) . ']" value="' . esc_attr($guess->guessed_points) . '" min="0"></td>
                </tr>';
            }
            echo '</tbody></table>';
            echo '<p><input type="submit" name="bunkerstats_update_submission" class="button button-primary" value="' . esc_attr__('Update Submission', 'bunkerstats') . '"></p>';
            echo '</form>';
            echo '<p><a href="' . admin_url('admin.php?page=bunkerstats_submissions&form_id=' . $form->id) . '">&laquo; ' . esc_html__('Back to Submissions', 'bunkerstats') . '</a></p>';
            echo '</div>';
            return;
        }

        // ...existing code for listing submissions and viewing details...
        // Add edit link to each submission row:
        // <td><a href="' . admin_url('admin.php?page=bunkerstats_submissions&form_id=' . $form_id . '&edit=' . $sub->id) . '">' . esc_html__('Edit', 'bunkerstats') . '</a></td>
        // ...existing code...
        if ($form_id) {
            $submissions = BunkerStats_Submission::get_all($form_id);
            echo '<h2>' . esc_html__('Submissions for Form:', 'bunkerstats') . ' ' . esc_html(BunkerStats_Form::get($form_id)->name) . '</h2>';
            echo '<table class="widefat"><thead><tr>
                <th>ID</th><th>' . esc_html__('Alias', 'bunkerstats') . '</th><th>' . esc_html__('Email', 'bunkerstats') . '</th><th>' . esc_html__('Eliminator Answer', 'bunkerstats') . '</th><th>' . esc_html__('Submitted At', 'bunkerstats') . '</th><th>' . esc_html__('View', 'bunkerstats') . '</th><th>' . esc_html__('Edit', 'bunkerstats') . '</th></tr></thead><tbody>';
            foreach ($submissions as $sub) {
                echo '<tr>
                    <td>' . esc_html($sub->id) . '</td>
                    <td>' . esc_html($sub->alias) . '</td>
                    <td>' . esc_html($sub->email) . '</td>
                    <td>' . esc_html($sub->eliminator_answer) . '</td>
                    <td>' . esc_html($sub->submitted_at) . '</td>
                    <td><a href="' . admin_url('admin.php?page=bunkerstats_submissions&form_id=' . $form_id . '&view=' . $sub->id) . '">' . esc_html__('View', 'bunkerstats') . '</a></td>
                    <td><a href="' . admin_url('admin.php?page=bunkerstats_submissions&form_id=' . $form_id . '&edit=' . $sub->id) . '">' . esc_html__('Edit', 'bunkerstats') . '</a></td>
                </tr>';
            }
            echo '</tbody></table>';
            // ...existing code for view details...
        }
        // ...existing code...
    }

    function bunkerstats_admin_statistics() {
        echo '<div class="wrap"><h1>' . esc_html__('BunkerStats Statistics', 'bunkerstats') . '</h1>';

        $forms = BunkerStats_Form::get_all();
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;

        // Sorting logic for statistics
        $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'name';
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'desc' : 'asc';
        $sortable = [
            'name' => esc_html__('Player', 'bunkerstats'),
            'avg' => esc_html__('Average', 'bunkerstats'),
            'var' => esc_html__('Variance', 'bunkerstats'),
            'min' => esc_html__('Min', 'bunkerstats'),
            'max' => esc_html__('Max', 'bunkerstats'),
            'spread' => esc_html__('Spread', 'bunkerstats'),
            'count' => esc_html__('Count', 'bunkerstats')
        ];

        // Helper for sort links
        function bunkerstats_stats_sort_link($label, $col, $current_sort, $current_order) {
            $order = ($current_sort === $col && $current_order === 'asc') ? 'desc' : 'asc';
            $arrow = '';
            if ($current_sort === $col) {
                $arrow = $current_order === 'asc' ? ' &uarr;' : ' &darr;';
            }
            $url = add_query_arg(['sort' => $col, 'order' => $order]);
            // preserve form_id in url
            if (isset($_GET['form_id'])) {
                $url = add_query_arg(['form_id' => intval($_GET['form_id'])], $url);
            }
            return '<a href="' . esc_url($url) . '">' . $label . $arrow . '</a>';
        }

        // Form selector
        echo '<form method="get" style="margin-bottom:1em;">
            <input type="hidden" name="page" value="bunkerstats_statistics">
            <select name="form_id" onchange="this.form.submit()">
                <option value="">' . esc_html__('Select Form', 'bunkerstats') . '</option>';
        foreach ($forms as $form) {
            echo '<option value="' . esc_attr($form->id) . '"' . selected($form_id, $form->id, false) . '>' . esc_html($form->name) . '</option>';
        }
        echo '</select></form>';

        if (!$form_id) {
            echo '<p>' . esc_html__('Select a form to view statistics.', 'bunkerstats') . '</p></div>';
            return;
        }

        $form = BunkerStats_Form::get($form_id);
        if (!$form) {
            echo '<p>' . esc_html__('Form not found.', 'bunkerstats') . '</p></div>';
            return;
        }

        $players = [];
        foreach ($form->player_ids as $pid) {
            $players[$pid] = BunkerStats_Player::get($pid);
        }

        $submissions = BunkerStats_Submission::get_all($form_id);
        if (!$submissions) {
            echo '<p>' . esc_html__('No submissions yet.', 'bunkerstats') . '</p></div>';
            return;
        }

        // Collect guesses for each player
        $player_stats = [];
        foreach ($players as $pid => $player) {
            $guesses = [];
            foreach ($submissions as $submission) {
                $submission = BunkerStats_Submission::get($submission->id);
                foreach ($submission->player_guesses as $guess) {
                    if ($guess->player_id == $pid) {
                        $guesses[] = intval($guess->guessed_points);
                    }
                }
            }
            if (count($guesses) > 0) {
                $avg = array_sum($guesses) / count($guesses);
                $var = count($guesses) > 1 ? array_sum(array_map(function($x) use ($avg) { return pow($x - $avg, 2); }, $guesses)) / (count($guesses) - 1) : 0;
                $min = min($guesses);
                $max = max($guesses);
                $spread = $max - $min;
                $player_stats[] = [
                    'id' => $pid,
                    'name' => $player->name,
                    'avg' => $avg,
                    'var' => $var,
                    'min' => $min,
                    'max' => $max,
                    'spread' => $spread,
                    'count' => count($guesses)
                ];
            }
        }

        // Sort player_stats by selected column
        usort($player_stats, function($a, $b) use ($sort, $order) {
            $v1 = $a[$sort];
            $v2 = $b[$sort];
            if ($sort === 'name') {
                $v1 = strtolower($v1);
                $v2 = strtolower($v2);
            }
            if ($v1 == $v2) return 0;
            if ($order === 'asc') {
                return ($v1 < $v2) ? -1 : 1;
            } else {
                return ($v1 > $v2) ? -1 : 1;
            }
        });

        // Top 5 biggest spreads
        $spread_sorted = $player_stats;
        usort($spread_sorted, function($a, $b) { return $b['spread'] <=> $a['spread']; });

        // Table: All players with stats (with sortable headers)
        echo '<h2>' . esc_html__('Player Prediction Statistics', 'bunkerstats') . '</h2>';
        echo '<table class="widefat"><thead><tr>';
        foreach ($sortable as $col => $label) {
            echo '<th>' . bunkerstats_stats_sort_link($label, $col, $sort, $order) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($player_stats as $stat) {
            echo '<tr>
                <td>' . esc_html($stat['name']) . '</td>
                <td>' . number_format($stat['avg'], 2) . '</td>
                <td>' . number_format($stat['var'], 2) . '</td>
                <td>' . esc_html($stat['min']) . '</td>
                <td>' . esc_html($stat['max']) . '</td>
                <td>' . esc_html($stat['spread']) . '</td>
                <td>' . esc_html($stat['count']) . '</td>
            </tr>';
        }
        echo '</tbody></table>';

        // Top 5 biggest spreads
        echo '<h2>' . esc_html__('Players with Biggest Spread', 'bunkerstats') . '</h2>';
        echo '<table class="widefat"><thead><tr>
            <th>' . esc_html__('Player', 'bunkerstats') . '</th>
            <th>' . esc_html__('Spread', 'bunkerstats') . '</th>
            <th>' . esc_html__('Min', 'bunkerstats') . '</th>
            <th>' . esc_html__('Max', 'bunkerstats') . '</th>
        </tr></thead><tbody>';
        foreach (array_slice($spread_sorted, 0, 5) as $stat) {
            echo '<tr>
                <td>' . esc_html($stat['name']) . '</td>
                <td>' . esc_html($stat['spread']) . '</td>
                <td>' . esc_html($stat['min']) . '</td>
                <td>' . esc_html($stat['max']) . '</td>
            </tr>';
        }
        echo '</tbody></table>';

        // Top 5 smallest spreads (with at least 2 submissions)
        $smallest_spread = array_filter($spread_sorted, function($stat) { return $stat['count'] > 1; });
        usort($smallest_spread, function($a, $b) { return $a['spread'] <=> $b['spread']; });
        echo '<h2>' . esc_html__('Players with Smallest Spread (min 2 guesses)', 'bunkerstats') . '</h2>';
        echo '<table class="widefat"><thead><tr>
            <th>' . esc_html__('Player', 'bunkerstats') . '</th>
            <th>' . esc_html__('Spread', 'bunkerstats') . '</th>
            <th>' . esc_html__('Min', 'bunkerstats') . '</th>
            <th>' . esc_html__('Max', 'bunkerstats') . '</th>
        </tr></thead><tbody>';
        foreach (array_slice($smallest_spread, 0, 5) as $stat) {
            echo '<tr>
                <td>' . esc_html($stat['name']) . '</td>
                <td>' . esc_html($stat['spread']) . '</td>
                <td>' . esc_html($stat['min']) . '</td>
                <td>' . esc_html($stat['max']) . '</td>
            </tr>';
        }
        echo '</tbody></table>';

        echo '</div>';
    }
}

// Uninstall cleanup
// DO NOT use a closure here! WordPress does not allow closures for uninstall hooks.
// Instead, create a separate uninstall.php file for uninstall logic.
register_uninstall_hook(__FILE__, 'bunkerstats_uninstall');

function bunkerstats_uninstall() {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS
        {$wpdb->prefix}bunkerstats_players,
        {$wpdb->prefix}bunkerstats_seasons,
        {$wpdb->prefix}bunkerstats_forms,
        {$wpdb->prefix}bunkerstats_form_players,
        {$wpdb->prefix}bunkerstats_submissions,
        {$wpdb->prefix}bunkerstats_submission_players
    ");
};

// Submission form shortcode
add_shortcode('bunkerstats_form', function($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $form_id = intval($atts['id']);
    if (!$form_id) return '<p>' . esc_html__('No form specified.', 'bunkerstats') . '</p>';

    $form = BunkerStats_Form::get($form_id);
    if (!$form) return '<p>' . esc_html__('Form not found.', 'bunkerstats') . '</p>';

    $output = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bunkerstats_submit_form']) && intval($_POST['form_id']) === $form_id) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bunkerstats_submit_form_' . $form_id)) {
            $output .= '<div class="error"><p>' . esc_html__('Security check failed. Please try again.', 'bunkerstats') . '</p></div>';
        } else {
            $alias = sanitize_text_field($_POST['alias']);
            $email = sanitize_email($_POST['email']);
            $eliminator_answer = sanitize_text_field($_POST['eliminator_answer']);
            $player_guesses = [];
            $valid = $alias && is_email($email) && $eliminator_answer;
            foreach ($form->player_ids as $pid) {
                $goals = isset($_POST['goals'][$pid]) ? max(0, intval($_POST['goals'][$pid])) : 0;
                $points = isset($_POST['points'][$pid]) ? max(0, intval($_POST['points'][$pid])) : 0;
                $player_guesses[] = [
                    'player_id' => $pid,
                    'guessed_goals' => $goals,
                    'guessed_points' => $points
                ];
                if ($goals < 0 || $points < 0) $valid = false;
            }
            if ($valid) {
                BunkerStats_Submission::create($form_id, $alias, $email, $eliminator_answer, $player_guesses);
                $output .= '<div class="updated"><p>' . esc_html__('Thank you for your submission!', 'bunkerstats') . '</p></div>';
            } else {
                $output .= '<div class="error"><p>' . esc_html__('Invalid input. Please check your entries.', 'bunkerstats') . '</p></div>';
            }
        }
    }

    $output .= '<form method="post" class="bunkerstats-form">';
    $output .= wp_nonce_field('bunkerstats_submit_form_' . $form_id, '_wpnonce', true, false);
    $output .= '<input type="hidden" name="form_id" value="' . esc_attr($form_id) . '">';
    $output .= '<p><label>' . esc_html__('Name (alias):', 'bunkerstats') . '<br><input type="text" name="alias" required></label></p>';
    $output .= '<p><label>' . esc_html__('Email:', 'bunkerstats') . '<br><input type="email" name="email" required></label></p>';
    $output .= '<table class="widefat"><thead><tr><th>' . esc_html__('Player', 'bunkerstats') . '</th><th>' . esc_html__('Goals', 'bunkerstats') . '</th><th>' . esc_html__('Points', 'bunkerstats') . '</th></tr></thead><tbody>';
    $form_players = [];
    foreach ($form->player_ids as $pid) {
        $player = BunkerStats_Player::get($pid);
        if ($player) {
            $form_players[] = $player;
        }
    }
    usort($form_players, function($player_a, $player_b) {
        return strcasecmp($player_a->name, $player_b->name);
    });
    foreach ($form_players as $player) {
        if ($player) {
            $output .= '<tr>
                <td>' . esc_html($player->name) . '</td>
                <td><input type="number" name="goals[' . esc_attr($player->id) . ']" min="0" required></td>
                <td><input type="number" name="points[' . esc_attr($player->id) . ']" min="0" required></td>
            </tr>';
        }
    }
    $output .= '</tbody></table>';
    $output .= '<p><label>' . esc_html__('Eliminator Question:', 'bunkerstats') . ' ' . esc_html($form->eliminator_question) . '<br><input type="text" name="eliminator_answer" required></label></p>';
    $output .= '<p><input type="submit" name="bunkerstats_submit_form" class="button button-primary" value="' . esc_attr__('Submit', 'bunkerstats') . '"></p>';
    $output .= '</form>';

    return $output;
});

// Result view shortcode
add_shortcode('bunkerstats_results', function($atts) {
    $atts = shortcode_atts(['id' => 0, 'type' => 'predicted'], $atts);
    $form_id = intval($atts['id']);
    $type = $atts['type']; // 'predicted' or 'final'

    if (empty($atts['id']) || !$form_id) return false;
    $form = BunkerStats_Form::get($form_id);
    if (!$form) return false;
    $players = [];
    foreach ($form->player_ids as $pid) {
        $players[$pid] = BunkerStats_Player::get($pid);
    }
    $submissions = BunkerStats_Submission::get_all($form_id);
    if (!$submissions) return false;

    // Scoring function
    function bunkerstats_calc_points($estimated) {
        $diff = $estimated;
        if ($diff == 0) return 10;
        if ($diff == 1) return 7;
        if ($diff == 2) return 5;
        if ($diff == 3) return 3;
        if ($diff == 4) return 1;
        return 0;
    }

    // Table header (no player columns)
    $output = '<table class="widefat"><thead><tr><th>' . esc_html__('Alias', 'bunkerstats') . '</th><th>' . esc_html__('Total Score', 'bunkerstats') . '</th></tr></thead><tbody>';

    // Calculate total scores for each submission
    $results = [];
    foreach ($submissions as $submission) {
        $submission = BunkerStats_Submission::get($submission->id); // get player_guesses
        $total = 0;
        foreach ($submission->player_guesses as $guess) {
            $player = $players[$guess->player_id];
            if ($type === 'predicted') {
                $current_points = intval($player->points);
                $current_games = max(1, intval($player->games_played));
                $projected_points = round($current_points / ($current_games / 52), 0);
                $diff = abs(intval($guess->guessed_points) - $projected_points);
            } else {
                $diff = abs(intval($guess->guessed_points) - intval($player->points));
            }
            $score = bunkerstats_calc_points($diff);
            $total += $score;
        }
        $results[] = [
            'alias' => $submission->alias,
            'total' => $total
        ];
    }

    // Sort results descending by total points
    usort($results, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    // Output rows
    foreach ($results as $row) {
        $output .= '<tr><td>' . esc_html($row['alias']) . '</td><td>' . esc_html($row['total']) . '</td></tr>';
    }
    $output .= '</tbody></table>';
    return $output;
});

add_filter('the_content', 'bunkerstats_disable_autop_strong', 0);

function bunkerstats_disable_autop_strong($content) {
    if (has_shortcode($content, 'bunkerstats_results')) {

        // REMOVE autop completely for this render cycle
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'shortcode_unautop');

        // Ensure shortcodes run immediately
        $content = do_shortcode($content);
    }
    return $content;
}

add_action('plugins_loaded', function() {
    load_plugin_textdomain('bunkerstats', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
