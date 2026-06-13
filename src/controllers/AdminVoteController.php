<?php

require_once BASE_PATH . '/src/helpers/csrf.php';

use models\Vote;
use models\Meeting;

/**
 * Собрания и голосования админ-панели: создание/закрытие/удаление голосований,
 * выгрузка итогов и управление собраниями. Выделено из AdminController.
 */
class AdminVoteController
{
    public static function meetings(): void
    {
        requireRole([1, 2, 4]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/meetings');
                return;
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                if (empty($_POST['meeting_date']) || empty($_POST['topic'])) {
                    flash('Дата и тема обязательны', 'error');
                    redirect('/admin/meetings');
                    return;
                }
                Meeting::create(
                    $_POST['meeting_date'],
                    trim($_POST['topic']),
                    trim($_POST['location'] ?? '')
                );
                \models\Notification::broadcastAll(
                    'meeting',
                    'Назначено собрание',
                    'Тема: ' . trim($_POST['topic']) . '. Дата: ' . date('d.m.Y', strtotime($_POST['meeting_date'])) . '.',
                    '/dashboard',
                    getCurrentUser()['id']
                );
                flash('Собрание создано, члены уведомлены', 'success');
                redirect('/admin/meetings');
            }

            if ($action === 'delete') {
                Meeting::delete((int)$_POST['id']);
                flash('Удалено', 'success');
                redirect('/admin/meetings');
            }
        }

        $meetings = Meeting::all();

        renderTemplate('admin/meetings', compact('meetings') + ['flash' => getFlash()]);
    }

    public static function votes(): void
    {
        requireRole([1, 2, 4]);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/votes');
                return;
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $options = array_values(array_filter(array_map('trim', explode("\n", $_POST['options'] ?? ''))));
                Vote::create(
                    trim($_POST['title']),
                    trim($_POST['description'] ?? ''),
                    getCurrentUser()['id'],
                    $options
                );
                \models\Notification::broadcastAll(
                    'vote',
                    'Новое голосование',
                    trim($_POST['title']),
                    '/votes',
                    getCurrentUser()['id']
                );
                flash('Голосование создано, члены уведомлены', 'success');
                redirect('/admin/votes');
            }
        }

        $votes = Vote::all();

        renderTemplate('admin/votes', compact('votes') + ['flash' => getFlash()]);
    }

    public static function votesClose(): void
    {
        requireRole([1, 2, 4]);

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            flash('Ошибка безопасности', 'error');
            redirect('/admin/votes');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db = \services\DatabaseConnection::get();
            $stmt = $db->prepare("UPDATE votes SET status='closed', ended_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('Голосование закрыто', 'success');
        }

        redirect('/admin/votes');
    }

    public static function votesDelete(): void
    {
        requireRole([1, 2, 4]);

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            flash('Ошибка безопасности', 'error');
            redirect('/admin/votes');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db = \services\DatabaseConnection::get();

            $stmt = $db->prepare("DELETE FROM votes_options WHERE vote_id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $db->prepare("DELETE FROM votes_participants WHERE vote_id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $db->prepare("DELETE FROM votes WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            flash('Голосование удалено', 'success');
        }

        redirect('/admin/votes');
    }

    /** Выгрузка итогов голосования в CSV: поимённо, с участком и датой голоса. */
    public static function votesExport(): void
    {
        requireRole([1, 2, 4]);
        $id = (int)($_GET['id'] ?? 0);
        $vote = $id > 0 ? \models\Vote::find($id) : null;
        if (!$vote) {
            http_response_code(404);
            renderTemplate('error_404', ['uri' => '/admin/votes/export']);
            return;
        }

        $results = \models\Vote::results($id);
        $options = [];
        $db = \services\DatabaseConnection::get();
        $stmt = $db->prepare("SELECT option_text, vote_count FROM votes_options WHERE vote_id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($o = $r->fetch_assoc()) $options[] = $o;
        $stmt->close();

        $statusRu = ['active' => 'Активно', 'closed' => 'Закрыто', 'draft' => 'Черновик'];
        $started  = !empty($vote['started_at']) ? date('d.m.Y H:i', strtotime($vote['started_at'])) : '—';
        $ended    = !empty($vote['ended_at'])   ? date('d.m.Y H:i', strtotime($vote['ended_at']))   : '—';

        $fname = 'golosovanie_' . $id . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        echo "\xEF\xBB\xBF"; // BOM для кириллицы в Excel

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Голосование:', $vote['title']], ';');
        if (!empty($vote['description'])) fputcsv($out, ['Описание:', $vote['description']], ';');
        fputcsv($out, ['Статус:', $statusRu[$vote['status']] ?? $vote['status']], ';');
        fputcsv($out, ['Начато:', $started], ';');
        fputcsv($out, ['Завершено:', $ended], ';');
        fputcsv($out, ['Всего проголосовало:', count($results)], ';');
        fputcsv($out, [], ';');

        fputcsv($out, ['ИТОГИ ПО ВАРИАНТАМ'], ';');
        fputcsv($out, ['Вариант', 'Голосов'], ';');
        foreach ($options as $o) {
            fputcsv($out, [$o['option_text'], (int)$o['vote_count']], ';');
        }
        fputcsv($out, [], ';');

        fputcsv($out, ['ПОИМЁННЫЙ СПИСОК'], ';');
        fputcsv($out, ['№ участка', 'ФИО', 'Выбор', 'Дата и время голоса'], ';');
        foreach ($results as $row) {
            $plot = plotFromAddress($row['address'] ?? '');
            fputcsv($out, [
                $plot ? '№' . $plot : '',
                $row['full_name'] ?? '',
                $row['option_text'] ?? '',
                !empty($row['voted_at']) ? date('d.m.Y H:i', strtotime($row['voted_at'])) : '',
            ], ';');
        }
        fclose($out);
    }
}
