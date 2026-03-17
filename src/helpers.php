<?php

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function format_ts(string $utc): string {
    $dt = new DateTime($utc, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
    return $dt->format('M j, Y g:i A');
}

function format_date(?string $date): string {
    if (!$date) return '—';
    return date('M j, Y', strtotime($date));
}

function generate_markdown_export(PDO $db): string {
    $lines = [];
    $lines[] = '# Project Tracker Export';
    $lines[] = 'Generated: ' . date('Y-m-d');
    $lines[] = '';

    $projects = $db->query(
        "SELECT p.*, sq.name AS squadron_name, poc.name AS poc_name, s.name AS status_name
         FROM projects p
         LEFT JOIN squadrons sq ON sq.id = p.squadron_id
         LEFT JOIN pocs poc ON poc.id = p.poc_id
         LEFT JOIN statuses s ON s.id = p.status_id
         ORDER BY p.start_date ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projects as $p) {
        $lines[] = '---';
        $lines[] = '';
        $name = $p['project_name'] !== '' ? ' — ' . $p['project_name'] : '';
        $lines[] = '# ' . $p['project_number'] . $name;
        $lines[] = '';
        $lines[] = '**Status:** ' . ($p['status_name'] ?? '—');
        $lines[] = '**Squadron:** ' . ($p['squadron_name'] ?? '—') . ' | **POC:** ' . ($p['poc_name'] ?? '—');
        $lines[] = '**Start:** ' . format_date($p['start_date']) . ' | **Completion:** ' . format_date($p['completion_date']);

        // Software tags
        $tags_stmt = $db->prepare(
            "SELECT st.name FROM software_tags st
             JOIN project_software_tags pst ON pst.tag_id = st.id
             WHERE pst.project_id = ?
             ORDER BY st.name"
        );
        $tags_stmt->execute([$p['id']]);
        $tags = array_column($tags_stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        // Deployment targets
        $targets_stmt = $db->prepare(
            "SELECT dt.name FROM deployment_targets dt
             JOIN project_deployment_targets pdt ON pdt.target_id = dt.id
             WHERE pdt.project_id = ?
             ORDER BY dt.name"
        );
        $targets_stmt->execute([$p['id']]);
        $targets = array_column($targets_stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        $lines[] = '**Software:** ' . (count($tags) ? implode(', ', $tags) : '—')
                 . ' | **Targets:** ' . (count($targets) ? implode(', ', $targets) : '—');
        $lines[] = '';
        $lines[] = '## Description';
        $lines[] = $p['description'];
        $lines[] = '';

        // Notes
        $notes_stmt = $db->prepare(
            "SELECT * FROM notes WHERE project_id = ? ORDER BY created_at ASC"
        );
        $notes_stmt->execute([$p['id']]);
        $notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($notes) > 0) {
            $lines[] = '## Notes';
            $lines[] = '';
            foreach ($notes as $note) {
                $lines[] = '### ' . format_ts($note['created_at']);
                $lines[] = $note['content'];
                $lines[] = '';
            }
        }
    }

    $lines[] = '---';
    return implode("\n", $lines) . "\n";
}
