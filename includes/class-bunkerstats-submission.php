<?php

class BunkerStats_Submission {
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_submissions';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id BIGINT UNSIGNED NOT NULL,
            alias VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            eliminator_answer VARCHAR(255) DEFAULT NULL,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Table for guesses per player
        $table2 = $wpdb->prefix . 'bunkerstats_submission_players';
        $sql2 = "CREATE TABLE $table2 (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            submission_id BIGINT UNSIGNED NOT NULL,
            player_id BIGINT UNSIGNED NOT NULL,
            guessed_goals INT DEFAULT 0,
            guessed_points INT DEFAULT 0
        ) $charset_collate;";
        dbDelta($sql2);
    }

    public static function deactivate() {
        // No action needed for now
    }

    public static function create($form_id, $alias, $email, $eliminator_answer, $player_guesses = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_submissions';
        $wpdb->insert($table, [
            'form_id' => $form_id,
            'alias' => $alias,
            'email' => $email,
            'eliminator_answer' => $eliminator_answer
        ]);
        $submission_id = $wpdb->insert_id;

        // Insert player guesses
        $table2 = $wpdb->prefix . 'bunkerstats_submission_players';
        foreach ($player_guesses as $guess) {
            $wpdb->insert($table2, [
                'submission_id' => $submission_id,
                'player_id' => $guess['player_id'],
                'guessed_goals' => $guess['guessed_goals'],
                'guessed_points' => $guess['guessed_points']
            ]);
        }
        return $submission_id;
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_submissions';
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($submission) {
            $table2 = $wpdb->prefix . 'bunkerstats_submission_players';
            $submission->player_guesses = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table2 WHERE submission_id = %d", $id
            ));
        }
        return $submission;
    }

    public static function get_all($form_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_submissions';
        if ($form_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE form_id = %d", $form_id));
        }
        return $wpdb->get_results("SELECT * FROM $table");
    }

    public static function update($id, $data, $player_guesses = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_submissions';
        $wpdb->update($table, $data, ['id' => $id]);
        if ($player_guesses !== null) {
            $table2 = $wpdb->prefix . 'bunkerstats_submission_players';
            foreach ($player_guesses as $guess) {
                $wpdb->update($table2, [
                    'guessed_goals' => $guess['guessed_goals'],
                    'guessed_points' => $guess['guessed_points']
                ], [
                    'submission_id' => $id,
                    'player_id' => $guess['player_id']
                ]);
            }
        }
    }

    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bunkerstats_submissions';
        $table2 = $wpdb->prefix . 'bunkerstats_submission_players';
        $wpdb->delete($table2, ['submission_id' => $id]);
        return $wpdb->delete($table, ['id' => $id]);
    }
}
