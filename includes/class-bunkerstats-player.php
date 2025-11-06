<?php

class BunkerStats_Player {
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_players';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            season_id BIGINT UNSIGNED NOT NULL,
            games_played INT DEFAULT 0,
            goals INT DEFAULT 0,
            points INT DEFAULT 0
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function deactivate() {
        // No action needed for now
    }

    public static function create($name, $season_id, $games_played = 0, $goals = 0, $points = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_players';
        $wpdb->insert($table, [
            'name' => $name,
            'season_id' => $season_id,
            'games_played' => $games_played,
            'goals' => $goals,
            'points' => $points
        ]);
        return $wpdb->insert_id;
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_players';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function get_all($season_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_players';
        if ($season_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE season_id = %d", $season_id));
        }
        return $wpdb->get_results("SELECT * FROM $table");
    }

    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_players';
        return $wpdb->update($table, $data, ['id' => $id]);
    }

    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_players';
        return $wpdb->delete($table, ['id' => $id]);
    }
}
