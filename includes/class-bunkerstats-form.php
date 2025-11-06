<?php

class BunkerStats_Form {
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_forms';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            season_id BIGINT UNSIGNED NOT NULL,
            eliminator_question VARCHAR(255) DEFAULT NULL
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Table for form-player rows
        $table2 = $wpdb->prefix . 'bunkerstats_form_players';
        $sql2 = "CREATE TABLE $table2 (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id BIGINT UNSIGNED NOT NULL,
            player_id BIGINT UNSIGNED NOT NULL
        ) $charset_collate;";
        dbDelta($sql2);
    }

    public static function deactivate() {
        // No action needed for now
    }

    public static function create($name, $season_id, $eliminator_question, $player_ids = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_forms';
        $wpdb->insert($table, [
            'name' => $name,
            'season_id' => $season_id,
            'eliminator_question' => $eliminator_question
        ]);
        $form_id = $wpdb->insert_id;

        // Insert player rows for this form
        $table2 = $wpdb->prefix . 'bunkerstats_form_players';
        foreach ($player_ids as $player_id) {
            $wpdb->insert($table2, [
                'form_id' => $form_id,
                'player_id' => $player_id
            ]);
        }
        return $form_id;
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($form) {
            $table2 = $wpdb->prefix . 'bunkerstats_form_players';
            $form->player_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT player_id FROM $table2 WHERE form_id = %d", $id
            ));
        }
        return $form;
    }

    public static function get_all($season_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_forms';
        if ($season_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE season_id = %d", $season_id));
        }
        return $wpdb->get_results("SELECT * FROM $table");
    }

    public static function update($id, $data, $player_ids = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_forms';
        $wpdb->update($table, $data, ['id' => $id]);
        if ($player_ids !== null) {
            $table2 = $wpdb->prefix . 'bunkerstats_form_players';
            $wpdb->delete($table2, ['form_id' => $id]);
            foreach ($player_ids as $player_id) {
                $wpdb->insert($table2, [
                    'form_id' => $id,
                    'player_id' => $player_id
                ]);
            }
        }
    }

    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_forms';
        $table2 = $wpdb->prefix . 'bunkerstats_form_players';
        $wpdb->delete($table2, ['form_id' => $id]);
        return $wpdb->delete($table, ['id' => $id]);
    }
}
