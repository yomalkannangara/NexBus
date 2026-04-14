<?php
$S = $S ?? [];
$recent = $recent ?? [];
$filter = in_array(($filter ?? 'all'), ['all', 'unread', 'message', 'alert'], true) ? $filter : 'all';
$unreadCount = (int)($unread_count ?? 0);
$msg = $msg ?? null;

$flashMap = [
    'read' => ['type' => 'ok', 'text' => 'Message marked as read.'],
    'read_all' => ['type' => 'ok', 'text' => 'All messages marked as read.'],
    'error' => ['type' => 'err', 'text' => 'Unable to update message status.'],
];

function tp_msg_time_ago(?string $ts): string
{
    if (!$ts) {
        return 'Unknown time';
    }

    $at = strtotime($ts);
    if ($at === false) {
        return $ts;
    }

    $diff = time() - $at;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return (string)max(1, intdiv($diff, 60)) . ' min ago';
    }
    if ($diff < 86400) {
        return (string)max(1, intdiv($diff, 3600)) . ' hr ago';
    }
    return date('Y-m-d H:i', $at);
}

function tp_msg_type_class(string $type): string
{
    return match ($type) {
        'Delay', 'Alert', 'Breakdown', 'Timetable' => 'is-alert',
        default => 'is-message',
    };
}

function tp_msg_source(array $row): string
{
    $name = trim((string)($row['source_name'] ?? ''));
    return $name !== '' ? $name : 'Depot Messaging';
}

function tp_msg_priority(array $row): ?string
{
    $priority = strtolower(trim((string)($row['priority'] ?? 'normal')));
    if (!in_array($priority, ['urgent', 'critical'], true)) {
        return null;
    }
    return strtoupper($priority);
}
?>

<style>
:root { --maroon:#7B1C3E; --gold:#f3b944; }

.tmsg-page { color: #111827; }
.tmsg-hero {
    background: linear-gradient(135deg, var(--maroon) 0%, #a8274e 100%);
    border-bottom: 4px solid var(--gold);
    border-radius: 14px;
    color: #fff;
    padding: 22px 26px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.tmsg-hero h1 { margin: 0; font-size: 1.35rem; font-weight: 800; color: #ffffff; }
.tmsg-hero p { margin: 4px 0 0; font-size: .86rem; color: rgba(255,255,255,.92); }
.tmsg-badge {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 999px;
    padding: 6px 14px;
    font-size: .78rem;
    font-weight: 800;
    color: #fff;
}

.tmsg-flash {
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 12px;
    font-size: .86rem;
    font-weight: 700;
}
.tmsg-flash.ok  { background: #dcfce7; color: #14532d; border: 1px solid #86efac; }
.tmsg-flash.err { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.tmsg-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.tmsg-filters { display: flex; gap: 8px; flex-wrap: wrap; }
.tmsg-filter {
    text-decoration: none;
    color: #7b1c3e;
    border: 1px solid #e8d39a;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: .78rem;
    font-weight: 700;
    background: #fffdf6;
}
.tmsg-filter.active {
    background: #7b1c3e;
    color: #fff;
    border-color: #7b1c3e;
}
.tmsg-mark-all {
    border: none;
    border-radius: 8px;
    background: #7b1c3e;
    color: #fff;
    padding: 8px 12px;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
}

.tmsg-list {
    display: grid;
    gap: 12px;
}
.tmsg-item {
    background: #fff;
    border: 1px solid #f2e6d2;
    border-left: 4px solid #d1d5db;
    border-radius: 12px;
    padding: 14px;
    box-shadow: 0 4px 14px rgba(17,24,39,.06);
}
.tmsg-item.unread { border-left-color: #7b1c3e; }
.tmsg-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 8px;
}
.tmsg-title {
    margin: 0;
    font-size: .95rem;
    font-weight: 800;
    color: #111827;
}
.tmsg-meta {
    margin-top: 3px;
    font-size: .75rem;
    color: #6b7280;
}
.tmsg-tags { display: flex; gap: 6px; flex-wrap: wrap; }
.tmsg-tag {
    border-radius: 999px;
    padding: 3px 8px;
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.tmsg-tag.is-message { background: #e0f2fe; color: #075985; }
.tmsg-tag.is-alert { background: #ffedd5; color: #9a3412; }
.tmsg-tag.priority { background: #fee2e2; color: #991b1b; }

.tmsg-body {
    margin: 0;
    font-size: .88rem;
    color: #1f2937;
    line-height: 1.6;
}

.tmsg-actions {
    margin-top: 12px;
    display: flex;
    justify-content: flex-end;
}
.tmsg-read {
    border: 1px solid #7b1c3e;
    background: #fff;
    color: #7b1c3e;
    border-radius: 7px;
    padding: 6px 10px;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
}
.tmsg-read-state {
    font-size: .75rem;
    color: #4b5563;
    font-weight: 700;
    background: #f3f4f6;
    border-radius: 999px;
    padding: 4px 10px;
}

.tmsg-empty {
    padding: 40px 16px;
    border: 1px dashed #d1d5db;
    border-radius: 12px;
    text-align: center;
    color: #6b7280;
    background: #fff;
}
</style>

<div class="tmsg-page">
    <div class="tmsg-hero">
        <div>
            <h1>Private Timekeeper Messages</h1>
            <p><?= htmlspecialchars($S['depot_name'] ?? 'Operator') ?> notifications from depot operations.</p>
        </div>
        <div class="tmsg-badge">Unread: <?= $unreadCount ?></div>
    </div>

    <?php if (!empty($msg) && isset($flashMap[$msg])): ?>
        <div class="tmsg-flash <?= htmlspecialchars($flashMap[$msg]['type']) ?>">
            <?= htmlspecialchars($flashMap[$msg]['text']) ?>
        </div>
    <?php endif; ?>

    <div class="tmsg-toolbar">
        <div class="tmsg-filters">
            <a class="tmsg-filter <?= $filter === 'all' ? 'active' : '' ?>" href="/TP/messages?filter=all">All</a>
            <a class="tmsg-filter <?= $filter === 'unread' ? 'active' : '' ?>" href="/TP/messages?filter=unread">Unread</a>
            <a class="tmsg-filter <?= $filter === 'message' ? 'active' : '' ?>" href="/TP/messages?filter=message">Messages</a>
            <a class="tmsg-filter <?= $filter === 'alert' ? 'active' : '' ?>" href="/TP/messages?filter=alert">Alerts</a>
        </div>

        <?php if ($unreadCount > 0): ?>
            <form method="post" action="/TP/messages?action=read_all&amp;filter=<?= urlencode($filter) ?>">
                <button type="submit" class="tmsg-mark-all">Mark all as read</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="tmsg-list">
        <?php if (empty($recent)): ?>
            <div class="tmsg-empty">No messages available for this filter.</div>
        <?php else: ?>
            <?php foreach ($recent as $row): ?>
                <?php
                $id = (int)($row['id'] ?? 0);
                $type = (string)($row['type'] ?? 'Message');
                $isUnread = ((int)($row['is_seen'] ?? 0) === 0);
                $sourceRole = trim((string)($row['source_role'] ?? ''));
                $priority = tp_msg_priority($row);
                ?>
                <article class="tmsg-item <?= $isUnread ? 'unread' : '' ?>">
                    <div class="tmsg-top">
                        <div>
                            <h3 class="tmsg-title"><?= htmlspecialchars(tp_msg_source($row)) ?></h3>
                            <div class="tmsg-meta">
                                <?= htmlspecialchars($sourceRole !== '' ? $sourceRole : 'Depot Officer') ?>
                                | <?= htmlspecialchars(tp_msg_time_ago((string)($row['created_at'] ?? ''))) ?>
                            </div>
                        </div>
                        <div class="tmsg-tags">
                            <span class="tmsg-tag <?= tp_msg_type_class($type) ?>"><?= htmlspecialchars($type) ?></span>
                            <?php if ($priority !== null): ?>
                                <span class="tmsg-tag priority"><?= htmlspecialchars($priority) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="tmsg-body"><?= nl2br(htmlspecialchars((string)($row['message'] ?? ''))) ?></p>

                    <div class="tmsg-actions">
                        <?php if ($isUnread && $id > 0): ?>
                            <form method="post" action="/TP/messages?action=read&amp;id=<?= $id ?>&amp;filter=<?= urlencode($filter) ?>">
                                <button type="submit" class="tmsg-read">Mark as read</button>
                            </form>
                        <?php else: ?>
                            <span class="tmsg-read-state">Read</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
