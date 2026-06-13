<?php

require_once BASE_PATH . '/src/helpers/csrf.php';

use models\{Announcement, Vote, Meeting};

/**
 * Единая «Лента сообщества» (/feed): объявления + события (собрания) + голосования
 * в одном разделе с одной формой создания. Выделено из MemberController.
 */
class FeedController
{
    /**
     * Тип создаваемой записи выбирается в форме; события и голосования доступны
     * только правлению (canMeetings), объявления — любому члену. Здесь же —
     * голосование, удаление своих объявлений и управление событиями/голосованиями
     * для правления.
     */
    public static function feed(): void {
        requireAuth();
        $user   = getCurrentUser();
        $userId = (int)$user['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                redirect('/feed');
            }
            $action = $_POST['action'] ?? '';

            switch ($action) {
                // --- Создать объявление (любой член) ---
                case 'create_announcement': {
                    $allowed  = ['sale', 'buy', 'wanted', 'service', 'info'];
                    $category = in_array($_POST['category'] ?? '', $allowed, true) ? $_POST['category'] : 'info';
                    $title    = trim($_POST['title'] ?? '');
                    $content  = trim($_POST['content'] ?? '');
                    if ($title === '' || $content === '') {
                        flash('Заголовок и описание обязательны.', 'error');
                        redirect('/feed');
                    }
                    Announcement::create($userId, $title, $content, $category);
                    flash('Объявление опубликовано.', 'success');
                    redirect('/feed?tab=announcements');
                }

                // --- Создать событие (только правление) ---
                case 'create_event': {
                    if (!canMeetings()) { flash('Недостаточно прав.', 'error'); redirect('/feed'); }
                    $date  = $_POST['meeting_date'] ?? '';
                    $topic = trim($_POST['topic'] ?? '');
                    if ($date === '' || $topic === '') {
                        flash('Дата и тема события обязательны.', 'error');
                        redirect('/feed');
                    }
                    Meeting::create($date, $topic, trim($_POST['location'] ?? ''));
                    \models\Notification::broadcastAll(
                        'meeting', 'Назначено событие',
                        'Тема: ' . $topic . '. Дата: ' . date('d.m.Y', strtotime($date)) . '.',
                        '/feed?tab=events', $userId
                    );
                    flash('Событие создано, члены уведомлены.', 'success');
                    redirect('/feed?tab=events');
                }

                // --- Создать голосование (только правление) ---
                case 'create_vote': {
                    if (!canMeetings()) { flash('Недостаточно прав.', 'error'); redirect('/feed'); }
                    $title   = trim($_POST['title'] ?? '');
                    $options = array_values(array_filter(array_map('trim', explode("\n", $_POST['options'] ?? ''))));
                    if ($title === '' || count($options) < 2) {
                        flash('Укажите вопрос и минимум два варианта (по одному в строке).', 'error');
                        redirect('/feed');
                    }
                    Vote::create($title, trim($_POST['description'] ?? ''), $userId, $options);
                    \models\Notification::broadcastAll(
                        'vote', 'Новое голосование', $title, '/feed?tab=votes', $userId
                    );
                    flash('Голосование создано, члены уведомлены.', 'success');
                    redirect('/feed?tab=votes');
                }

                // --- Проголосовать (любой член) ---
                case 'cast_vote': {
                    $ok = Vote::castVote((int)($_POST['vote_id'] ?? 0), $userId, (int)($_POST['option_id'] ?? 0));
                    flash($ok ? 'Голос принят.' : 'Вы уже голосовали в этом опросе.', $ok ? 'success' : 'warning');
                    redirect('/feed?tab=votes');
                }

                // --- Удалить своё объявление ---
                case 'delete_announcement': {
                    $id = (int)($_POST['id'] ?? 0);
                    $db = \services\DatabaseConnection::get();
                    $stmt = $db->prepare("SELECT author_id FROM announcements WHERE id = ?");
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($row && (int)$row['author_id'] === $userId) {
                        Announcement::delete($id);
                        flash('Объявление удалено.', 'success');
                    } else {
                        flash('Объявление не найдено или нет прав.', 'error');
                    }
                    redirect('/feed?tab=announcements');
                }

                // --- Удалить событие (правление) ---
                case 'delete_event': {
                    if (!canMeetings()) { flash('Недостаточно прав.', 'error'); redirect('/feed'); }
                    Meeting::delete((int)($_POST['id'] ?? 0));
                    flash('Событие удалено.', 'success');
                    redirect('/feed?tab=events');
                }

                // --- Закрыть голосование (правление) ---
                case 'close_vote': {
                    if (!canMeetings()) { flash('Недостаточно прав.', 'error'); redirect('/feed'); }
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $db = \services\DatabaseConnection::get();
                        $stmt = $db->prepare("UPDATE votes SET status='closed', ended_at=NOW() WHERE id=?");
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->close();
                        flash('Голосование закрыто.', 'success');
                    }
                    redirect('/feed?tab=votes');
                }
            }
            redirect('/feed');
        }

        // GET — собрать ленту
        $announcements = Announcement::all(1, 30);
        $events        = Meeting::all();
        $votes         = Vote::active();
        $userVotes     = [];
        foreach ($votes as $v) {
            if (Vote::userVoted((int)$v['id'], $userId)) $userVotes[(int)$v['id']] = true;
        }
        $canManage = canMeetings();
        $tab       = $_GET['tab'] ?? 'all';
        renderTemplate('member/feed', compact(
            'user', 'userId', 'announcements', 'events', 'votes', 'userVotes', 'canManage', 'tab'
        ) + ['flash' => getFlash()]);
    }
}
