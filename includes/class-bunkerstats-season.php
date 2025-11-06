<?php

class BunkerStats_Season {
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_seasons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function deactivate() {
        // No action needed for now
    }

    public static function create($name) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_seasons';
        $wpdb->insert($table, [
            'name' => $name
        ]);
        return $wpdb->insert_id;
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_seasons';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_seasons';
        return $wpdb->get_results("SELECT * FROM $table");
    }

    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_seasons';
        return $wpdb->update($table, $data, ['id' => $id]);
    }

    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_seasons';
        return $wpdb->delete($table, ['id' => $id]);
    }
}
