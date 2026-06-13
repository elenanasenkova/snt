<?php
require_once BASE_PATH . '/src/helpers/csrf.php';

use models\{Announcement, Fee, Finance, Vote, Meeting, Document, Ticket, User};

class MemberController {

    public static function dashboard(): void {
        requireAuth();
        $user          = getCurrentUser();
        $meetings      = Meeting::upcoming();
        $announcements = Announcement::all(1, 5);
        $plotNumber    = plotFromAddress($user['address'] ?? '');
        $fees          = $plotNumber ? Fee::paymentsByPlot($plotNumber) : [];
        $totalPaid     = array_sum(array_column(array_filter($fees ?? [], fn($f) => $f['status'] === 'paid'), 'paid_amount'));
        $debtAmount    = array_sum(array_column(array_filter($fees ?? [], fn($f) => $f['status'] === 'unpaid'), 'required_amount'));
        renderTemplate('member/dashboard', compact('user', 'meetings', 'announcements', 'fees', 'plotNumber', 'totalPaid', 'debtAmount'));
    }

    public static function classifieds(): void {
        requireAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                redirect('/classifieds');
            }
            $allowedCategories = ['sale', 'buy', 'wanted', 'service', 'info'];
            $category = in_array($_POST['category'] ?? '', $allowedCategories, true)
                ? $_POST['category']
                : 'info';
            Announcement::create(
                getCurrentUser()['id'],
                trim($_POST['title']   ?? ''),
                trim($_POST['content'] ?? ''),
                $category
            );
            flash('Объявление добавлено', 'success');
            redirect('/classifieds');
        }

        $announcements = Announcement::all($page, 15);
        $totalPages    = max(1, ceil(Announcement::count() / 15));
        $user          = getCurrentUser();
        renderTemplate('member/classifieds', compact('announcements', 'page', 'totalPages', 'user') + ['flash' => getFlash()]);
    }

    public static function deleteClassified(): void {
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/classifieds');
        }
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
            redirect('/classifieds');
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('Некорректный идентификатор объявления.', 'error');
            redirect('/classifieds');
        }
        $db = \services\DatabaseConnection::get();
        $stmt = $db->prepare("SELECT author_id FROM announcements WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        if (!$row || (int)$row['author_id'] !== (int)getCurrentUser()['id']) {
            flash('Объявление не найдено или у вас нет прав на его удаление.', 'error');
            redirect('/classifieds');
        }
        Announcement::delete($id);
        flash('Объявление удалено.', 'success');
        redirect('/classifieds');
    }

    public static function finances(): void {
        requireAuth();
        $user       = getCurrentUser();
        $plotNumber = plotFromAddress($user['address'] ?? '');
        $fees       = $plotNumber ? Fee::paymentsByPlot($plotNumber) : [];
        $years      = Fee::availableYears();
        renderTemplate('member/finances', compact('fees', 'plotNumber', 'years', 'user') + ['flash' => getFlash()]);
    }

    public static function votes(): void {
        requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                redirect('/votes');
            }
            $result = Vote::castVote(
                (int)$_POST['vote_id'],
                getCurrentUser()['id'],
                (int)$_POST['option_id']
            );
            if ($result) {
                flash('Голос принят', 'success');
            } else {
                flash('Вы уже голосовали в этом опросе', 'warning');
            }
            redirect('/votes');
        }

        $votes  = Vote::active();
        $userId = getCurrentUser()['id'];

        $userVotes = [];
        foreach ($votes as $vote) {
            $result = Vote::userVoted($vote['id'], $userId);
            if ($result) {
                $userVotes[$vote['id']] = $result;
            }
        }

        renderTemplate('member/votes', ['votes' => $votes, 'userId' => $userId, 'flash' => getFlash(), 'userVotes' => $userVotes]);
    }

    public static function protocols(): void {
        requireAuth();
        $documents = Document::allPublic();
        renderTemplate('member/protocols', compact('documents'));
    }

    public static function discussions(): void {
        requireAuth();
        $posts = Announcement::allByCategory('info', 30);
        $user  = getCurrentUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                redirect('/discussions');
            }
            Announcement::create(
                getCurrentUser()['id'],
                trim($_POST['title']   ?? ''),
                trim($_POST['content'] ?? ''),
                'info'
            );
            flash('Добавлено', 'success');
            redirect('/discussions');
        }

        renderTemplate('member/discussions', compact('posts', 'user') + ['flash' => getFlash()]);
    }

    public static function reports(): void {
        requireAuth();
        $year       = (int)($_GET['year'] ?? date('Y'));
        $summary    = Finance::summary($year);
        $feeTypes   = Fee::typesByYear($year);
        $debtByPlot = Fee::debtByPlot($year);
        renderTemplate('member/reports', compact('summary', 'feeTypes', 'debtByPlot', 'year'));
    }

    /** Уведомления текущего пользователя; открытие помечает все как прочитанные. */
    public static function notifications(): void {
        requireAuth();
        $userId = getCurrentUser()['id'];
        $items  = \models\Notification::forUser($userId, 100);
        \models\Notification::markAllRead($userId);
        renderTemplate('member/notifications', compact('items'));
    }

    /**
     * Обращения в правление. GET без id — форма + список своих;
     * GET ?id=N — просмотр переписки; POST — создать обращение или добавить ответ.
     */
    public static function tickets(): void {
        requireAuth();
        \models\Ticket::ensureTable();
        $userId = getCurrentUser()['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности. Попробуйте ещё раз.', 'error');
                redirect('/tickets');
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $subject = trim($_POST['subject'] ?? '');
                $body    = trim($_POST['body'] ?? '');
                if ($subject === '' || $body === '') {
                    flash('Укажите тему и текст обращения.', 'error');
                    redirect('/tickets');
                }
                $newId = \models\Ticket::create($userId, mb_substr($subject, 0, 200), $body);
                flash('Обращение отправлено в правление.', 'success');
                redirect('/tickets?id=' . $newId);
            }

            if ($action === 'reply') {
                $ticketId = (int)($_POST['ticket_id'] ?? 0);
                $body     = trim($_POST['body'] ?? '');
                $ticket   = \models\Ticket::find($ticketId);
                // Член может отвечать только в своём обращении и пока оно не закрыто
                if (!$ticket || (int)$ticket['user_id'] !== (int)$userId) {
                    flash('Обращение не найдено.', 'error');
                    redirect('/tickets');
                }
                if ($ticket['status'] === 'closed') {
                    flash('Обращение закрыто, ответ невозможен.', 'warning');
                    redirect('/tickets?id=' . $ticketId);
                }
                if ($body === '') {
                    flash('Введите текст ответа.', 'error');
                    redirect('/tickets?id=' . $ticketId);
                }
                // Ответ члена возвращает тикет в работу
                \models\Ticket::addReply($ticketId, $userId, $body, 'in_progress');
                flash('Ответ добавлен.', 'success');
                redirect('/tickets?id=' . $ticketId);
            }

            redirect('/tickets');
        }

        $viewId = (int)($_GET['id'] ?? 0);
        $ticket = null;
        $replies = [];
        if ($viewId > 0) {
            $ticket = \models\Ticket::find($viewId);
            if (!$ticket || (int)$ticket['user_id'] !== (int)$userId) {
                flash('Обращение не найдено.', 'error');
                redirect('/tickets');
            }
            $replies = \models\Ticket::replies($viewId);
        }

        $tickets = \models\Ticket::forUser($userId);
        renderTemplate('member/tickets', compact('tickets', 'ticket', 'replies') + ['flash' => getFlash()]);
    }

}
